<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SetupController extends Controller
{
    /**
     * Exibe o wizard de setup inicial.
     * Só é acessível enquanto não houver nenhum admin no banco.
     */
    public function index()
    {
        return view('setup.index');
    }

    /**
     * Processa o formulário de setup inicial.
     * Cria o primeiro usuário admin e marca o setup como concluído.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'min:2', 'max:100'],
            'email'           => ['required', 'email', 'max:150'],
            'password'        => ['required', 'string', 'min:12', 'confirmed'],
            'server_name'     => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'name.required'        => 'O nome é obrigatório.',
            'email.required'       => 'O e-mail é obrigatório.',
            'email.email'          => 'Informe um e-mail válido.',
            'password.required'    => 'A senha é obrigatória.',
            'password.min'         => 'A senha deve ter no mínimo 12 caracteres.',
            'password.confirmed'   => 'As senhas não coincidem.',
            'server_name.required' => 'O nome do servidor é obrigatório.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Cria o usuário admin
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'admin',
        ]);

        // Salva nome do servidor nas configurações do sistema
        config(['app.server_name' => $request->server_name]);

        // Faz login automático após setup
        auth()->login($user);
        session()->regenerate();

        return redirect()->route('dashboard')->with(
            'success',
            'GPanel configurado com sucesso! Bem-vindo, ' . $user->name . '.'
        );
    }
}
