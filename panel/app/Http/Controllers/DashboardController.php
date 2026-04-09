<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Database;
use App\Services\CommandService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(private CommandService $cmd) {}

    public function index()
    {
        $stats = Cache::remember('dashboard_stats', 15, function () {
            return [
                'sites_total'    => Site::count(),
                'sites_running'  => Site::where('status', 'running')->count(),
                'sites_stopped'  => Site::where('status', 'stopped')->count(),
                'databases_total'=> Database::count(),
                'disk'           => $this->getDiskUsage(),
                'memory'         => $this->getMemoryUsage(),
                'uptime'         => $this->getUptime(),
                'load'           => $this->getLoadAverage(),
            ];
        });

        $services = Cache::remember('services_status', 10, function () {
            return $this->getServicesStatus();
        });

        $user        = auth()->user();
        $recentSites = $user->isAdmin()
            ? Site::latest()->limit(5)->get()
            : $user->allowedSites()->latest()->limit(5)->get();

        return view('dashboard.index', compact('stats', 'services', 'recentSites'));
    }

    // ---------------------------------------------------------------------- //

    private function getDiskUsage(): array
    {
        try {
            $result = $this->cmd->run('diag.disk_usage');
            $lines  = explode("\n", trim($result['stdout']));
            // Pega linha do / (raiz)
            foreach ($lines as $line) {
                if (preg_match('/(\d+)%\s+\/$/', $line, $m)) {
                    return ['percent' => (int) $m[1], 'raw' => $line];
                }
            }
        } catch (\Throwable) {}

        return ['percent' => 0, 'raw' => 'N/A'];
    }

    private function getMemoryUsage(): array
    {
        try {
            $result = $this->cmd->run('diag.memory');
            // Parses: Mem:   2048   1024    512
            if (preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $result['stdout'], $m)) {
                $total   = (int) $m[1];
                $used    = (int) $m[2];
                $percent = $total > 0 ? round(($used / $total) * 100) : 0;
                return [
                    'total_mb'   => $total,
                    'used_mb'    => $used,
                    'free_mb'    => (int) $m[3],
                    'percent'    => $percent,
                ];
            }
        } catch (\Throwable) {}

        return ['total_mb' => 0, 'used_mb' => 0, 'free_mb' => 0, 'percent' => 0];
    }

    private function getUptime(): string
    {
        try {
            $result = $this->cmd->run('diag.uptime');
            if (preg_match('/up\s+(.+?),\s+\d+\s+user/', $result['stdout'], $m)) {
                return trim($m[1]);
            }
            return trim($result['stdout']);
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function getLoadAverage(): string
    {
        return sys_getloadavg()
            ? implode(', ', array_map(fn($l) => number_format($l, 2), sys_getloadavg()))
            : 'N/A';
    }

    private function getServicesStatus(): array
    {
        $services = [
            'nginx'      => ['label' => 'Nginx',      'icon' => 'server'],
            'mysql'      => ['label' => 'MySQL',       'icon' => 'database'],
            'php8.2-fpm' => ['label' => 'PHP-FPM 8.2','icon' => 'code'],
            'redis'      => ['label' => 'Redis',       'icon' => 'lightning-bolt'],
        ];

        foreach ($services as $name => &$info) {
            try {
                $result = $this->cmd->run('service.status', ['service' => $name]);
                $info['status'] = str_contains($result['stdout'], 'active (running)')
                    ? 'running'
                    : 'stopped';
            } catch (\Throwable) {
                $info['status'] = 'unknown';
            }
        }

        return $services;
    }
}
