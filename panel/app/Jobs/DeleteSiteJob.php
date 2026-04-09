<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\CommandService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1;

    public function __construct(private Site $site) {}

    public function handle(CommandService $cmd): void
    {
        $domain   = $this->site->domain;
        $sitePath = "/var/www/sites/{$domain}";

        // 1. Remove nginx symlink from sites-enabled
        try {
            $cmd->run('site.unlink_nginx', ['domain' => $domain]);
        } catch (\Throwable $e) {
            Log::warning("DeleteSite [{$domain}]: erro ao remover symlink nginx: {$e->getMessage()}");
        }

        // 2. Remove nginx config from sites-available
        try {
            $cmd->run('site.remove_nginx_conf', ['domain' => $domain]);
        } catch (\Throwable $e) {
            Log::warning("DeleteSite [{$domain}]: erro ao remover nginx conf: {$e->getMessage()}");
        }

        // 3. Stop and remove supervisor program (Node.js / Python)
        if ($this->site->supervisor_program) {
            $program = $this->site->supervisor_program;
            try {
                $cmd->run('supervisor.stop', ['program' => $program]);
            } catch (\Throwable $e) {
                Log::warning("DeleteSite [{$domain}]: erro ao parar supervisor: {$e->getMessage()}");
            }
            try {
                $cmd->run('supervisor.remove_conf', ['program' => $program]);
                $cmd->run('supervisor.reread');
            } catch (\Throwable $e) {
                Log::warning("DeleteSite [{$domain}]: erro ao remover conf supervisor: {$e->getMessage()}");
            }
        }

        // 4. Remove cron file
        $cronSlug = 'gpanel-' . str_replace('.', '-', $domain);
        try {
            $cmd->run('cron.remove', ['path' => "/etc/cron.d/{$cronSlug}"]);
        } catch (\Throwable $e) {
            Log::warning("DeleteSite [{$domain}]: erro ao remover cron: {$e->getMessage()}");
        }

        // 5. Remove nginx security files
        try {
            $cmd->run('security.remove_acl',      ['path' => "/etc/nginx/gpanel-security/{$domain}.conf"]);
            $cmd->run('security.remove_htpasswd', ['path' => "/etc/nginx/htpasswd/{$domain}"]);
        } catch (\Throwable $e) {
            Log::warning("DeleteSite [{$domain}]: erro ao remover arquivos de segurança: {$e->getMessage()}");
        }

        // 6. Remove site files
        try {
            $cmd->run('site.remove', ['path' => $sitePath]);
        } catch (\Throwable $e) {
            Log::warning("DeleteSite [{$domain}]: erro ao remover arquivos do site: {$e->getMessage()}");
        }

        // 7. Reload nginx
        try {
            $cmd->run('nginx.reload');
        } catch (\Throwable $e) {
            Log::warning("DeleteSite [{$domain}]: erro ao recarregar nginx: {$e->getMessage()}");
        }

        // 8. Delete database record (always runs even if steps above fail)
        $this->site->delete();

        Log::info("Site removido com sucesso: {$domain}");
    }
}
