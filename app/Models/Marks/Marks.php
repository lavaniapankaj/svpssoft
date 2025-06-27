<?php

namespace App\Models\Marks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marks extends Model
{
    use HasFactory;
    protected $table = 'marks';
    protected $guarded = [];
}
