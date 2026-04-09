<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ────────────────────────────────────────────────────────── //
//  Tarefas agendadas
// ────────────────────────────────────────────────────────── //

// Renovação de SSL (diário às 3:30 AM)
Schedule::command('ssl:renew')->dailyAt('03:30');

// Limpeza de logs de auditoria antigos (semanal)
Schedule::call(function () {
    \App\Models\AuditLog::where('created_at', '<', now()->subDays(90))->delete();
})->weekly();

// Limpeza de backups de sites expirados (diário à meia-noite)
Schedule::call(function () {
    $gpanelDir  = config('gpanel.gpanel_dir', '/gpanel');
    $backupBase = $gpanelDir . '/storage/backups/sites';
    $expired    = \App\Models\SiteBackup::where('expires_at', '<', now())->get();

    foreach ($expired as $backup) {
        $path = "{$backupBase}/{$backup->site_id}/{$backup->filename}";
        if (file_exists($path)) {
            @unlink($path);
        }
        $backup->delete();
    }
})->dailyAt('00:30');
