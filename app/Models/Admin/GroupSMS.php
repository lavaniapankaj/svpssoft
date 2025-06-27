<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupSMS extends Model
{
    use HasFactory;
    protected $table = 'sms_group';
    protected $guarded = [];
}
