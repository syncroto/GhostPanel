<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminList extends Command
{
    protected $signature   = 'admin:list';
    protected $description = 'Listar todos os administradores';

    public function handle(): int
    {
        $admins = User::where('role', 'admin')
            ->orderBy('created_at')
            ->get(['id', 'name', 'email', 'created_at']);

        if ($admins->isEmpty()) {
            $this->warn('Nenhum admin cadastrado.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->line('  <fg=blue>GPanel</> — Administradores');
        $this->line('');
        $this->table(
            ['ID', 'Nome', 'E-mail', 'Criado em'],
            $admins->map(fn($a) => [
                $a->id,
                $a->name,
                $a->email,
                $a->created_at->format('d/m/Y H:i'),
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
