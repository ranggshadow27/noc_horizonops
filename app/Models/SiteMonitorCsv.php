<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteMonitorCsv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'site_monitor_csv';

    protected $fillable = [
        'site_id',
        'sitecode',
        'modem',
        'mikrotik',
        'ap1',
        'ap2',
        'modem_last_up',
        'mikrotik_last_up',
        'ap1_last_up',
        'ap2_last_up',
        'status',
        'sensor_status',
        'uptime_modem_dur',
        'uptime_modem_percentage',
        'gateway',
        'beam_id',
    ];

    // Cast tipe data
    protected $casts = [
        'modem_last_up'     => 'datetime',
        'mikrotik_last_up'  => 'datetime',
        'ap1_last_up'       => 'datetime',
        'ap2_last_up'       => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    // Enum helper (opsional, biar lebih enak dipakai)
    public const STATUS_UP = 'Up';
    public const STATUS_DOWN = 'Down';
    public const STATUS_FAILED = 'Failed';

    public const OVERALL_STATUS = [
        'Normal',
        'Minor',
        'Major',
        'Critical',
        'Warning'
    ];
}
