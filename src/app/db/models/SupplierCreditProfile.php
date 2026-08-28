<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierCreditProfile extends Model
{
    protected $table = 'supplier_credit_profiles';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
