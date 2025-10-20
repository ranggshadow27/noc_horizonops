<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpPerformance extends Model
{
    protected $fillable = ['sp_perf_id', 'sp_id', 'today_ticket', 'created_at'];
    public $incrementing = false;
    protected $primaryKey = 'sp_perf_id';
}
