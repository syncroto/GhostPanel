<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminResetPassword extends Command
{
    protected $signature   = 'admin:reset-password';
    protected $description = 'Resetar senha de um administrador';

    public function handle(): int
    {
        $admins = User::where('role', 'admin')->get(['id', 'name', 'email']);

        if ($admins->isEmpty()) {
            $this->error('Nenhum admin cadastrado.');
            return self::FAILURE;
        }

        $this->info('');
        $this->line('  Admins disponíveis:');
        $this->table(['ID', 'Nome', 'E-mail'], $admins->toArray());

        $id = $this->ask('  ID do admin para resetar a senha');
        $user = User::where('role', 'admin')->find($id);

        if (!$user) {
            $this->error("  Admin ID {$id} não encontrado.");
            return self::FAILURE;
        }

        $this->line("  Resetando senha para: <fg=yellow>{$user->name}</> ({$user->email})");

        $password = $this->secret('  Nova senha (mínimo 12 caracteres)');
        if (strlen($password) < 12) {
            $this->error('  A senha deve ter no mínimo 12 caracteres.');
            return self::FAILURE;
        }

        $confirm = $this->secret('  Confirmar nova senha');
        if ($password !== $confirm) {
            $this->error('  As senhas não coincidem.');
            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);

        $this->info("  <fg=green>✓</> Senha de {$user->name} resetada com sucesso.");
        return self::SUCCESS;
    }
}
