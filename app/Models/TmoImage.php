<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoImage extends Model
{
    use HasFactory;

    protected $table = 'tmo_images';
    protected $primaryKey = 'tmo_id'; // Set primary key
    public $incrementing = false; // Karena bukan auto-increment
    protected $keyType = 'string'; // Karena tmo_id bertipe string

    protected $fillable = [
        'tmo_id',
        'transceiver_img',
        'feedhorn_img',
        'antenna_img',
        'stabillizer_img',
        'rack_img',
        'modem_img',
        'router_img',
        'ap1_img',
        'ap2_img',
        'modem_summary_img',
        'pingtest_img',
        'speedtest_img',
        'cm_ba_img',
        'pm_ba_img',
        'signplace_img',
        'stabillizer_voltage_img',
        'power_source_voltage_img',
    ];

    public function tmoData()
    {
        return $this->belongsTo(TmoData::class, 'tmo_id', 'tmo_id');
    }
}
