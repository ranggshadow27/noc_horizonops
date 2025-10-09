<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HBTicketUpdate extends Model
{
    use HasFactory;

    protected $table = 'hb_ticket_updates'; // Eksplisit set tabel

    protected $fillable = ['ticket_id', 'comment', 'user_id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = Auth::id();  // Auto set user yang nambah comment
        });
    }

    public function ticket()
    {
        return $this->belongsTo(HaloBaktiTicket::class, 'ticket_id', 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
};
