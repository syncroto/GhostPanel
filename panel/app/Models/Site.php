<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'type',
        'php_version',
        'node_version',
        'port',
        'root_path',
        'status',
        'ssl_enabled',
        'user_id',
        'nginx_config_path',
        'supervisor_program',
        'notes',
        'security_config',
    ];

    // Usa domain como chave de rota em vez do ID
    public function getRouteKeyName(): string
    {
        return 'domain';
    }

    protected $casts = [
        'ssl_enabled'     => 'boolean',
        'security_config' => 'array',
    ];

    // ---------------------------------------------------------------------- //

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function siteBackups(): HasMany
    {
        return $this->hasMany(\App\Models\SiteBackup::class);
    }

    // ---------------------------------------------------------------------- //

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'php'       => 'PHP ' . $this->php_version,
            'nodejs'    => 'Node.js ' . $this->node_version,
            'python'    => 'Python 3',
            'wordpress' => 'WordPress (PHP ' . $this->php_version . ')',
            'html5'     => 'HTML5 / Static',
            default     => strtoupper($this->type),
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'running'  => 'green',
            'stopped'  => 'red',
            'creating' => 'yellow',
            'deleting' => 'orange',
            'error'    => 'red',
            default    => 'gray',
        };
    }

    public function getTypeBadgeColor(): string
    {
        return match ($this->type) {
            'php'       => 'blue',
            'nodejs'    => 'green',
            'python'    => 'yellow',
            'wordpress' => 'purple',
            default     => 'gray',
        };
    }
}
