<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function creditStatus()
    {
        return $this->belongsTo(Status::class, 'credit_status_id');
    }

    public function taxProfile()
    {
        return $this->belongsTo(TaxProfile::class, 'tax_profile_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
