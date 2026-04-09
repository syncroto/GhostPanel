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

class CreateSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(private Site $site) {}

    public function handle(CommandService $cmd): void
    {
        try {
            $this->createDirectories($cmd);
            $this->createNginxConfig($cmd);
            $this->setupStack($cmd);
            $this->reloadNginx($cmd);

            $this->site->update(['status' => 'running']);
            Log::info("Site criado com sucesso: {$this->site->domain}");

        } catch (\Throwable $e) {
            $this->site->update(['status' => 'error']);
            Log::error("Erro ao criar site {$this->site->domain}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function createDirectories(CommandService $cmd): void
    {
        $sitePath = "/var/www/sites/{$this->site->domain}";

        $cmd->runOrFail('site.mkdir', ['path' => $sitePath . '/public']);
        $cmd->runOrFail('site.mkdir', ['path' => $sitePath . '/logs']);

        // Cria arquivo index padrão apenas para PHP e HTML5; nodejs/python/wordpress ficam com a pasta vazia
        if ($this->site->type === 'html5') {
            file_put_contents("{$sitePath}/public/index.html", $this->getDefaultIndexHtml());
        } elseif ($this->site->type === 'php') {
            file_put_contents("{$sitePath}/public/index.php", $this->getDefaultIndex());
        }

        $cmd->runOrFail('site.chown', [
            'user' => 'www-data',
            'path' => $sitePath,
        ]);
    }

    private function createNginxConfig(CommandService $cmd): void
    {
        $configPath = "/etc/nginx/sites-available/{$this->site->domain}";
        $config     = $this->generateNginxConfig();

        // Escreve em /tmp (www-data tem acesso), depois move com sudo para /etc/nginx/
        $tmpFile = '/tmp/gpanel-nginx-' . $this->site->id . '-' . time();
        file_put_contents($tmpFile, $config);
        $cmd->runOrFail('file.install', ['src' => $tmpFile, 'dest' => $configPath]);

        $this->site->update(['nginx_config_path' => $configPath]);

        $cmd->runOrFail('site.symlink_nginx', ['domain' => $this->site->domain]);
    }

    private function setupStack(CommandService $cmd): void
    {
        match ($this->site->type) {
            'php'       => $this->setupPhp($cmd),
            'nodejs'    => $this->setupNodejs($cmd),
            'python'    => $this->setupPython($cmd),
            'wordpress' => $this->setupWordPress($cmd),
            'html5'     => null, // static site, nada a configurar
            default     => null,
        };
    }

    private function setupPhp(CommandService $cmd): void
    {
        // PHP-FPM já gerencia via pool padrão por enquanto
        // Pool dedicado pode ser adicionado na Fase 2
    }

    private function setupNodejs(CommandService $cmd): void
    {
        $sitePath = "/var/www/sites/{$this->site->domain}";
        $program  = "site-{$this->site->id}";

        $port = $this->site->port ?? 3000;

        $supervisorConfig = "[program:{$program}]\n" .
            "command=node {$sitePath}/server.js\n" .
            "directory={$sitePath}\n" .
            "user=www-data\n" .
            "autostart=true\nautorestart=true\n" .
            "environment=PORT=\"{$port}\"\n" .
            "stdout_logfile={$sitePath}/logs/app.log\n" .
            "stderr_logfile={$sitePath}/logs/app_error.log\n";

        // Escreve em /tmp depois move com sudo para /etc/supervisor/
        $tmpConf = '/tmp/gpanel-supervisor-' . $this->site->id . '.conf';
        file_put_contents($tmpConf, $supervisorConfig);
        $cmd->runOrFail('file.install', ['src' => $tmpConf, 'dest' => "/etc/supervisor/conf.d/{$program}.conf"]);

        $this->site->update(['supervisor_program' => $program]);
        $cmd->runOrFail('supervisor.reread');
        $cmd->runOrFail('supervisor.start', ['program' => $program]);
    }

    private function setupPython(CommandService $cmd): void
    {
        $sitePath = "/var/www/sites/{$this->site->domain}";
        $program  = "site-{$this->site->id}";

        $cmd->runOrFail('python.create_venv', ['path' => $sitePath]);

        $supervisorConfig = "[program:{$program}]\n" .
            "command={$sitePath}/venv/bin/gunicorn --workers 2 --bind unix:{$sitePath}/app.sock app:app\n" .
            "directory={$sitePath}\nuser=www-data\n" .
            "autostart=true\nautorestart=true\n" .
            "stdout_logfile={$sitePath}/logs/app.log\n" .
            "stderr_logfile={$sitePath}/logs/app_error.log\n";

        $tmpConf = '/tmp/gpanel-supervisor-' . $this->site->id . '.conf';
        file_put_contents($tmpConf, $supervisorConfig);
        $cmd->runOrFail('file.install', ['src' => $tmpConf, 'dest' => "/etc/supervisor/conf.d/{$program}.conf"]);

        $this->site->update(['supervisor_program' => $program]);
        $cmd->runOrFail('supervisor.reread');
        $cmd->runOrFail('supervisor.start', ['program' => $program]);
    }

    private function setupWordPress(CommandService $cmd): void
    {
        $sitePath = "/var/www/sites/{$this->site->domain}/public";
        $cmd->runOrFail('wp.install', ['path' => $sitePath]);
    }

    private function reloadNginx(CommandService $cmd): void
    {
        $cmd->runOrFail('nginx.configtest');
        $cmd->runOrFail('nginx.reload');
    }

    private function generateNginxConfig(): string
    {
        $domain   = $this->site->domain;
        $rootPath = $this->site->root_path;
        $sitePath = "/var/www/sites/{$domain}";

        return match ($this->site->type) {
            'nodejs', 'python' => $this->nginxProxyConfig($domain, $sitePath),
            'html5'            => $this->nginxStaticConfig($domain, $rootPath),
            default            => $this->nginxPhpConfig($domain, $rootPath, $this->site->php_version),
        };
    }

    private function nginxPhpConfig(string $domain, string $rootPath, string $phpVersion): string
    {
        return <<<NGINX
server {
    listen 80;
    server_name {$domain} www.{$domain};

    root {$rootPath};
    index index.php index.html;

    access_log /var/www/sites/{$domain}/logs/access.log;
    error_log  /var/www/sites/{$domain}/logs/error.log;

    client_max_body_size 100M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php{$phpVersion}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.env { deny all; }
    location ~ /\.git { deny all; }
}
NGINX;
    }

    private function nginxProxyConfig(string $domain, string $sitePath): string
    {
        $port = $this->site->port ?? 3000;
        $upstreamTarget = $this->site->type === 'nodejs'
            ? "http://127.0.0.1:{$port}"
            : "unix:{$sitePath}/app.sock";

        return <<<NGINX
server {
    listen 80;
    server_name {$domain} www.{$domain};

    access_log /var/www/sites/{$domain}/logs/access.log;
    error_log  /var/www/sites/{$domain}/logs/error.log;

    client_max_body_size 100M;

    location / {
        proxy_pass {$upstreamTarget};
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
        proxy_cache_bypass \$http_upgrade;
    }
}
NGINX;
    }

    private function nginxStaticConfig(string $domain, string $rootPath): string
    {
        return <<<NGINX
server {
    listen 80;
    server_name {$domain} www.{$domain};

    root {$rootPath};
    index index.html index.htm;

    access_log /var/www/sites/{$domain}/logs/access.log;
    error_log  /var/www/sites/{$domain}/logs/error.log;

    client_max_body_size 100M;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.env { deny all; }
    location ~ /\.git { deny all; }
}
NGINX;
    }

    private function getDefaultIndex(): string
    {
        return <<<'PHP'
<?php
echo '<h1>Ok</h1>';
PHP;
    }

    private function getDefaultIndexHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ok</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f9fafb; }
        .card { text-align: center; padding: 2rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { color: #111827; margin-bottom: .5rem; }
        p { color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Ok</h1>
    </div>
</body>
</html>
HTML;
    }
}
