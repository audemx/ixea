<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $table = 'ledgers';
    public $timestamps = false;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function table()
    {
        return $this->belongsTo(SystemTable::class, 'table_id');
    }
}
