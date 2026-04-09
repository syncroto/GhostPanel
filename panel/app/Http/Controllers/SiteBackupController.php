<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteBackup;
use App\Services\CommandService;
use Illuminate\Http\Request;

class SiteBackupController extends Controller
{
    private string $backupBase;

    public function __construct(private CommandService $cmd)
    {
        $this->backupBase = config('gpanel.gpanel_dir', '/gpanel') . '/storage/backups/sites';
    }

    public function store(Request $request, Site $site)
    {
        if (!auth()->user()->canAccessSite($site)) abort(403);

        $ts        = now()->format('Y-m-d_H-i-s');
        $filename  = "{$site->domain}_{$ts}.tar.gz";
        $backupDir = "{$this->backupBase}/{$site->id}";
        $outputPath = "{$backupDir}/{$filename}";

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        try {
            $this->cmd->runOrFail('backup.tar_site', [
                'output_path' => $outputPath,
                'site_path'   => "/var/www/sites/{$site->domain}",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro ao criar backup: ' . $e->getMessage()], 500);
        }

        $size = file_exists($outputPath) ? filesize($outputPath) : 0;

        $backup = SiteBackup::create([
            'site_id'    => $site->id,
            'filename'   => $filename,
            'size'       => $size,
            'expires_at' => now()->addDays(3),
        ]);

        return response()->json([
            'message' => 'Backup criado com sucesso.',
            'backup'  => [
                'id'             => $backup->id,
                'filename'       => $backup->filename,
                'formatted_size' => $backup->formatted_size,
                'created_at'     => $backup->created_at->format('d/m/Y H:i'),
                'expires_at'     => $backup->expires_at->format('d/m/Y H:i'),
                'expires_in'     => $backup->expires_at->diffForHumans(),
            ],
        ]);
    }

    public function download(Site $site, SiteBackup $backup)
    {
        if (!auth()->user()->canAccessSite($site)) abort(403);
        if ($backup->site_id !== $site->id) abort(404);

        $path = "{$this->backupBase}/{$site->id}/{$backup->filename}";

        if (!file_exists($path)) {
            abort(404, 'Arquivo de backup não encontrado.');
        }

        return response()->download($path);
    }

    public function destroy(Site $site, SiteBackup $backup)
    {
        if (!auth()->user()->canAccessSite($site)) abort(403);
        if ($backup->site_id !== $site->id) abort(404);

        $path = "{$this->backupBase}/{$site->id}/{$backup->filename}";
        if (file_exists($path)) {
            unlink($path);
        }

        $backup->delete();

        return response()->json(['message' => 'Backup removido.']);
    }
}
