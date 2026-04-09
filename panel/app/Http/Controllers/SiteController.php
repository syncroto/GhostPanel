<?php

namespace App\Http\Controllers;

use App\Jobs\CreateSiteJob;
use App\Jobs\DeleteSiteJob;
use App\Models\Site;
use App\Services\CommandService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function __construct(private CommandService $cmd) {}

    public function index()
    {
        $user  = auth()->user();
        $sites = $user->isAdmin()
            ? Site::orderBy('created_at', 'desc')->get()
            : $user->allowedSites()->orderBy('created_at', 'desc')->get();

        return view('sites.index', compact('sites'));
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) abort(403);
        return view('sites.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $validated = $request->validate([
            'domain'      => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9.-]+$/', Rule::unique('sites', 'domain')],
            'type'        => ['required', Rule::in(['php', 'nodejs', 'python', 'wordpress', 'html5'])],
            'php_version' => ['required_if:type,php,wordpress', Rule::in(['8.1', '8.2', '8.3'])],
            'node_version'=> ['required_if:type,nodejs', Rule::in(['18', '20', '22'])],
            'port'        => ['nullable', 'integer', 'min:1024', 'max:65535'],
            'root_path'   => ['nullable', 'string', 'max:500'],
        ], [
            'domain.required' => 'O domínio é obrigatório.',
            'domain.regex'    => 'O domínio contém caracteres inválidos.',
            'domain.unique'   => 'Este domínio já está cadastrado.',
            'type.required'   => 'Selecione o tipo de site.',
            'type.in'         => 'Tipo de site inválido.',
            'port.min'        => 'A porta deve ser maior que 1024.',
            'port.max'        => 'A porta deve ser menor que 65535.',
        ]);

        // Verifica conflito de porta
        if (!empty($validated['port'])) {
            $portInUse = Site::where('port', $validated['port'])->exists();
            if ($portInUse) {
                return back()->withErrors(['port' => "A porta {$validated['port']} já está em uso por outro site."])->withInput();
            }
        }

        $rootPath = $validated['root_path']
            ?? '/var/www/sites/' . $validated['domain'] . '/public';

        $site = Site::create([
            'domain'       => $validated['domain'],
            'type'         => $validated['type'],
            'php_version'  => $validated['php_version'] ?? '8.2',
            'node_version' => $validated['node_version'] ?? null,
            'port'         => $validated['port'] ?? null,
            'root_path'    => $rootPath,
            'status'       => 'creating',
            'user_id'      => auth()->id(),
        ]);

        CreateSiteJob::dispatch($site);

        return redirect()->route('sites.show', $site->domain)
            ->with('info', 'Site sendo criado em background. Aguarde...');
    }

    public function show(Site $site)
    {
        if (!auth()->user()->canAccessSite($site)) {
            abort(403);
        }

        $site->load('databases');

        try {
            $cronJobs = $site->cronJobs()->get()->toArray();
        } catch (\Exception $e) {
            $cronJobs = [];
        }

        $securityConfig = $site->security_config ?? [];

        $siteBackups = \App\Models\SiteBackup::where('site_id', $site->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($b) => [
                'id'             => $b->id,
                'filename'       => $b->filename,
                'formatted_size' => $b->formatted_size,
                'created_at'     => $b->created_at->format('d/m/Y H:i'),
                'expires_at'     => $b->expires_at->format('d/m/Y H:i'),
                'expires_in'     => $b->expires_at->diffForHumans(),
            ])
            ->toArray();

        return view('sites.show', compact('site', 'cronJobs', 'securityConfig', 'siteBackups'));
    }

    public function destroy(Site $site)
    {
        try {
            DeleteSiteJob::dispatchSync($site);
        } catch (\Throwable $e) {
            return redirect()->route('sites.index')
                ->with('error', 'Erro ao remover site: ' . $e->getMessage());
        }

        return redirect()->route('sites.index')
            ->with('success', 'Site removido com sucesso.');
    }

    // ---------------------------------------------------------------------- //
    //  AJAX actions
    // ---------------------------------------------------------------------- //

    public function restart(Site $site)
    {
        try {
            match ($site->type) {
                'php', 'wordpress' => $this->cmd->runOrFail('phpfpm.reload', ['version' => $site->php_version]),
                'nodejs', 'python' => $this->cmd->runOrFail('supervisor.restart', ['program' => "site-{$site->id}"]),
                default            => null,
            };
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Site reiniciado.']);
    }

    public function toggleSsl(Site $site)
    {
        try {
            if ($site->ssl_enabled) {
                $this->cmd->runOrFail('ssl.revoke', ['domain' => $site->domain]);
                $site->update(['ssl_enabled' => false]);
                $message = 'SSL removido.';
            } else {
                $this->cmd->runOrFail('ssl.obtain', [
                    'domain' => $site->domain,
                    'email'  => auth()->user()->email,
                ]);
                $site->update(['ssl_enabled' => true]);
                $message = 'SSL ativado com sucesso!';
            }
            $this->cmd->run('nginx.reload');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => $message]);
    }

    // Verifica se uma porta está em uso (AJAX)
    public function checkPort(Request $request)
    {
        $port = (int) $request->query('port');
        if ($port < 1024 || $port > 65535) {
            return response()->json(['available' => false, 'reason' => 'Porta inválida.']);
        }

        $dbInUse = Site::where('port', $port)->exists();
        if ($dbInUse) {
            return response()->json(['available' => false, 'reason' => 'Porta já usada por outro site.']);
        }

        // Verifica se a porta está aberta no sistema (sem shell_exec — pode estar desabilitado pelo hardening)
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        $sysInUse = is_resource($connection);
        if ($connection) {
            fclose($connection);
        }

        return response()->json([
            'available' => !$sysInUse,
            'reason'    => $sysInUse ? 'Porta em uso por outro processo.' : null,
        ]);
    }

    // ---------------------------------------------------------------------- //
    //  Vhost editor
    // ---------------------------------------------------------------------- //

    public function vhost(Site $site)
    {
        $content = '';
        if ($site->nginx_config_path && file_exists($site->nginx_config_path)) {
            $content = file_get_contents($site->nginx_config_path);
        }
        return view('sites.vhost', compact('site', 'content'));
    }

    // API JSON para o editor inline na aba Nginx
    public function vhostJson(Site $site)
    {
        $content = '';
        if ($site->nginx_config_path && file_exists($site->nginx_config_path)) {
            $content = file_get_contents($site->nginx_config_path);
        }
        return response()->json([
            'content' => $content,
            'path'    => $site->nginx_config_path,
            'exists'  => (bool) ($site->nginx_config_path && file_exists($site->nginx_config_path)),
        ]);
    }

    public function vhostSave(Request $request, Site $site)
    {
        $request->validate(['content' => 'required|string']);

        if (!$site->nginx_config_path) {
            return back()->withErrors(['content' => 'Caminho Nginx não configurado para este site.']);
        }

        $tmpFile = '/tmp/gpanel-vhost-' . $site->id . '-' . time();
        file_put_contents($tmpFile, $request->content);

        try {
            $this->cmd->runOrFail('nginx.install_vhost', [
                'src'  => $tmpFile,
                'dest' => $site->nginx_config_path,
            ]);
            $this->cmd->runOrFail('nginx.configtest');
            $this->cmd->run('nginx.reload');
        } catch (\Throwable $e) {
            @unlink($tmpFile);
            return back()->withErrors(['content' => 'Erro ao salvar: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Configuração Nginx salva e recarregada.');
    }

    // API JSON para salvar vhost inline
    public function vhostSaveJson(Request $request, Site $site)
    {
        $request->validate(['content' => 'required|string']);

        if (!$site->nginx_config_path) {
            return response()->json(['error' => 'Caminho Nginx não configurado para este site.'], 422);
        }

        $tmpFile = '/tmp/gpanel-vhost-' . $site->id . '-' . time();
        file_put_contents($tmpFile, $request->content);

        try {
            $this->cmd->runOrFail('nginx.install_vhost', [
                'src'  => $tmpFile,
                'dest' => $site->nginx_config_path,
            ]);
            $this->cmd->runOrFail('nginx.configtest');
            $this->cmd->run('nginx.reload');
        } catch (\Throwable $e) {
            @unlink($tmpFile);
            return response()->json(['error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Configuração Nginx salva e recarregada.']);
    }

    // ---------------------------------------------------------------------- //
    //  Logs
    // ---------------------------------------------------------------------- //

    public function logs(Site $site)
    {
        return view('sites.logs', compact('site'));
    }
}
