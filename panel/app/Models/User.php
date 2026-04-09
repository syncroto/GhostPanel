<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'db_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ---------------------------------------------------------------------- //

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function allowedSites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'user_site');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAccessSite(Site $site): bool
    {
        if ($this->isAdmin()) return true;
        return $this->allowedSites()->where('site_id', $site->id)->exists();
    }
}
