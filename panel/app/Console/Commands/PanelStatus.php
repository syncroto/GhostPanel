<?php

namespace App\Console\Commands;

use App\Services\CommandService;
use Illuminate\Console\Command;

class PanelStatus extends Command
{
    protected $signature   = 'panel:status';
    protected $description = 'Exibir status de todos os serviços do GPanel';

    private array $services = [
        'nginx'          => 'Nginx',
        'php8.2-fpm'     => 'PHP-FPM 8.2',
        'mysql'          => 'MySQL',
        'postgresql'     => 'PostgreSQL',
        'redis-server'   => 'Redis',
        'supervisor'     => 'Supervisor',
    ];

    private array $supervisorPrograms = [
        'gpanel-worker' => 'Queue Worker',
        'gpanel-node'   => 'Node.js Helper',
    ];

    public function handle(CommandService $cmd): int
    {
        $this->line('');
        $this->line('  <fg=blue>GPanel</> — Status dos Serviços');
        $this->line('  ────────────────────────────────');
        $this->line('');

        // Serviços do sistema
        $this->line('  <fg=yellow>Serviços do sistema:</>');
        foreach ($this->services as $name => $label) {
            $this->printServiceStatus($cmd, $name, $label);
        }

        $this->line('');
        $this->line('  <fg=yellow>Processos do GPanel (Supervisor):</>');
        foreach ($this->supervisorPrograms as $program => $label) {
            $this->printSupervisorStatus($cmd, $program, $label);
        }

        // Sites
        $siteCount = \App\Models\Site::count();
        $running   = \App\Models\Site::where('status', 'running')->count();

        $this->line('');
        $this->line('  <fg=yellow>Sites:</>');
        $this->line("    Total: {$siteCount} | Ativos: <fg=green>{$running}</> | Parados: <fg=red>" . ($siteCount - $running) . '</>' );

        // Recursos
        $this->line('');
        $this->line('  <fg=yellow>Recursos do servidor:</>');
        $load = sys_getloadavg();
        $this->line('    Load average: ' . implode(', ', array_map(fn($l) => number_format($l, 2), $load)));
        $this->line('    Uptime: ' . trim(shell_exec('uptime -p 2>/dev/null') ?: 'N/A'));

        $mem = $this->getMemInfo();
        $this->line("    RAM: {$mem['used']}MB usados / {$mem['total']}MB total");

        $this->line('');

        return self::SUCCESS;
    }

    private function printServiceStatus(CommandService $cmd, string $name, string $label): void
    {
        try {
            $result = $cmd->run('service.status', ['service' => $name]);
            $running = str_contains($result['stdout'], 'active (running)');
            $status  = $running
                ? '<fg=green>● ATIVO</>'
                : '<fg=red>○ PARADO</>';
        } catch (\Throwable) {
            $status = '<fg=gray>? N/A</>';
        }

        $this->line(sprintf('    %-20s %s', $label, $status));
    }

    private function printSupervisorStatus(CommandService $cmd, string $program, string $label): void
    {
        try {
            $result  = $cmd->run('supervisor.status', ['program' => $program]);
            $running = str_contains($result['stdout'], 'RUNNING');
            $status  = $running
                ? '<fg=green>● RUNNING</>'
                : '<fg=red>○ STOPPED</>';
        } catch (\Throwable) {
            $status = '<fg=gray>? N/A</>';
        }

        $this->line(sprintf('    %-20s %s', $label, $status));
    }

    private function getMemInfo(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo') ?: '';
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);

        $totalMb = isset($total[1]) ? (int)($total[1] / 1024) : 0;
        $availMb = isset($avail[1]) ? (int)($avail[1] / 1024) : 0;

        return [
            'total' => $totalMb,
            'used'  => $totalMb - $availMb,
            'avail' => $availMb,
        ];
    }
}
