<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'statuses';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function color()
    {
        return $this->belongsTo(SystemColor::class, 'color');
    }
}
