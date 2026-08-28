<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    public $timestamps = true;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function taxProfile()
    {
        return $this->belongsTo(TaxProfile::class, 'tax_profile_id');
    }
}
