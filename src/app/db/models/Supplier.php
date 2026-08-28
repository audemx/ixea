<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account');
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
