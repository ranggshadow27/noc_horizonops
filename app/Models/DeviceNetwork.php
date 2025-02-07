<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceNetwork extends Model
{
    use HasFactory;

    // Nama tabel yang digunakan oleh model ini
    protected $table = 'device_networks';

    // Menentukan kolom yang bisa diisi
    protected $fillable = [
        'site_id',
        'modem_ip',
        'router_ip',
        'ap1_ip',
        'ap2_ip',
    ];

    // Menentukan kolom yang digunakan sebagai primary key
    protected $primaryKey = 'device_network_id';

    // Jika kamu ingin mencegah auto-increment ID (karena menggunakan device_network_id sebagai primary key)
    public $incrementing = true;

    // Menentukan tipe data primary key (karena device_network_id integer)
    protected $keyType = 'int';

    // Relasi dengan SiteDetail (karena site_id adalah FK)
    public function site()
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }
}

