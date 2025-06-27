<?php

namespace App\Models\Admin;

use App\Models\Admin\SessionMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceSchedule extends Model
{
    use HasFactory;

    protected $table = 'attendance_schedule';
    protected $guarded = [];

    public function session()
    {
        return $this->belongsTo(SessionMaster::class, 'session_id','id');
    }

}

