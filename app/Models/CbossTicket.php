<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbossTicket extends Model
{
    use HasFactory;

    protected $primaryKey = 'ticket_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'cboss_tickets'; // Nama tabel

    protected $fillable = [
        'ticket_id',
        'site_id',
        'province',
        'spmk',
        'problem_map',
        'trouble_category',
        'status',
        'detail_action',
        'ticket_start',
        'ticket_end',
    ];

    protected $casts = [
        'ticket_start' => 'datetime',
        'ticket_end' => 'datetime',
    ];

    public function siteDetail(): BelongsTo
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }

    public function areaList(): BelongsTo
    {
        return $this->belongsTo(AreaList::class, 'province', 'province');
    }
}