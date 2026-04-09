<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PanelUpdate extends Command
{
    protected $signature = 'panel:update
                            {--frontend : Atualiza apenas os assets do frontend (npm build)}
                            {--backend  : Atualiza apenas o backend (git pull + composer)}';

    protected $description = 'Atualizar o GPanel para a última versão do repositório';

    private string $panelDir;

    public function handle(): int
    {
        $this->panelDir = base_path();  // /gpanel/panel

        $onlyFrontend = $this->option('frontend');
        $onlyBackend  = $this->option('backend');

        // Se nenhuma flag for passada, atualiza tudo
        $updateFrontend = !$onlyBackend;
        $updateBackend  = !$onlyFrontend;

        $this->line('');
        $this->line('  <fg=blue>GPanel</> — Atualizando painel...');
        $this->line('  ─────────────────────────────────');

        if ($updateBackend) {
            $this->line('');
            if (!$this->updateBackend()) {
                return self::FAILURE;
            }
        }

        if ($updateFrontend) {
            $this->line('');
            if (!$this->updateFrontend()) {
                return self::FAILURE;
            }
        }

        $this->line('');
        $this->restartWorkers();

        $this->line('');
        $this->info('  ✓ GPanel atualizado com sucesso!');
        $this->line('');

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------------- //

    private function updateBackend(): bool
    {
        $this->line('  <fg=yellow>[Backend]</> Atualizando código...');

        // 1. git pull
        $this->line('  Executando git pull...');
        $gpanelDir = dirname($this->panelDir); // /gpanel
        [$output, $exit] = $this->execIn($gpanelDir, 'git pull origin main 2>&1');

        if ($exit !== 0) {
            $this->error("  git pull falhou:\n{$output}");
            return false;
        }

        // Detecta se houve mudanças reais
        if (str_contains($output, 'Already up to date')) {
            $this->line('  <fg=gray>  Código já está na versão mais recente.');
        } else {
            $this->line("  <fg=green>  ✓ Código atualizado.");
        }

        // 2. composer install (apenas se composer.json/lock mudou)
        if ($this->composerChanged($output)) {
            $this->line('  Atualizando dependências PHP (composer)...');
            [$out, $exit] = $this->exec(
                'composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1'
            );
            if ($exit !== 0) {
                $this->error("  composer install falhou:\n{$out}");
                return false;
            }
            $this->line('  <fg=green>  ✓ Dependências PHP atualizadas.');
        }

        // 3. Migrations (apenas se houver migration nova)
        $this->line('  Verificando migrations...');
        [$out] = $this->exec('php artisan migrate --force --no-interaction 2>&1');
        if (str_contains($out, 'Nothing to migrate')) {
            $this->line('  <fg=gray>  Nenhuma migration nova.');
        } else {
            $this->line('  <fg=green>  ✓ Migrations executadas.');
        }

        // 4. Limpa caches
        $this->line('  Limpando caches...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:clear');
        $this->line('  <fg=green>  ✓ Caches limpos.');

        return true;
    }

    private function updateFrontend(): bool
    {
        $this->line('  <fg=yellow>[Frontend]</> Recompilando assets...');

        // Verifica se package.json existe
        if (!file_exists($this->panelDir . '/package.json')) {
            $this->warn('  package.json não encontrado, pulando build frontend.');
            return true;
        }

        // npm install (apenas se package.json/lock mudou)
        $this->line('  Instalando dependências npm...');
        [$out, $exit] = $this->exec('npm install --silent 2>&1');

        if ($exit !== 0) {
            $this->error("  npm install falhou:\n{$out}");
            return false;
        }

        // npm run build
        $this->line('  Compilando assets (npm run build)...');
        [$out, $exit] = $this->exec('npm run build 2>&1');

        if ($exit !== 0) {
            $this->error("  npm run build falhou:\n{$out}");
            return false;
        }

        $this->line('  <fg=green>  ✓ Assets frontend compilados.');
        return true;
    }

    private function restartWorkers(): void
    {
        $this->line('  <fg=yellow>[Workers]</> Reiniciando...');

        // Graceful restart: aguarda jobs em andamento terminarem
        Artisan::call('queue:restart');
        $this->line('  <fg=green>  ✓ Queue workers reiniciados graciosamente.');

        // Supervisor
        [, $exit] = $this->exec('supervisorctl restart gpanel-worker:* 2>&1');
        if ($exit === 0) {
            $this->line('  <fg=green>  ✓ Supervisor workers reiniciados.');
        }
    }

    // ---------------------------------------------------------------------- //

    /**
     * Detecta se composer.json ou composer.lock foi alterado no git pull.
     */
    private function composerChanged(string $gitOutput): bool
    {
        return str_contains($gitOutput, 'composer.json')
            || str_contains($gitOutput, 'composer.lock');
    }

    /**
     * Executa um comando no diretório do painel.
     * Retorna [output, exit_code].
     */
    private function exec(string $command): array
    {
        return $this->execIn($this->panelDir, $command);
    }

    private function execIn(string $dir, string $command): array
    {
        $output   = [];
        $exitCode = 0;
        exec("cd {$dir} && {$command}", $output, $exitCode);
        return [implode("\n", $output), $exitCode];
    }
}
