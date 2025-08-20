<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SiteLog extends Model
{
    protected $table = 'site_logs';
    protected $primaryKey = 'site_log_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'site_log_id',
        'site_id',
        'modem_uptime',
        'modem_last_up',
        'traffic_uptime',
        'sensor_status',
        'nmt_ticket',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate custom site_log_id saat record dibuat
        static::creating(function ($model) {
            if (empty($model->site_log_id)) {
                $today = Carbon::today()->format('Ymd');
                $model->site_log_id = "{$model->site_id}-{$today}";
            }
        });
    }

    /**
     * Relasi ke SiteDetail
     */
    public function siteDetails(): BelongsTo
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }

    public function nmtTicket(): BelongsTo
    {
        return $this->belongsTo(NmtTickets::class, 'nmt_ticket', 'ticket_id');
    }

    public function siteMonitor()
    {
        return $this->hasOne(SiteMonitor::class, 'site_id', 'site_id');
    }
}
