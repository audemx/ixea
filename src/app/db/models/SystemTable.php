<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemTable extends Model
{
    protected $table = 'system_tables';
    public $timestamps = false;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
