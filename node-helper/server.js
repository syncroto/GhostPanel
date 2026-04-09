'use strict';

/**
 * GPanel Node.js Helper
 * Responsável por:
 *   1. Terminal web interativo (WebSocket + PTY)
 *   2. Streaming de logs em tempo real (tail -f via WebSocket)
 *
 * Porta: 3001 (proxy reverso via Nginx /ws/)
 */

const WebSocket = require('ws');
const pty       = require('node-pty');
const path      = require('path');
const fs        = require('fs');
const { spawn } = require('child_process');

// ─────────────────────────────────────────────────────────────────────────── //
//  Configuração
// ─────────────────────────────────────────────────────────────────────────── //
const PORT       = parseInt(process.env.WS_PORT || '3001', 10);
const HOST       = process.env.WS_HOST || '127.0.0.1';
const GPANEL_DIR = process.env.GPANEL_DIR || '/gpanel';

// Caminhos de log permitidos (whitelist de segurança)
const ALLOWED_LOG_ROOTS = [
    '/var/www/sites',
    `${GPANEL_DIR}/storage/logs`,
    '/var/log/nginx',
    '/var/log/php',
];

// ─────────────────────────────────────────────────────────────────────────── //
//  Servidor WebSocket
// ─────────────────────────────────────────────────────────────────────────── //
const wss = new WebSocket.Server({ host: HOST, port: PORT });

console.log(`[GPanel Node Helper] WebSocket server ouvindo em ws://${HOST}:${PORT}`);

wss.on('connection', (ws, req) => {
    const url    = new URL(req.url, `ws://${HOST}`);
    const action = url.pathname.replace(/^\/ws\//, '');

    console.log(`[WS] Nova conexão: ${action} — ${req.socket.remoteAddress}`);

    switch (true) {
        case action === 'terminal':
            handleTerminal(ws, url);
            break;

        case action.startsWith('logs/'):
            handleLogs(ws, url, action.replace('logs/', ''));
            break;

        default:
            ws.send(JSON.stringify({ type: 'error', message: `Ação desconhecida: ${action}` }));
            ws.close();
    }
});

// ─────────────────────────────────────────────────────────────────────────── //
//  Terminal web interativo (node-pty)
// ─────────────────────────────────────────────────────────────────────────── //
function handleTerminal(ws, url) {
    // Parâmetros do terminal
    const cols     = parseInt(url.searchParams.get('cols')  || '120', 10);
    const rows     = parseInt(url.searchParams.get('rows')  || '30',  10);
    const sitePath = url.searchParams.get('path') || '/var/www/sites';

    // Valida o caminho — aceita qualquer path absoluto válido (painel de gerenciamento)
    const resolvedPath = path.resolve(sitePath);
    if (!resolvedPath.startsWith('/')) {
        ws.send(JSON.stringify({ type: 'error', message: 'Caminho inválido.' }));
        ws.close();
        return;
    }

    // Cria PTY como www-data com rbash (restricted bash — impede cd /, cd .., etc.)
    // rbash impede: cd com caminhos absolutos, alteração de PATH, redirecionamentos perigosos
    const shell = fs.existsSync('/bin/rbash') ? '/bin/rbash' : '/bin/bash';
    // sudo reseta o ambiente por padrão — passamos os env vars explicitamente via `env`
    const ptyProcess = pty.spawn('sudo', [
        '-u', 'www-data',
        '--',
        'env',
        `HOME=${resolvedPath}`,
        `PWD=${resolvedPath}`,
        `TERM=xterm-256color`,
        `LANG=en_US.UTF-8`,
        `PATH=/usr/bin:/bin`,
        `SHELL=${shell}`,
        `PS1=\\u@\\W\\$ `,
        `npm_config_cache=${resolvedPath}/.npm`,
        shell, '--norc', '--noprofile',
    ], {
        name: 'xterm-256color',
        cols,
        rows,
        cwd: resolvedPath,
    });

    console.log(`[Terminal] PTY PID ${ptyProcess.pid} | user=www-data | shell=${shell} | cwd=${resolvedPath}`);

    // PTY → WebSocket
    ptyProcess.onData((data) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'output', data }));
        }
    });

    ptyProcess.onExit(({ exitCode }) => {
        console.log(`[Terminal] PTY ${ptyProcess.pid} encerrado (exit ${exitCode})`);
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'exit', code: exitCode }));
            ws.close();
        }
    });

    // WebSocket → PTY
    ws.on('message', (msg) => {
        try {
            const packet = JSON.parse(msg.toString());

            switch (packet.type) {
                case 'input':
                    ptyProcess.write(packet.data);
                    break;

                case 'resize':
                    if (packet.cols && packet.rows) {
                        ptyProcess.resize(
                            Math.max(1, Math.min(500, packet.cols)),
                            Math.max(1, Math.min(200, packet.rows))
                        );
                    }
                    break;

                case 'ping':
                    ws.send(JSON.stringify({ type: 'pong' }));
                    break;
            }
        } catch (e) {
            console.error('[Terminal] Erro ao processar mensagem:', e.message);
        }
    });

    ws.on('close', () => {
        console.log(`[Terminal] Conexão fechada — encerrando PTY ${ptyProcess.pid}`);
        try { ptyProcess.kill(); } catch {}
    });

    ws.on('error', (err) => {
        console.error('[Terminal] Erro WS:', err.message);
        try { ptyProcess.kill(); } catch {}
    });
}

// ─────────────────────────────────────────────────────────────────────────── //
//  Streaming de logs (tail -f)
// ─────────────────────────────────────────────────────────────────────────── //
function handleLogs(ws, url, logParam) {
    // logParam pode ser: "nginx/access" ou caminho relativo
    const logMap = {
        'nginx/access':  '/var/log/nginx/access.log',
        'nginx/error':   '/var/log/nginx/error.log',
        'gpanel/worker': `${GPANEL_DIR}/storage/logs/worker.log`,
        'gpanel/app':    `${GPANEL_DIR}/storage/logs/laravel.log`,
    };

    let logPath = logMap[logParam];

    // Permite caminho direto sob /var/www/sites/*/logs/
    if (!logPath) {
        const decoded = decodeURIComponent(logParam);
        const resolved = path.resolve('/' + decoded);

        const isAllowed = ALLOWED_LOG_ROOTS.some(root => resolved.startsWith(root))
            && resolved.endsWith('.log');

        if (!isAllowed) {
            ws.send(JSON.stringify({ type: 'error', message: 'Arquivo de log não permitido.' }));
            ws.close();
            return;
        }

        logPath = resolved;
    }

    if (!fs.existsSync(logPath)) {
        ws.send(JSON.stringify({ type: 'error', message: `Arquivo não encontrado: ${logPath}` }));
        ws.close();
        return;
    }

    console.log(`[Logs] Streaming ${logPath}`);

    // Envia as últimas 50 linhas imediatamente
    const tail = spawn('tail', ['-n', '50', '-f', logPath]);

    tail.stdout.on('data', (data) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'log', data: data.toString() }));
        }
    });

    tail.stderr.on('data', (data) => {
        console.error('[Logs] stderr:', data.toString());
    });

    ws.on('close', () => {
        console.log(`[Logs] Conexão fechada — encerrando tail`);
        tail.kill();
    });

    ws.on('error', (err) => {
        console.error('[Logs] Erro WS:', err.message);
        tail.kill();
    });

    ws.on('message', (msg) => {
        try {
            const packet = JSON.parse(msg.toString());
            if (packet.type === 'ping') {
                ws.send(JSON.stringify({ type: 'pong' }));
            }
        } catch {}
    });
}

// ─────────────────────────────────────────────────────────────────────────── //
//  Graceful shutdown
// ─────────────────────────────────────────────────────────────────────────── //
process.on('SIGTERM', () => {
    console.log('[GPanel Node Helper] SIGTERM recebido, encerrando...');
    wss.close(() => {
        console.log('[GPanel Node Helper] Encerrado.');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    wss.close(() => process.exit(0));
});
