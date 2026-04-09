<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PanelUninstall extends Command
{
    protected $signature   = 'panel:uninstall';
    protected $description = 'Desinstalar o GPanel (mantém sites, nginx, mysql, etc.)';

    public function handle(): int
    {
        $this->line('');
        $this->warn('  ATENÇÃO: Esta ação removerá o GPanel completamente!');
        $this->line('  - O código do painel será removido de /gpanel/');
        $this->line('  - Os sites hospedados NÃO serão removidos.');
        $this->line('  - Nginx, PHP, MySQL, Redis NÃO serão removidos.');
        $this->line('');

        $confirm = $this->ask('  Para confirmar, digite REMOVER GPANEL');

        if ($confirm !== 'REMOVER GPANEL') {
            $this->info('Desinstalação cancelada.');
            return self::SUCCESS;
        }

        $this->line('Desinstalando GPanel...');

        // Para os serviços do supervisor
        @shell_exec('supervisorctl stop gpanel-worker:* 2>/dev/null');
        @shell_exec('supervisorctl stop gpanel-node 2>/dev/null');
        @shell_exec('rm -f /etc/supervisor/conf.d/gpanel-worker.conf');
        @shell_exec('rm -f /etc/supervisor/conf.d/gpanel-node.conf');
        @shell_exec('supervisorctl reread 2>/dev/null');
        @shell_exec('supervisorctl update 2>/dev/null');

        // Remove vhost do painel
        @shell_exec('rm -f /etc/nginx/sites-enabled/gpanel');
        @shell_exec('rm -f /etc/nginx/sites-available/gpanel');
        @shell_exec('nginx -t && systemctl reload nginx 2>/dev/null');

        // Remove CLI
        @shell_exec('rm -f /usr/local/bin/gpanel');

        // Remove código do painel
        $gpanelDir = base_path('/../..');
        @shell_exec("rm -rf {$gpanelDir}/panel {$gpanelDir}/node-helper {$gpanelDir}/agent");

        $this->info('GPanel desinstalado com sucesso.');
        $this->line('Os seus sites e serviços continuam funcionando normalmente.');

        return self::SUCCESS;
    }
}
