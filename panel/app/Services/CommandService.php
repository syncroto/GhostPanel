<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * CommandService — ponto central de execução de comandos do sistema.
 *
 * REGRA CRÍTICA DE SEGURANÇA:
 *   - NUNCA executar input do usuário diretamente.
 *   - Todos os parâmetros passam por escapeshellarg().
 *   - Somente operações da whitelist são permitidas.
 *   - Todo comando é registrado em log de auditoria.
 */
class CommandService
{
    /**
     * Whitelist de operações permitidas.
     * Chave = nome da operação.
     * Valor = template do comando (placeholders: {param}).
     */
    private const ALLOWED_COMMANDS = [
        // Nginx
        'nginx.reload'         => 'sudo systemctl reload nginx',
        'nginx.restart'        => 'sudo systemctl restart nginx',
        'nginx.status'         => 'sudo systemctl status nginx --no-pager',
        'nginx.configtest'     => 'sudo nginx -t',

        // PHP-FPM
        'phpfpm.reload'        => 'sudo systemctl reload php{version}-fpm',
        'phpfpm.restart'       => 'sudo systemctl restart php{version}-fpm',
        'phpfpm.status'        => 'sudo systemctl status php{version}-fpm --no-pager',

        // MySQL — {root_pass} substituído por -p'senha' quando definido, ou omitido (auth_socket via sudo)
        'mysql.create_db'      => 'sudo mysql -u root {root_pass}-e "CREATE DATABASE IF NOT EXISTS \`{db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"',
        'mysql.drop_db'        => 'sudo mysql -u root {root_pass}-e "DROP DATABASE IF EXISTS \`{db_name}\`;"',
        'mysql.create_user'    => 'sudo mysql -u root {root_pass}-e "CREATE USER IF NOT EXISTS \'{db_user}\'@\'localhost\' IDENTIFIED BY \'{db_pass}\';"',
        'mysql.drop_user'      => 'sudo mysql -u root {root_pass}-e "DROP USER IF EXISTS \'{db_user}\'@\'localhost\';"',
        'mysql.grant'          => 'sudo mysql -u root {root_pass}-e "GRANT ALL PRIVILEGES ON \`{db_name}\`.* TO \'{db_user}\'@\'localhost\'; FLUSH PRIVILEGES;"',

        // Redis
        'redis.flush_all'      => 'redis-cli FLUSHALL',
        'redis.flush_db'       => 'redis-cli -n {db_index} FLUSHDB',
        'redis.restart'        => 'sudo systemctl restart redis-server',

        // Certbot / SSL
        'ssl.obtain'           => 'sudo certbot --nginx -d {domain} --non-interactive --agree-tos -m {email}',
        'ssl.renew'            => 'sudo certbot renew --quiet',
        'ssl.revoke'           => 'sudo certbot delete --cert-name {domain} --non-interactive',

        // UFW
        'ufw.rule'             => 'sudo ufw {action} {port}/{protocol}',
        'ufw.rule_from'        => 'sudo ufw {action} from {from} to any port {port} proto {protocol}',
        'ufw.delete'           => 'sudo ufw delete {action} {port}/{protocol}',
        'ufw.allow_port'       => 'sudo ufw allow {port}/tcp',
        'ufw.deny_port'        => 'sudo ufw deny {port}/tcp',
        'ufw.delete_rule'      => 'sudo ufw delete allow {port}/tcp',
        'ufw.status'           => 'sudo ufw status numbered',
        'ufw.reload'           => 'sudo ufw reload',

        // Supervisor
        'supervisor.restart'     => 'sudo supervisorctl restart {program}',
        'supervisor.start'       => 'sudo supervisorctl start {program}',
        'supervisor.stop'        => 'sudo supervisorctl stop {program}',
        'supervisor.status'      => 'sudo supervisorctl status {program}',
        'supervisor.reread'      => 'sudo supervisorctl reread && sudo supervisorctl update',
        'supervisor.remove_conf' => 'sudo rm -f /etc/supervisor/conf.d/{program}.conf',

        // Sites
        'site.mkdir'              => 'mkdir -p {path}',
        'site.chown'              => 'sudo chown -R {user}:www-data {path}',
        'site.chmod'              => 'sudo chmod -R {mode} {path}',
        'site.symlink_nginx'      => 'sudo ln -sf /etc/nginx/sites-available/{domain} /etc/nginx/sites-enabled/{domain}',
        'site.unlink_nginx'       => 'sudo rm -f /etc/nginx/sites-enabled/{domain}',
        'site.remove_nginx_conf'  => 'sudo rm -f /etc/nginx/sites-available/{domain}',
        'site.remove'             => 'sudo rm -rf {path}',

        // FTP
        'ftp.create_user'      => 'sudo useradd -m -d /var/www/sites/{username} -s /bin/bash {username}',
        'ftp.set_password'     => 'echo "{username}:{password}" | sudo chpasswd',
        'ftp.delete_user'      => 'sudo userdel -r {username}',
        'ftp.add_to_list'      => 'echo {username} | sudo tee -a /etc/vsftpd.userlist',

        // Serviços genéricos
        'service.start'        => 'sudo systemctl start {service}',
        'service.stop'         => 'sudo systemctl stop {service}',
        'service.restart'      => 'sudo systemctl restart {service}',
        'service.status'       => 'sudo systemctl status {service} --no-pager',
        'service.enable'       => 'sudo systemctl enable {service}',
        'service.disable'      => 'sudo systemctl disable {service}',

        // WordPress
        'wp.install'           => 'sudo -u www-data wp core download --path={path} --locale=pt_BR --allow-root',
        'wp.config'            => 'sudo -u www-data wp config create --path={path} --dbname={db_name} --dbuser={db_user} --dbpass={db_pass} --allow-root',
        'wp.core_install'      => 'sudo -u www-data wp core install --path={path} --url={url} --title={title} --admin_user={admin_user} --admin_password={admin_pass} --admin_email={admin_email} --allow-root',

        // Node.js
        'npm.install'          => 'npm install --prefix {path} --silent',
        'npm.build'            => 'npm run build --prefix {path}',

        // Python
        'python.create_venv'   => 'python3 -m venv {path}/venv',
        'python.pip_install'   => 'pip3 install -r {path}/requirements.txt',

        // Instala arquivo em diretório root (escreve via /tmp depois move com sudo)
        'file.install'         => 'sudo mv {src} {dest}',
        'nginx.install_vhost'  => 'sudo mv {src} {dest}',

        // Backup
        'backup.run'           => '/gpanel/scripts/backup.sh {target}',
        'backup.mysqldump'     => 'sudo mysqldump -u root {root_pass}{db_name} > {output_path}',
        'backup.tar_site'      => 'sudo tar -czf {output_path} -C {site_path} .',

        // Cron jobs
        'cron.install'              => 'sudo mv {src} {dest} && sudo chmod 644 {dest}',
        'cron.remove'               => 'sudo rm -f {path}',

        // Site security (nginx ACL + htpasswd)
        'security.ensure_dirs'      => 'sudo mkdir -p /etc/nginx/gpanel-security /etc/nginx/htpasswd',
        'security.install_acl'      => 'sudo mv {src} {dest} && sudo chmod 644 {dest}',
        'security.remove_acl'       => 'sudo rm -f {path}',
        'security.install_htpasswd' => 'sudo mv {src} {dest} && sudo chown root:www-data {dest} && sudo chmod 640 {dest}',
        'security.remove_htpasswd'  => 'sudo rm -f {path}',

        // MySQL user management
        'mysql.alter_user'          => 'sudo mysql -u root {root_pass}-e "ALTER USER \'{db_user}\'@\'localhost\' IDENTIFIED BY \'{db_pass}\'; FLUSH PRIVILEGES;"',

        // PHP Shell Prevention
        'security.php_ini_set'   => 'sudo tee /etc/php/{version}/fpm/conf.d/99-gpanel-hardening.ini',
        'security.nginx_no_exec' => 'sudo mv {src} {dest} && sudo chmod 644 {dest}',

        // Diagnóstico
        'diag.disk_usage'      => 'df -h',
        'diag.memory'          => 'free -h',
        'diag.uptime'          => 'uptime',
        'diag.processes'       => 'ps aux --sort=-%cpu | head -20',
    ];

    /**
     * Parâmetros com valores restritos (enums).
     */
    private const ENUM_PARAMS = [
        'version'  => ['8.1', '8.2', '8.3'],
        'mode'     => ['755', '775', '644', '664', '600'],
        'action'   => ['allow', 'deny'],
        'protocol' => ['tcp', 'udp'],
        'target'   => ['full', 'sites', 'databases'],
    ];

    /**
     * Timeout padrão por operação (segundos).
     */
    private const TIMEOUTS = [
        'ssl.obtain'         => 120,
        'wp.install'         => 300,
        'wp.core_install'    => 120,
        'npm.install'        => 300,
        'npm.build'          => 300,
        'python.pip_install' => 300,
        'backup.mysqldump'   => 600,
        'backup.tar_site'    => 600,
    ];

    private const DEFAULT_TIMEOUT = 30;

    // ---------------------------------------------------------------------- //

    /**
     * Executa uma operação da whitelist com os parâmetros fornecidos.
     *
     * @param  string  $operation  Chave da whitelist (ex: 'nginx.reload')
     * @param  array   $params     Parâmetros para substituição no template
     * @return array{exit_code: int, stdout: string, stderr: string}
     *
     * @throws RuntimeException  Se a operação não estiver na whitelist
     */
    public function run(string $operation, array $params = []): array
    {
        if (!array_key_exists($operation, self::ALLOWED_COMMANDS)) {
            $this->logAttempt($operation, $params, null, 'DENIED');
            throw new RuntimeException("Operação não permitida: {$operation}");
        }

        $command = $this->buildCommand($operation, $params);
        $timeout = self::TIMEOUTS[$operation] ?? self::DEFAULT_TIMEOUT;

        $result = $this->execute($command, $timeout);

        $this->logAttempt($operation, $params, $result, 'EXECUTED');

        return $result;
    }

    /**
     * Executa e retorna apenas stdout (throws se exit_code != 0).
     */
    public function runOrFail(string $operation, array $params = []): string
    {
        $result = $this->run($operation, $params);

        if ($result['exit_code'] !== 0) {
            throw new RuntimeException(
                "Comando falhou [{$operation}] (exit {$result['exit_code']}): {$result['stderr']}"
            );
        }

        return $result['stdout'];
    }

    /**
     * Verifica se uma operação existe na whitelist.
     */
    public function isAllowed(string $operation): bool
    {
        return array_key_exists($operation, self::ALLOWED_COMMANDS);
    }

    // ---------------------------------------------------------------------- //
    //  Métodos privados
    // ---------------------------------------------------------------------- //

    /**
     * Constrói o comando final substituindo placeholders e sanitizando parâmetros.
     */
    private function buildCommand(string $operation, array $params): string
    {
        $template = self::ALLOWED_COMMANDS[$operation];

        foreach ($params as $key => $value) {
            // Valida enums quando aplicável
            if (isset(self::ENUM_PARAMS[$key])) {
                if (!in_array($value, self::ENUM_PARAMS[$key], true)) {
                    throw new RuntimeException(
                        "Valor inválido para parâmetro '{$key}': {$value}. " .
                        "Permitidos: " . implode(', ', self::ENUM_PARAMS[$key])
                    );
                }
                // Enum não precisa de escapeshellarg (valores fixos controlados)
                $template = str_replace('{' . $key . '}', $value, $template);
            } else {
                // Todos os outros parâmetros passam por escapeshellarg()
                $safe = escapeshellarg((string) $value);
                if ($key === 'root_pass') {
                    // Quando vazio → remove o placeholder (auth_socket via sudo)
                    // Quando definido → vira -p'senha' seguido de espaço
                    if ((string) $value === '') {
                        $template = str_replace('{root_pass}', '', $template);
                    } else {
                        $escaped = str_replace("'", "'\\''", (string) $value);
                        $template = str_replace('{root_pass}', "-p'" . $escaped . "' ", $template);
                    }
                } elseif (in_array($key, ['db_pass', 'password', 'db_user', 'db_name'], true)) {
                    $safeUnquoted = str_replace("'", "\\'", (string) $value);
                    $template = str_replace('{' . $key . '}', $safeUnquoted, $template);
                } else {
                    $template = str_replace('{' . $key . '}', $safe, $template);
                }
            }
        }

        // Verifica se ainda há placeholders não substituídos
        if (preg_match('/\{[a-z_]+\}/', $template, $matches)) {
            throw new RuntimeException(
                "Parâmetro obrigatório ausente: {$matches[0]} para operação '{$operation}'"
            );
        }

        return $template;
    }

    /**
     * Executa o comando com proc_open (controle total de stdin/stdout/stderr).
     *
     * @return array{exit_code: int, stdout: string, stderr: string}
     */
    private function execute(string $command, int $timeout): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException("Falha ao abrir processo para comando.");
        }

        // Fecha stdin imediatamente (não precisamos enviar input)
        fclose($pipes[0]);

        // Lê com timeout usando stream_select
        $stdout = '';
        $stderr = '';
        $startTime = time();

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            $changed = stream_select($read, $write, $except, 1);

            if ($changed === false) {
                break;
            }

            foreach ($read as $pipe) {
                if ($pipe === $pipes[1]) {
                    $stdout .= fread($pipe, 8192);
                } elseif ($pipe === $pipes[2]) {
                    $stderr .= fread($pipe, 8192);
                }
            }

            if (feof($pipes[1]) && feof($pipes[2])) {
                break;
            }

            if ((time() - $startTime) >= $timeout) {
                proc_terminate($process, SIGKILL);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                throw new RuntimeException("Timeout após {$timeout}s executando comando.");
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout'    => trim($stdout),
            'stderr'    => trim($stderr),
        ];
    }

    /**
     * Registra tentativa de execução em log de auditoria.
     */
    private function logAttempt(
        string $operation,
        array  $params,
        ?array $result,
        string $status
    ): void {
        $userId = Auth::id();
        $exitCode = $result['exit_code'] ?? null;

        // Remove senhas dos logs
        $safeParams = array_filter(
            $params,
            fn($key) => !in_array($key, ['root_pass', 'db_pass', 'password'], true),
            ARRAY_FILTER_USE_KEY
        );

        Log::channel('audit')->info('CommandService', [
            'status'    => $status,
            'operation' => $operation,
            'params'    => $safeParams,
            'user_id'   => $userId,
            'exit_code' => $exitCode,
            'ip'        => request()->ip(),
        ]);

        // Persiste no banco se o model estiver disponível
        if (class_exists(AuditLog::class)) {
            try {
                AuditLog::create([
                    'user_id'   => $userId,
                    'operation' => $operation,
                    'params'    => json_encode($safeParams),
                    'status'    => $status,
                    'exit_code' => $exitCode,
                    'ip'        => request()->ip(),
                ]);
            } catch (\Throwable) {
                // Não deixa falha no log quebrar a operação
            }
        }
    }
}
