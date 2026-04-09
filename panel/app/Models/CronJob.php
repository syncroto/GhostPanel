<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronJob extends Model
{
    protected $fillable = ['site_id', 'schedule', 'command', 'run_as'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
