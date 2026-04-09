<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteBackup extends Model
{
    protected $fillable = ['site_id', 'filename', 'size', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'size'       => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1024, 1) . ' KB';
    }
}
