<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('allowedSites')->orderBy('created_at', 'desc')->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'in:admin,user'],
            'db_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'site_ids' => ['nullable', 'array'],
            'site_ids.*' => ['integer', 'exists:sites,id'],
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'db_limit'  => $validated['db_limit'] ?? 3,
            'is_active' => true,
        ]);

        if ($validated['role'] === 'user' && !empty($validated['site_ids'])) {
            $user->allowedSites()->sync($validated['site_ids']);
        }

        return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password'   => ['nullable', 'string', 'min:8'],
            'role'       => ['required', 'in:admin,user'],
            'is_active'  => ['boolean'],
            'db_limit'   => ['nullable', 'integer', 'min:1', 'max:50'],
            'site_ids'   => ['nullable', 'array'],
            'site_ids.*' => ['integer', 'exists:sites,id'],
        ]);

        $data = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'is_active' => $request->boolean('is_active'),
            'db_limit'  => $validated['db_limit'] ?? 3,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        if ($validated['role'] === 'user') {
            $user->allowedSites()->sync($validated['site_ids'] ?? []);
        } else {
            $user->allowedSites()->detach();
        }

        return redirect()->route('users.index')->with('success', 'Usuário atualizado.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Você não pode remover sua própria conta.']);
        }

        $user->allowedSites()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário removido.');
    }
}
