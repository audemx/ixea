<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAction extends Model
{
    protected $table = 'system_actions';
    public $timestamps = false;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
