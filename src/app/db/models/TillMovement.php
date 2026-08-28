<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TillMovement extends Model
{
    protected $table = 'till_movements';
    public $timestamps = false;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function table()
    {
        return $this->belongsTo(SystemTable::class, 'table_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
