<?php

namespace App\Console\Commands;

use App\Models\BroadcastSession;
use App\Models\SweepingTicketsFollowupLog;
use App\Jobs\SendWaFollowupJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SweepingProcessFollowup extends Command
{
    protected $signature = 'sweeping:process-followup';
    protected $description = 'Proses auto broadcast WA follow-up setiap interval';

    public function handle()
    {
        $this->info('🚀 Starting Sweeping Follow-up Processor...');

        $sessions = BroadcastSession::where('status', 'active')
            ->whereNotNull('number_key')
            ->get();

        $totalProcessed = 0;

        foreach ($sessions as $session) {
            if (!$this->isTimeToProcess($session)) {
                continue;
            }

            // Ambil 1 sweeping_id yang masih ada log pending
            $pendingLog = SweepingTicketsFollowupLog::where('broadcast_session_id', $session->id)
                ->where('status', 'pending')
                ->orderBy('sweeping_id')
                ->first();

            if (!$pendingLog) {
                $this->checkIfSessionCompleted($session);
                continue;
            }

            // Ambil SEMUA log pending untuk sweeping_id tersebut
            $logsToProcess = SweepingTicketsFollowupLog::where('broadcast_session_id', $session->id)
                ->where('sweeping_id', $pendingLog->sweeping_id)
                ->where('status', 'pending')
                ->get();

            $this->info("📤 Processing Site: {$pendingLog->sweeping_id} | Jumlah PIC: {$logsToProcess->count()}");

            foreach ($logsToProcess as $log) {
                SendWaFollowupJob::dispatch($log->id);
                $totalProcessed++;
            }

            // Update timestamp session
            $session->update(['last_processed_at' => now()]);
        }

        $this->info("✅ Selesai. Total pesan diproses: {$totalProcessed}");
    }

    private function isTimeToProcess(BroadcastSession $session): bool
    {
        if (!$session->last_processed_at) {
            return true; // pertama kali langsung jalan
        }

        $minutesSinceLast = now()->diffInMinutes($session->last_processed_at);

        // Tambah sedikit tolerance (±1 menit)
        return $minutesSinceLast >= $session->interval_minutes;
    }

    private function checkIfSessionCompleted(BroadcastSession $session): void
    {
        $pendingCount = SweepingTicketsFollowupLog::where('broadcast_session_id', $session->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount === 0) {
            $session->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            $failedCount = SweepingTicketsFollowupLog::where('broadcast_session_id', $session->id)
                ->where('status', 'failed')
                ->count();

            if ($failedCount > 0) {
                $this->info("🏁 Session {$session->name} selesai dengan {$failedCount} failed.");
            } else {
                $this->info("🏁 Session {$session->name} selesai 100% sukses.");
            }
        }
    }
}
