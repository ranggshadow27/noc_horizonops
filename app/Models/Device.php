<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    // Nama tabel yang digunakan oleh model ini
    protected $table = 'devices';

    // Menentukan kolom yang bisa diisi
    protected $fillable = [
        'site_id',
        'rack_sn',
        'antenna_sn',
        'antenna_type',
        'transceiver_sn',
        'transceiver_type',
        'modem_sn',
        'modem_type',
        'router_sn',
        'router_type',
        'ap1_sn',
        'ap1_type',
        'ap2_sn',
        'ap2_type',
        'stabilizer_sn',
        'stabilizer_type',
    ];

    // Menentukan kolom yang digunakan sebagai primary key
    protected $primaryKey = 'device_id';

    // Jika kamu ingin mencegah auto-increment ID (karena menggunakan device_id sebagai primary key)
    public $incrementing = true;

    // Menentukan tipe data primary key (karena device_id integer)
    protected $keyType = 'int';

    // Relasi dengan SiteDetail (karena site_id adalah FK)
    public function site()
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }
}
