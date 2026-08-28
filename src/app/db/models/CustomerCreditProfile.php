<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditProfile extends Model
{
    protected $table = 'customer_credit_profiles';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
