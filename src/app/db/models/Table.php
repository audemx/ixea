<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $table = 'tables';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function useStatus()
    {
        return $this->belongsTo(Status::class, 'use_status_id');
    }
}
