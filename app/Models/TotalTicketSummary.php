<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TotalTicketSummary extends Model
{
    use HasFactory;

    protected $table = 'total_ticket_summary';

    protected $fillable = [
        'summary_date',
        'ap1_down',
        'ap2_down',
        'ap1_and_2_down',
        'router_down',
        'all_sensor_down',
        'total_ticket',
    ];
}
