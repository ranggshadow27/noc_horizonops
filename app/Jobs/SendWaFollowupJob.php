<?php

namespace App\Jobs;

use App\Models\SweepingTicketsFollowupLog;
use App\Models\SiteMonitorCsv;           // pastikan import ini
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            // ==================== CEK SENSOR STATUS ====================
            $shouldSkip = $this->shouldSkipBecauseOnline($log->sweeping_id);

            if ($shouldSkip) {
                $this->markAsSiteOnline($log);
                return; // langsung keluar, tidak kirim WA
            }

            // ==================== KIRIM WA ====================
            $cleanMessage = str_replace(['\\n', '\n'], "\n", $log->message);

            $response = Http::timeout(30)
                ->post(env('WATZAP_BASE_URL') . '/send_message', [
                    'api_key'    => env('WATZAP_API_KEY'),
                    'number_key' => $log->number_key,
                    'phone_no'   => $log->pic_phone,
                    'message'    => $cleanMessage,
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
        } catch (\Exception $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    // ==================== HELPER METHODS ====================

    private function shouldSkipBecauseOnline(string $sweeping_id): bool
    {
        // Ambil classification dari sweeping ticket
        $ticket = \App\Models\SweepingTicket::select('classification')
            ->where('sweeping_id', $sweeping_id)
            ->first();

        // Jika bukan MAJOR atau MINOR → JANGAN SKIP (selalu kirim WA)
        if (!$ticket || !in_array(strtoupper($ticket->classification), ['MAJOR', 'MINOR'])) {
            return false;
        }

        // Hanya untuk MAJOR/MINOR baru cek status monitor
        $monitor = SiteMonitorCsv::where('site_id', function ($query) use ($sweeping_id) {
            $query->select('site_id')
                ->from('sweeping_tickets')
                ->where('sweeping_id', $sweeping_id);
        })
            ->first();

        if (!$monitor) {
            return false; // tidak ada data monitor → tetap kirim
        }

        return in_array($monitor->sensor_status, ['Online', 'AP1 Down', 'AP2 Down']);
    }

    private function markAsSiteOnline(SweepingTicketsFollowupLog $log): void
    {
        $fakeResponse = [
            'ack'     => 'site-online',
            'status'  => '200',
            'message' => 'Site currently online - no need to follow up'
        ];

        // Update SEMUA log untuk sweeping_id yang sama
        SweepingTicketsFollowupLog::where('sweeping_id', $log->sweeping_id)
            ->where('broadcast_session_id', $log->broadcast_session_id)
            ->where('status', 'pending')
            ->update([
                'status'        => 'read',
                'api_response'  => $fakeResponse,
                'last_attempt_at' => now(),
            ]);

        Log::info("Site online, skipped follow-up", [
            'sweeping_id' => $log->sweeping_id,
            'session_id'  => $log->broadcast_session_id
        ]);
    }
}
