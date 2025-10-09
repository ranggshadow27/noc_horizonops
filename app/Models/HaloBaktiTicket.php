<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class HaloBaktiTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'ticket_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'halo_bakti_tickets';

    protected $fillable = ['ticket_id', 'site_id', 'description', 'pic_name', 'pic_number', 'hb_tt_number', 'status', 'comments'];

    protected $casts = [
        'comments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $today = now()->format('Ymd');
            $lastTicket = static::where('ticket_id', 'like', "MHG-HB-{$today}-%")
                ->orderBy('ticket_id', 'desc')
                ->first();

            $newNumber = 1;
            if ($lastTicket) {
                $lastNumber = (int) substr($lastTicket->ticket_id, -3);
                $newNumber = $lastNumber + 1;
            }
            $paddedNumber = str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            $model->ticket_id = "MHG-HB-{$today}-{$paddedNumber}";

            if ($model->description) {
                preg_match('/Nama\s*:\s*([^\n]+)/i', $model->description, $nameMatch);
                $model->pic_name = $nameMatch[1] ?? $model->pic_name;
                preg_match('/Telepon\s*:\s*(\d+)/i', $model->description, $numberMatch);
                $model->pic_number = $numberMatch[1] ?? $model->pic_number;
                preg_match('/Ticket\s*:\s*([^\n]+)/i', $model->description, $ttMatch);
                $model->hb_tt_number = $ttMatch[1] ?? $model->hb_tt_number;
            }

            // Ambil comments, tambah comment, lalu set kembali
            $comments = $model->comments ?? [];
            $comments[] = [
                'comment' => "Ticket opened at " . now()->format('D, d M Y H:i'),
                'time' => now()->toDateTimeString(),
                'user_id' => 'System',
                'images' => [],
            ];
            $model->comments = $comments; // Set ulang
        });

        static::updating(function ($model) {
            if ($model->isDirty('description')) {
                preg_match('/Nama\s*:\s*([^\n]+)/i', $model->description, $nameMatch);
                $model->pic_name = $nameMatch[1] ?? $model->pic_name;
                preg_match('/Telepon\s*:\s*(\d+)/i', $model->description, $numberMatch);
                $model->pic_number = $numberMatch[1] ?? $model->pic_number;
                preg_match('/Ticket\s*:\s*([^\n]+)/i', $model->description, $ttMatch);
                $model->hb_tt_number = $ttMatch[1] ?? $model->hb_tt_number;
            }

            if ($model->isDirty('status')) {
                $oldStatus = $model->getOriginal('status');
                $newStatus = $model->status;
                $comments = $model->comments ?? [];
                $comments[] = [
                    'comment' => "Ticket status changed from {$oldStatus} to {$newStatus}",
                    'time' => now()->toDateTimeString(),
                    'user_id' => 'System',
                    'images' => [],
                ];
                $model->comments = $comments;
            }
        });
    }

    public function site()
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }

    public function addComment(string $comment, array $images = [], ?string $userId = null)
    {
        $comments = $this->comments ?? [];
        $comments[] = [
            'comment' => $comment,
            'time' => now()->toDateTimeString(),
            'user_id' => $userId ?? Auth::id() ?? 'System',
            'images' => $images,
        ];
        $this->comments = $comments;
        $this->save();
    }

    public function removeComment(int $index)
    {
        $comments = $this->comments ?? [];
        if (isset($comments[$index])) {
            unset($comments[$index]);
            $this->comments = array_values($comments);
            $this->save();
        }
    }
}
