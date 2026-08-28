<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceSnapshot extends Model
{
    protected $table = 'finance_snapshots';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
