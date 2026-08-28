<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditPayment extends Model
{
    protected $table = 'customer_credit_payments';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
