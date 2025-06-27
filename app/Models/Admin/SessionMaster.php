<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionMaster extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function adminSession()
    {
        return $this->belongsTo(SessionMaster::class, 'admin_current_session');
    }
}
