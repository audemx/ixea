<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function accountIn()
    {
        return $this->belongsTo(Account::class, 'account_in');
    }

    public function accountOut()
    {
        return $this->belongsTo(Account::class, 'account_out');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
