<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteDetail extends Model
{
    use HasFactory;

    // Nama tabel yang digunakan oleh model ini
    protected $table = 'site_details';

    // Menentukan kolom yang bisa diisi
    protected $fillable = [
        'site_id',
        'site_name',
        'province',
        'administrative_area',
        'address',
        'latitude',
        'longitude',
        'spotbeam',
        'ip_hub',
        'gateway',
        'power_source',
        'batch',
        'pic_number',
        'pic_name',
        'installer_number',
        'installer_name',
    ];

    // Menentukan kolom yang digunakan sebagai primary key
    protected $primaryKey = 'site_id';

    // Jika kamu ingin mencegah auto-increment ID (karena menggunakan site_id sebagai primary key)
    public $incrementing = false;

    // Menentukan tipe data primary key (karena site_id string, bukan integer)
    protected $keyType = 'string';

    // Relasi ke Device
    public function devices()
    {
        return $this->hasOne(Device::class, 'site_id', 'site_id');
    }

    // Relasi ke DeviceNetwork
    public function deviceNetworks()
    {
        return $this->hasOne(DeviceNetwork::class, 'site_id', 'site_id');
    }

    public function siteMonitor()
    {
        return $this->hasOne(SiteMonitor::class, 'site_id', 'site_id');
    }

    public function nmtTickets()
    {
        return $this->hasMany(NmtTickets::class, 'site_id', 'site_id');
    }

    public function area()
    {
        return $this->belongsTo(AreaList::class, 'province', 'province');
    }

    public function sweepingTickets()
    {
        return $this->hasMany(SweepingTicket::class, 'site_id', 'site_id');
    }

    public function cbossTmo()
    {
        return $this->hasMany(CbossTmo::class, 'site_id', 'site_id');
    }

    public function cbossTicket()
    {
        return $this->hasMany(CbossTicket::class, 'site_id');
    }
}
