<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminCreate extends Command
{
    protected $signature   = 'admin:create';
    protected $description = 'Criar novo usuário administrador';

    public function handle(): int
    {
        $this->info('');
        $this->line('  <fg=blue>GPanel</> — Criar Admin');
        $this->line('  ─────────────────────────');
        $this->info('');

        $name = $this->ask('  Nome completo');

        $email = $this->ask('  E-mail');
        if (User::where('email', $email)->exists()) {
            $this->error("  Já existe um usuário com e-mail: {$email}");
            return self::FAILURE;
        }

        $password = $this->secret('  Senha (mínimo 12 caracteres)');
        if (strlen($password) < 12) {
            $this->error('  A senha deve ter no mínimo 12 caracteres.');
            return self::FAILURE;
        }

        $confirm = $this->secret('  Confirmar senha');
        if ($password !== $confirm) {
            $this->error('  As senhas não coincidem.');
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'admin',
        ]);

        $this->info('');
        $this->line("  <fg=green>✓</> Admin criado com sucesso!");
        $this->table(['ID', 'Nome', 'E-mail', 'Role'], [
            [$user->id, $user->name, $user->email, $user->role]
        ]);

        return self::SUCCESS;
    }
}
