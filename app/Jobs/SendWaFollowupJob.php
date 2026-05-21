<?php

namespace App\Jobs;

use App\Models\SweepingTicketsFollowupLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWaFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $logId) {}

    public function handle()
    {
        $log = SweepingTicketsFollowupLog::find($this->logId);
        if (!$log || $log->status !== 'pending') {
            return;
        }

        $log->increment('attempt');
        $log->update(['last_attempt_at' => now()]);

        try {
            $response = Http::timeout(30)  // safety timeout
                ->post(env('WATZAP_BASE_URL') . '/send_message', [   // pastikan endpoint benar
                    'api_key'    => env('WATZAP_API_KEY'),
                    'number_key' => $log->number_key,
                    'phone_no'   => $log->pic_phone,
                    'message'    => $log->message,
                ]);

            $responseData = $response->json();

            $status = ($response->successful() &&
                isset($responseData['status']) &&
                in_array($responseData['status'], ['200', 200, 'success']))
                ? 'sent'
                : 'failed';

            $log->update([
                'api_response'  => $responseData,
                'status'        => $status,
                'error_message' => $status === 'failed' ? json_encode($responseData) : null,
            ]);

            // $this->info("✅ Sukses kirim ke {$log->pic_phone}");
        } catch (\Exception $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // $this->error("❌ Gagal kirim ke {$log->pic_phone} - " . $e->getMessage());
        }
    }
}
