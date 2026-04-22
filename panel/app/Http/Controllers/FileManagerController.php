<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    private const ALLOWED_ROOTS = [
        '/var/www/sites',
        '/gpanel/storage',
        '/var/log/nginx',
    ];

    // Max file size for viewing/editing (2 MB)
    private const MAX_EDIT_SIZE = 2 * 1024 * 1024;

    // ---------------------------------------------------------------------- //
    //  Listing
    // ---------------------------------------------------------------------- //

    public function listFiles(Request $request)
    {
        $path = $this->safePath($request->query('path', '/var/www/sites'));
        if (!$path) return response()->json(['error' => 'Caminho não permitido.'], 403);
        if (!is_dir($path)) return response()->json(['error' => 'Não é um diretório.'], 400);

        $items = [];
        $entries = @scandir($path) ?: [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full  = rtrim($path, '/') . '/' . $entry;
            $isDir = is_dir($full);
            $size  = $isDir ? null : (@filesize($full) ?: 0);
            $items[] = [
                'name'     => $entry,
                'type'     => $isDir ? 'dir' : 'file',
                'size'     => $isDir ? null : $this->formatBytes($size),
                'size_raw' => $isDir ? 0 : $size,
                'modified' => date('d/m/Y H:i', @filemtime($full) ?: 0),
                'perms'    => substr(sprintf('%o', @fileperms($full) ?: 0), -4),
                'ext'      => $isDir ? null : strtolower(pathinfo($entry, PATHINFO_EXTENSION)),
            ];
        }

        // Dirs first, then files, both alphabetical
        usort($items, fn($a, $b) =>
            $a['type'] !== $b['type']
                ? ($a['type'] === 'dir' ? -1 : 1)
                : strcmp($a['name'], $b['name'])
        );

        return response()->json(['path' => $path, 'items' => $items]);
    }

    // ---------------------------------------------------------------------- //
    //  View / Edit
    // ---------------------------------------------------------------------- //

    public function view(Request $request)
    {
        $path = $this->safePath($request->query('path', ''));
        if (!$path || !is_file($path)) return response()->json(['error' => 'Arquivo não encontrado.'], 404);

        $size = filesize($path);
        if ($size > self::MAX_EDIT_SIZE) {
            return response()->json(['error' => "Arquivo muito grande para visualização (máx 2MB). Tamanho: " . $this->formatBytes($size)], 413);
        }

        return response()->json([
            'path'    => $path,
            'name'    => basename($path),
            'content' => file_get_contents($path),
            'size'    => $this->formatBytes($size),
            'ext'     => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
        ]);
    }

    public function save(Request $request)
    {
        $request->validate(['path' => 'required|string', 'content' => 'required|string']);
        $path = $this->safePath($request->path);
        if (!$path) return response()->json(['error' => 'Caminho não permitido.'], 403);

        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        file_put_contents($path, $request->content);
        return response()->json(['message' => 'Arquivo salvo com sucesso.']);
    }

    // ---------------------------------------------------------------------- //
    //  Delete
    // ---------------------------------------------------------------------- //

    public function delete(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $this->safePath($request->path);
        if (!$path) return response()->json(['error' => 'Caminho não permitido.'], 403);

        if (is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            $this->rmdir_recursive($path);
        }

        return response()->json(['message' => 'Removido com sucesso.']);
    }

    // ---------------------------------------------------------------------- //
    //  Create directory / file
    // ---------------------------------------------------------------------- //

    public function mkdir(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $this->safePath($request->path);
        if (!$path) return response()->json(['error' => 'Caminho não permitido.'], 403);

        if (file_exists($path)) return response()->json(['error' => 'Já existe.'], 422);

        @mkdir($path, 0755, true);
        return response()->json(['message' => 'Pasta criada.']);
    }

    public function touch(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $this->safePath($request->path);
        if (!$path) return response()->json(['error' => 'Caminho não permitido.'], 403);

        if (file_exists($path)) return response()->json(['error' => 'Arquivo já existe.'], 422);

        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        file_put_contents($path, '');
        return response()->json(['message' => 'Arquivo criado.']);
    }

    // ---------------------------------------------------------------------- //
    //  Rename / Move
    // ---------------------------------------------------------------------- //

    public function rename(Request $request)
    {
        $request->validate([
            'path'     => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        $path = $this->safePath($request->path);
        if (!$path || !file_exists($path)) {
            return response()->json(['error' => 'Arquivo/pasta não encontrado.'], 404);
        }

        // Sanitize: only the basename, no path traversal
        $newName = basename($request->new_name);
        if (empty($newName) || $newName === '.' || $newName === '..') {
            return response()->json(['error' => 'Nome inválido.'], 422);
        }

        $newPath = $this->safePath(dirname($path) . '/' . $newName);
        if (!$newPath) return response()->json(['error' => 'Destino não permitido.'], 403);

        if (file_exists($newPath)) {
            return response()->json(['error' => 'Já existe um item com esse nome.'], 422);
        }

        rename($path, $newPath);
        return response()->json(['message' => 'Renomeado com sucesso.']);
    }

    // ---------------------------------------------------------------------- //
    //  Download
    // ---------------------------------------------------------------------- //

    public function download(Request $request)
    {
        $path = $this->safePath($request->query('path', ''));
        if (!$path || !is_file($path)) abort(404);

        return response()->download($path);
    }

    // ---------------------------------------------------------------------- //
    //  Upload
    // ---------------------------------------------------------------------- //

    public function upload(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'file' => 'required|file|max:102400', // max 100MB
        ]);

        $dir = $this->safePath($request->path);
        if (!$dir) return response()->json(['error' => 'Caminho não permitido.'], 403);
        if (!is_dir($dir)) return response()->json(['error' => 'Destino não é um diretório.'], 400);

        $file = $request->file('file');

        // Sanitize filename
        $name = preg_replace('/[^a-zA-Z0-9._\-()]/', '_', $file->getClientOriginalName());
        if (empty($name)) $name = 'upload_' . time();

        try {
            $file->move($dir, $name);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha ao salvar arquivo: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Upload realizado.', 'name' => $name]);
    }

    // ---------------------------------------------------------------------- //
    //  Permissions (chmod)
    // ---------------------------------------------------------------------- //

    public function chmod(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'mode' => ['required', 'string', 'regex:/^[0-7]{3,4}$/'],
        ]);

        $path = $this->safePath($request->path);
        if (!$path || !file_exists($path)) {
            return response()->json(['error' => 'Arquivo/pasta não encontrado.'], 404);
        }

        chmod($path, octdec($request->mode));
        return response()->json([
            'message' => 'Permissões alteradas.',
            'perms'   => substr(sprintf('%o', fileperms($path)), -4),
        ]);
    }

    // ---------------------------------------------------------------------- //
    //  Helpers
    // ---------------------------------------------------------------------- //

    private function safePath(string $input): string|false
    {
        // Resolve caminho já existente via realpath (imune a traversal)
        $resolved = realpath($input);
        if ($resolved) {
            foreach (self::ALLOWED_ROOTS as $root) {
                if (str_starts_with($resolved, $root . '/') || $resolved === $root) {
                    return $resolved;
                }
            }
            return false;
        }

        // Para caminhos não existentes (create/mkdir/touch):
        // Resolve o diretório pai via realpath e concatena apenas o basename
        $parentResolved = realpath(dirname($input));
        if (!$parentResolved) {
            return false;
        }

        $basename = basename($input);
        // Rejeita componentes de navegação
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return false;
        }

        $normalized = $parentResolved . '/' . $basename;

        foreach (self::ALLOWED_ROOTS as $root) {
            if (str_starts_with($normalized, $root . '/') || $normalized === $root) {
                return $normalized;
            }
        }
        return false;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    private function rmdir_recursive(string $dir): void
    {
        foreach (@scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->rmdir_recursive($p) : unlink($p);
        }
        rmdir($dir);
    }
}
