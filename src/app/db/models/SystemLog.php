<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';
    public $timestamps = false;
    protected $guarded = [];

    // --- Relaciones BelongsTo ---

    public function action()
    {
        return $this->belongsTo(SystemAction::class, 'action_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function table()
    {
        return $this->belongsTo(SystemTable::class, 'table_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
