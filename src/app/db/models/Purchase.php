<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function handler()
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }

    public function paidStatus()
    {
        return $this->belongsTo(Status::class, 'paid_status');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function receivedStatus()
    {
        return $this->belongsTo(Status::class, 'received_status');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
