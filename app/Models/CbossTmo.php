<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbossTmo extends Model
{
    use HasFactory;

    protected $primaryKey = 'tmo_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'cboss_tmo'; // Nama tabel


    protected $fillable = [
        'tmo_id',
        'site_id',
        'province',
        'spmk_number',
        'techinican_name',
        'techinican_number',
        'pic_name',
        'pic_number',
        'tmo_by',
        'tmo_code',
        'esno',
        'sqf',
        'ifl_cable',
        'problem',
        'action',
        'homebase',
        'tmo_date',
    ];

    protected $casts = [
        'action' => 'array',
        'tmo_date' => 'datetime',
    ];

    // Relationships
    public function siteDetail(): BelongsTo
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaList::class, 'province', 'province');
    }
}
