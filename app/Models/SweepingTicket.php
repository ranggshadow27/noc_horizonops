<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SweepingTicket extends Model
{
    protected $primaryKey = 'sweeping_id';
    public $incrementing = false; // Karena sweeping_id adalah string, bukan auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'sweeping_id',
        'site_id',
        'status',
        'classification',
        'problem_classification',
        'cboss_tt',
        'cboss_problem',
    ];

    /**
     * Relasi ke table site_details
     */
    public function siteDetail(): BelongsTo
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }

    public function area()
    {
        return $this->belongsTo(AreaList::class, 'site_province', 'province');
    }

    public function cbossTmo()
    {
        return $this->hasMany(CbossTmo::class, 'site_id', 'site_id');
    }

    public function haloBaktiTicket()
    {
        return $this->hasMany(HaloBaktiTicket::class, 'site_id', 'site_id');
    }

    public function followupLogs()
    {
        return $this->hasMany(SweepingTicketsFollowupLog::class, 'sweeping_id', 'sweeping_id');
    }

    public function getFollowupStatusAttribute()
    {
        $latest = $this->followupLogs()->latest('id')->first();
        return $latest?->status ?? 'no_log';
    }

    public function getTotalAttemptsAttribute()
    {
        return $this->followupLogs()->sum('attempt');
    }
}
