<?php

namespace App\Models\Fee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeDetail extends Model
{
    use HasFactory;
    protected $table = 'fee_details';
    protected $guarded = [];
}
