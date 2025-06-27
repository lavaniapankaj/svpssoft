<?php

namespace App\Models\Admin;

use App\Models\Admin\StateMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DistrictMaster extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function state()
    {
        return $this->belongsTo(StateMaster::class, 'state_id');
    }
}
