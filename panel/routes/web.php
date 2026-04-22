<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteSecurityController;
use App\Http\Controllers\SiteBackupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------- //
//  Setup
// ---------------------------------------------------------------------- //
Route::middleware('gpanel.setup')->group(function () {
    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::get('/', function () {
    if (\App\Models\User::count() === 0) return redirect()->route('setup.index');
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// ---------------------------------------------------------------------- //
//  Auth
// ---------------------------------------------------------------------- //
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', \App\Http\Controllers\Auth\LoginController::class . '@store')->middleware('throttle:5,1')->name('login.store');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

// ---------------------------------------------------------------------- //
//  Painel
// ---------------------------------------------------------------------- //
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sites — usa domain como chave (getRouteKeyName = 'domain')
    Route::get('/sites',                  [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create',           [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites',                 [SiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{site}',           [SiteController::class, 'show'])->name('sites.show');
    Route::delete('/sites/{site}',        [SiteController::class, 'destroy'])->name('sites.destroy');

    Route::post('/sites/{site}/restart',    [SiteController::class, 'restart'])->name('sites.restart');
    Route::post('/sites/{site}/toggle-ssl', [SiteController::class, 'toggleSsl'])->name('sites.toggle-ssl');
    Route::get('/sites/{site}/vhost',       [SiteController::class, 'vhost'])->name('sites.vhost');
    Route::post('/sites/{site}/vhost',      [SiteController::class, 'vhostSave'])->name('sites.vhost.save');
    Route::get('/api/sites/{site}/vhost',   [SiteController::class, 'vhostJson'])->name('sites.vhost.json');
    Route::post('/api/sites/{site}/vhost',  [SiteController::class, 'vhostSaveJson'])->name('sites.vhost.save-json');
    Route::get('/sites/{site}/logs',        [SiteController::class, 'logs'])->name('sites.logs');
    Route::get('/api/check-port',           [SiteController::class, 'checkPort'])->name('sites.check-port');

    // File Manager API
    Route::get('/api/fm/list',      [FileManagerController::class, 'listFiles']);
    Route::get('/api/fm/view',      [FileManagerController::class, 'view']);
    Route::post('/api/fm/save',     [FileManagerController::class, 'save']);
    Route::delete('/api/fm/delete', [FileManagerController::class, 'delete']);
    Route::post('/api/fm/mkdir',    [FileManagerController::class, 'mkdir']);
    Route::post('/api/fm/touch',    [FileManagerController::class, 'touch']);
    Route::post('/api/fm/rename',   [FileManagerController::class, 'rename']);
    Route::get('/api/fm/download',  [FileManagerController::class, 'download']);
    Route::post('/api/fm/upload',   [FileManagerController::class, 'upload']);
    Route::post('/api/fm/chmod',    [FileManagerController::class, 'chmod']);

    // Databases
    Route::get('/databases',                                  [DatabaseController::class, 'index'])->name('databases.index');
    Route::post('/databases',                                 [DatabaseController::class, 'store'])->name('databases.store');
    Route::delete('/databases/{database}',                    [DatabaseController::class, 'destroy'])->name('databases.destroy');
    Route::post('/databases/{database}/reset-password',       [DatabaseController::class, 'resetPassword'])->name('databases.reset-password');
    Route::post('/api/sites/{site}/databases',                [DatabaseController::class, 'storeBySite'])->name('databases.store-by-site');

    // Cron Jobs
    Route::post('/api/sites/{site}/cron',              [CronJobController::class, 'store'])->name('cron.store');
    Route::delete('/api/sites/{site}/cron/{cronJob}',  [CronJobController::class, 'destroy'])->name('cron.destroy');

    // Site Backups
    Route::post('/api/sites/{site}/backups',                        [SiteBackupController::class, 'store'])->name('site-backups.store');
    Route::get('/api/sites/{site}/backups/{backup}/download',       [SiteBackupController::class, 'download'])->name('site-backups.download');
    Route::delete('/api/sites/{site}/backups/{backup}',             [SiteBackupController::class, 'destroy'])->name('site-backups.destroy');

    // Site Security
    Route::post('/api/sites/{site}/security', [SiteSecurityController::class, 'update'])->name('sites.security');

    // Firewall (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/firewall',    [FirewallController::class, 'index'])->name('firewall.index');
        Route::post('/firewall',   [FirewallController::class, 'store'])->name('firewall.store');
        Route::delete('/firewall', [FirewallController::class, 'destroy'])->name('firewall.destroy');

        // Backups (admin only)
        Route::get('/backups',                     [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups',                    [BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{filename}',       [BackupController::class, 'destroy'])->name('backups.destroy');

        // Users (admin only)
        Route::get('/users',              [UserController::class, 'index'])->name('users.index');
        Route::post('/users',             [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}',       [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',    [UserController::class, 'destroy'])->name('users.destroy');
    });
});
