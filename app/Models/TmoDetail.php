<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoDetail extends Model
{
    use HasFactory;

    protected $table = 'tmo_details';
    // public $incrementing = true; // Set true jika menggunakan tmo_detail_id sebagai primary key
    protected $primaryKey = 'tmo_id'; // Set primary key
    public $incrementing = false; // Karena bukan auto-increment
    protected $keyType = 'string'; // Karena tmo_id bertipe string

    protected $fillable = [
        'tmo_id',
        'transceiver_sn',
        'feedhorn_sn',
        'antenna_sn',
        'stabillizer_sn',
        'rack_sn',
        'modem_sn',
        'router_sn',
        'ap1_sn',
        'ap2_sn',
        'transceiver_type',
        'modem_type',
        'router_type',
        'ap1_type',
        'ap2_type',
    ];

    public function tmoData()
    {
        return $this->belongsTo(TmoData::class, 'tmo_id', 'tmo_id');
    }
}
