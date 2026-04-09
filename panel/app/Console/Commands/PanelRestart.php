<?php

namespace App\Console\Commands;

use App\Services\CommandService;
use Illuminate\Console\Command;

class PanelRestart extends Command
{
    protected $signature   = 'panel:restart';
    protected $description = 'Reiniciar o painel GPanel (workers + node helper)';

    public function handle(CommandService $cmd): int
    {
        $this->line('Reiniciando GPanel...');

        try {
            $cmd->runOrFail('supervisor.restart', ['program' => 'gpanel-worker:*']);
            $this->line('<fg=green>✓</> Queue Workers reiniciados.');

            $cmd->runOrFail('supervisor.restart', ['program' => 'gpanel-node']);
            $this->line('<fg=green>✓</> Node.js Helper reiniciado.');

            $cmd->runOrFail('nginx.reload');
            $this->line('<fg=green>✓</> Nginx recarregado.');

        } catch (\Throwable $e) {
            $this->error('Erro ao reiniciar: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('GPanel reiniciado com sucesso.');
        return self::SUCCESS;
    }
}
