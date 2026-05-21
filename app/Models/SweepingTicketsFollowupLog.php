<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SweepingTicketsFollowupLog extends Model
{
    protected $table = 'sweeping_tickets_followup_logs';

    protected $fillable = [
        'broadcast_session_id',
        'sweeping_id',           // diganti
        'number_key',
        'pic_phone',
        'pic_name',
        'message',
        'api_response',
        'status',
        'attempt',
        'last_attempt_at',
        'error_message',
    ];

    protected $casts = [
        'api_response' => 'array',
        'last_attempt_at' => 'datetime',
    ];

    public function broadcastSession()
    {
        return $this->belongsTo(BroadcastSession::class, 'broadcast_session_id');
    }

    public function sweepingTicket()
    {
        return $this->belongsTo(SweepingTicket::class, 'sweeping_id', 'sweeping_id');
    }
}
