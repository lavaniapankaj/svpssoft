<?php

namespace App\Models\Student;

use App\Models\Admin\ClassMaster;
use App\Models\Admin\SectionMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMaster extends Model
{
    use HasFactory;

    protected $table = 'stu_main_srno';
    protected $guarded = [];

    
}
