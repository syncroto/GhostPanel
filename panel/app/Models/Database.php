<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Database extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'username',
        'driver',
        'host',
        'port',
        'notes',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
