<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditSale extends Model
{
    protected $table = 'customer_credit_sales';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
