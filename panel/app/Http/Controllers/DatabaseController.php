<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Services\CommandService;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    public function __construct(private CommandService $cmd) {}

    public function index()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito a administradores.');
        }
        $databases = Database::with('site')->orderBy('created_at', 'desc')->get();
        return view('databases.index', compact('databases'));
    }

    /**
     * Store a database created from inside a site's tab (non-admin users).
     * Enforces the per-user db_limit for the given site.
     */
    public function storeBySite(Request $request, \App\Models\Site $site)
    {
        $user = auth()->user();

        if (!$user->canAccessSite($site)) {
            return response()->json(['error' => 'Acesso negado a este site.'], 403);
        }

        if (!$user->isAdmin()) {
            $currentCount = Database::where('site_id', $site->id)->count();
            $limit        = $user->db_limit ?? 3;
            if ($currentCount >= $limit) {
                return response()->json([
                    'error' => "Limite de {$limit} banco(s) por site atingido."
                ], 422);
            }
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
            'username' => ['required', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string', 'min:8'],
            'driver'   => ['required', 'in:mysql,postgresql'],
        ], [
            'name.regex'     => 'Nome do banco: apenas letras, números e _.',
            'username.regex' => 'Usuário: apenas letras, números e _.',
        ]);

        $rootPass = config('gpanel.mysql_root_password', '');

        try {
            if ($validated['driver'] === 'mysql') {
                $this->cmd->runOrFail('mysql.create_db',   ['root_pass' => $rootPass, 'db_name' => $validated['name']]);
                $this->cmd->runOrFail('mysql.create_user', ['root_pass' => $rootPass, 'db_user' => $validated['username'], 'db_pass' => $validated['password']]);
                $this->cmd->runOrFail('mysql.grant',       ['root_pass' => $rootPass, 'db_name' => $validated['name'], 'db_user' => $validated['username']]);
            } else {
                $this->cmd->runOrFail('pg.create_db',   ['name' => $validated['name']]);
                $this->cmd->runOrFail('pg.create_user', ['username' => $validated['username'], 'password' => $validated['password'], 'database' => $validated['name']]);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro ao criar banco: ' . $e->getMessage()], 500);
        }

        try {
            $db = Database::create([
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'driver'   => $validated['driver'],
                'site_id'  => $site->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message'  => 'Banco criado com sucesso.',
            'database' => [
                'id'       => $db->id,
                'name'     => $db->name,
                'username' => $db->username,
                'driver'   => $db->driver,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
            'username' => ['required', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string', 'min:8'],
            'driver'   => ['required', 'in:mysql,postgresql'],
            'site_id'  => ['nullable', 'exists:sites,id'],
        ], [
            'name.regex'     => 'Nome do banco: apenas letras, números e _.',
            'username.regex' => 'Usuário: apenas letras, números e _.',
        ]);

        $rootPass = config('gpanel.mysql_root_password', '');

        try {
            if ($validated['driver'] === 'mysql') {
                $this->cmd->runOrFail('mysql.create_db', [
                    'root_pass' => $rootPass,
                    'db_name'   => $validated['name'],
                ]);
                $this->cmd->runOrFail('mysql.create_user', [
                    'root_pass' => $rootPass,
                    'db_user'   => $validated['username'],
                    'db_pass'   => $validated['password'],
                ]);
                $this->cmd->runOrFail('mysql.grant', [
                    'root_pass' => $rootPass,
                    'db_name'   => $validated['name'],
                    'db_user'   => $validated['username'],
                ]);
            } else {
                $this->cmd->runOrFail('pg.create_db', ['name' => $validated['name']]);
                $this->cmd->runOrFail('pg.create_user', [
                    'username' => $validated['username'],
                    'password' => $validated['password'],
                    'database' => $validated['name'],
                ]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['name' => 'Erro ao criar banco: ' . $e->getMessage()])->withInput();
        }

        try {
            Database::create([
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'driver'   => $validated['driver'],
                'site_id'  => $validated['site_id'] ?: null,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['name' => 'Erro ao salvar no banco: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('databases.index')->with('success', 'Banco de dados criado com sucesso.');
    }

    public function destroy(Database $database)
    {
        $rootPass = config('gpanel.mysql_root_password', '');

        try {
            if ($database->driver === 'mysql') {
                $this->cmd->run('mysql.drop_db',   ['root_pass' => $rootPass, 'db_name' => $database->name]);
                $this->cmd->run('mysql.drop_user', ['root_pass' => $rootPass, 'db_user' => $database->username]);
            } else {
                $this->cmd->run('pg.drop_db',   ['name' => $database->name]);
                $this->cmd->run('pg.drop_user', ['username' => $database->username]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $database->delete();
        return redirect()->route('databases.index')->with('success', 'Banco removido.');
    }

    public function resetPassword(Request $request, Database $database)
    {
        $request->validate(['password' => 'required|string|min:8']);
        $rootPass = config('gpanel.mysql_root_password', '');

        try {
            $this->cmd->runOrFail('mysql.alter_user', [
                'root_pass' => $rootPass,
                'db_user'   => $database->username,
                'db_pass'   => $request->password,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }
}
