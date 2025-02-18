<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoTask extends Model
{
    use HasFactory;

    protected $table = 'tmo_task';
    protected $primaryKey = 'task_id';

    protected $fillable = [
        'spmk_number',
        'site_id',
        'site_name',
        'province',
        'latitude',
        'longitude',
        'address',
        'engineer',
        'engineer_number',
        'tmo_id',
        'tmo_type',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function tmoData()
    {
        return $this->hasOne(TmoData::class, 'tmo_id', 'tmo_id');
    }

    public function site()
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }
}
