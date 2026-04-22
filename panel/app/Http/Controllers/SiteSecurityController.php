<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\CommandService;
use Illuminate\Http\Request;

class SiteSecurityController extends Controller
{
    public function __construct(private CommandService $cmd) {}

    public function update(Request $request, Site $site)
    {
        if (!auth()->user()->canAccessSite($site)) abort(403);

        $validated = $request->validate([
            'ip_mode'      => 'required|in:off,whitelist,blacklist',
            'ips'          => 'nullable|string|max:2000',
            'auth_enabled' => 'boolean',
            'auth_user'    => 'nullable|string|max:64',
            'auth_pass'    => 'nullable|string|max:128',
        ]);

        $rawIps = array_map('trim', explode("\n", $validated['ips'] ?? ''));
        $ips    = array_values(array_filter($rawIps, function (string $ip): bool {
            if ($ip === '') return false;
            // Aceita IP simples ou CIDR (ex: 192.168.1.0/24)
            [$addr] = explode('/', $ip, 2);
            return (bool) filter_var($addr, FILTER_VALIDATE_IP);
        }));

        $existing = $site->security_config ?? [];

        $config = [
            'ip_mode'        => $validated['ip_mode'],
            'ips'            => $ips,
            'auth_enabled'   => (bool) ($validated['auth_enabled'] ?? false),
            'auth_user'      => $validated['auth_user'] ?? '',
            'auth_pass_hash' => $existing['auth_pass_hash'] ?? '',
        ];

        if ($config['auth_enabled'] && !empty($validated['auth_pass'])) {
            $config['auth_pass_hash'] = $this->apr1Md5($validated['auth_pass']);
        }

        if (!$config['auth_enabled']) {
            $config['auth_pass_hash'] = '';
        }

        try {
            // Salva no banco PRIMEIRO — se a coluna não existir, falha aqui antes de mexer no nginx
            $site->update(['security_config' => $config]);

            $this->cmd->run('security.ensure_dirs', []);
            $this->applyAcl($site, $config);
            $this->applyHtpasswd($site, $config);
            $this->ensureNginxInclude($site);
            $this->cmd->runOrFail('nginx.configtest');
            $this->cmd->runOrFail('nginx.reload');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao atualizar segurança do site', [
                'site'      => $site->id,
                'exception' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Erro ao atualizar configurações de segurança.'], 500);
        }

        return response()->json(['message' => 'Segurança atualizada e Nginx recarregado.']);
    }

    private function applyAcl(Site $site, array $config): void
    {
        $dest  = '/etc/nginx/gpanel-security/' . $site->domain . '.conf';
        $lines = ['# GPanel ACL — ' . $site->domain];

        if ($config['ip_mode'] === 'whitelist' && !empty($config['ips'])) {
            foreach ($config['ips'] as $ip) {
                $lines[] = "allow {$ip};";
            }
            $lines[] = 'deny all;';
        } elseif ($config['ip_mode'] === 'blacklist' && !empty($config['ips'])) {
            foreach ($config['ips'] as $ip) {
                $lines[] = "deny {$ip};";
            }
        }

        if ($config['auth_enabled'] && !empty($config['auth_user']) && !empty($config['auth_pass_hash'])) {
            $lines[] = 'auth_basic "Area Restrita";';
            $lines[] = 'auth_basic_user_file /etc/nginx/htpasswd/' . $site->domain . ';';
        }

        $tmp = '/tmp/gpanel-acl-' . $site->id . '-' . time();
        file_put_contents($tmp, implode("\n", $lines) . "\n");
        $this->cmd->run('security.install_acl', ['src' => $tmp, 'dest' => $dest]);
    }

    private function applyHtpasswd(Site $site, array $config): void
    {
        $dest = '/etc/nginx/htpasswd/' . $site->domain;

        if (!$config['auth_enabled'] || empty($config['auth_user']) || empty($config['auth_pass_hash'])) {
            $this->cmd->run('security.remove_htpasswd', ['path' => $dest]);
            return;
        }

        $content = $config['auth_user'] . ':' . $config['auth_pass_hash'] . "\n";
        $tmp     = '/tmp/gpanel-htpasswd-' . $site->id . '-' . time();
        file_put_contents($tmp, $content);
        $this->cmd->run('security.install_htpasswd', ['src' => $tmp, 'dest' => $dest]);
    }

    /**
     * Gera hash APR1-MD5 compatível com nginx/Apache htpasswd.
     * PHP nativo crypt() não suporta $apr1$ — implementação manual necessária.
     */
    private function apr1Md5(string $password): string
    {
        $salt = substr(str_replace(['+', '/', '='], ['.', '/', ''], base64_encode(random_bytes(6))), 0, 8);

        $len  = strlen($password);
        $ctx  = $password . '$apr1$' . $salt;
        $bin  = md5($password . $salt . $password, true);

        for ($i = $len; $i > 0; $i -= 16) {
            $ctx .= substr($bin, 0, min(16, $i));
        }

        for ($i = $len; $i > 0; $i >>= 1) {
            $ctx .= ($i & 1) ? chr(0) : $password[0];
        }

        $bin = md5($ctx, true);

        for ($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $password : $bin;
            if ($i % 3) $new .= $salt;
            if ($i % 7) $new .= $password;
            $new .= ($i & 1) ? $bin : $password;
            $bin = md5($new, true);
        }

        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $to64   = function (int $v, int $n) use ($itoa64): string {
            $out = '';
            while (--$n >= 0) { $out .= $itoa64[$v & 0x3f]; $v >>= 6; }
            return $out;
        };

        $hash  = $to64((ord($bin[ 0]) << 16) | (ord($bin[ 6]) << 8) | ord($bin[12]), 4);
        $hash .= $to64((ord($bin[ 1]) << 16) | (ord($bin[ 7]) << 8) | ord($bin[13]), 4);
        $hash .= $to64((ord($bin[ 2]) << 16) | (ord($bin[ 8]) << 8) | ord($bin[14]), 4);
        $hash .= $to64((ord($bin[ 3]) << 16) | (ord($bin[ 9]) << 8) | ord($bin[15]), 4);
        $hash .= $to64((ord($bin[ 4]) << 16) | (ord($bin[10]) << 8) | ord($bin[ 5]), 4);
        $hash .= $to64(ord($bin[11]), 2);

        return '$apr1$' . $salt . '$' . $hash;
    }

    private function ensureNginxInclude(Site $site): void
    {
        $configPath = $site->nginx_config_path;
        if (!$configPath || !file_exists($configPath)) return;

        $content = file_get_contents($configPath);
        if (str_contains($content, 'gpanel-security')) return;

        $include    = "\n    # GPanel Security\n    include /etc/nginx/gpanel-security/{$site->domain}.conf;\n";
        $lastBrace  = strrpos($content, '}');
        if ($lastBrace !== false) {
            $newContent = substr($content, 0, $lastBrace) . $include . '}';
            $tmp        = '/tmp/gpanel-nginx-sec-' . $site->id . '-' . time();
            file_put_contents($tmp, $newContent);
            $this->cmd->runOrFail('file.install', ['src' => $tmp, 'dest' => $configPath]);
        }
    }
}
