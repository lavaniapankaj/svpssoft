<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupSMSMobile extends Model
{
    use HasFactory;
    protected $table = 'mobile_number';
    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo(GroupSMS::class, 'group_id');
    }
}
