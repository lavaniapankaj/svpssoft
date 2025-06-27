<?php

namespace App\Models\Admin;

use App\Models\Admin\ClassMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SectionMaster extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function class()
    {
        return $this->belongsTo(ClassMaster::class, 'class_id');
    }
}
