<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'operation',
        'params',
        'status',
        'exit_code',
        'ip',
    ];

    public $timestamps = false;

    protected $casts = [
        'params'     => 'array',
        'created_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();
        static::creating(fn($model) => $model->created_at = now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
