<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class NmtTickets extends Model
{
    use HasFactory;

    protected $primaryKey = 'ticket_id';  // Menetapkan ticket_id sebagai primary key
    public $incrementing = false;  // Menonaktifkan auto-increment karena ticket_id bukan angka
    protected $table = 'nmt_tickets';

    protected $fillable = [
        'ticket_id',
        'cboss_tt',
        'site_id',
        'site_province',
        'status',
        'date_start',
        'aging',
        'closed_date',
        'target_online',
        'problem_classification',
        'problem_detail',
        'problem_type',
        'update_progress',
    ];

    public function site()
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');  // Relasi ke SiteDetail
    }

    public function area()
    {
        return $this->belongsTo(AreaList::class, 'site_province', 'province');
    }

    public function siteMonitor()
    {
        return $this->hasOne(SiteMonitor::class, 'site_id', 'site_id');
    }
}
