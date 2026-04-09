<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Services\CommandService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    private string $backupDir;

    public function __construct(private CommandService $cmd)
    {
        $this->backupDir = config('gpanel.gpanel_dir', '/gpanel') . '/storage/backups';
    }

    public function index()
    {
        $backups = $this->listBackups();
        return view('backups.index', compact('backups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'target' => ['required', 'in:sites,databases,full'],
        ]);

        $target   = $validated['target'];
        $ts       = now()->format('Y-m-d_H-i-s');
        $rootPass = config('gpanel.mysql_root_password', '');

        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        try {
            if (in_array($target, ['sites', 'full'])) {
                $this->cmd->runOrFail('backup.tar_site', [
                    'output_path' => $this->backupDir . "/sites_{$ts}.tar.gz",
                    'site_path'   => '/var/www/sites',
                ]);
            }

            if (in_array($target, ['databases', 'full'])) {
                $databases = Database::where('driver', 'mysql')->get();
                foreach ($databases as $db) {
                    $this->cmd->runOrFail('backup.mysqldump', [
                        'root_pass'   => $rootPass,
                        'db_name'     => $db->name,
                        'output_path' => $this->backupDir . "/{$db->name}_{$ts}.sql",
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['target' => 'Erro ao criar backup: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Backup criado com sucesso.');
    }

    public function download(string $filename)
    {
        // Sanitize — apenas nome do arquivo, sem path traversal
        $filename = basename($filename);
        $path = $this->backupDir . '/' . $filename;

        if (!file_exists($path) || !preg_match('/\.(tar\.gz|sql)$/', $filename)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function destroy(string $filename)
    {
        $filename = basename($filename);
        $path = $this->backupDir . '/' . $filename;

        if (file_exists($path) && preg_match('/\.(tar\.gz|sql)$/', $filename)) {
            unlink($path);
        }

        return back()->with('success', 'Backup removido.');
    }

    private function listBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $files = array_merge(
            glob($this->backupDir . '/*.tar.gz') ?: [],
            glob($this->backupDir . '/*.sql') ?: []
        );

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i', filemtime($file)),
                'ts'   => filemtime($file),
            ];
        }

        usort($backups, fn ($a, $b) => $b['ts'] - $a['ts']);

        return $backups;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1024, 1) . ' KB';
    }
}
