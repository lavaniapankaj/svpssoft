<?php

namespace App\Models\Admin;

use App\Models\Admin\ClassMaster;
use App\Models\Admin\SessionMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeMaster extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function class()
    {
        return $this->belongsTo(ClassMaster::class, 'class_id');
    }

    public function session()
    {
        return $this->belongsTo(SessionMaster::class, 'session_id');
    }
}
