<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BroadcastSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'broadcast_sessions';

    protected $fillable = [
        'name',
        'area',
        'number_key',
        'template_message',
        'interval_minutes',
        'status',
        'total_logs',
        'sent_count',
        'failed_count',
        'started_at',
        'last_processed_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(SweepingTicketsFollowupLog::class, 'broadcast_session_id');
    }
}
