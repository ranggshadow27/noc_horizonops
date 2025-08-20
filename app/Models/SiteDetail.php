<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteDetail extends Model
{
    use HasFactory;

    protected $table = 'site_details';
    protected $primaryKey = 'site_id';
    public $incrementing = false;
    protected $keyType = 'string';

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

    /**
     * Relasi ke SiteLog (hasMany)
     */
    public function siteLogs()
    {
        return $this->hasMany(SiteLog::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke SiteMonitor (hasOne)
     */
    public function siteMonitor()
    {
        return $this->hasOne(SiteMonitor::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke NmtTickets (hasMany)
     */
    public function nmtTickets()
    {
        return $this->hasMany(NmtTickets::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke Device (hasOne)
     */
    public function devices()
    {
        return $this->hasOne(Device::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke DeviceNetwork (hasOne)
     */
    public function deviceNetworks()
    {
        return $this->hasOne(DeviceNetwork::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke AreaList (belongsTo)
     */
    public function area()
    {
        return $this->belongsTo(AreaList::class, 'province', 'province');
    }

    /**
     * Relasi ke SweepingTicket (hasMany)
     */
    public function sweepingTickets()
    {
        return $this->hasMany(SweepingTicket::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke CbossTmo (hasMany)
     */
    public function cbossTmo()
    {
        return $this->hasMany(CbossTmo::class, 'site_id', 'site_id');
    }

    /**
     * Relasi ke CbossTicket (hasMany)
     */
    public function cbossTicket()
    {
        return $this->hasMany(CbossTicket::class, 'site_id', 'site_id');
    }
}
