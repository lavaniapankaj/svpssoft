<?php

namespace App\Models\Admin;

use App\Models\Admin\ExamMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarksMaster extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function exam(){
        return $this->belongsTo(ExamMaster::class,'exam_id');
    }
}
