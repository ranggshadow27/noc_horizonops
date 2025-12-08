<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckOssCache extends Command
{
    protected $signature = 'cache:oss';
    protected $description = 'Cek status cache token OSS';

    public function handle()
    {
        $key = 'oss_token_validation';

        if (!Cache::has($key)) {
            $this->error('Cache token OSS KOSONG / BELUM PERNAH DI-SET');
            return;
        }

        $data = Cache::get($key);

        $this->info('Cache token OSS DITEMUKAN!');
        $this->table(
            ['Key', 'Value'],
            [
                ['Cache Key', $key],
                ['API Token (preview)', substr($data['api_token'] ?? '-', 0, 15) . '...'],
                ['Expired At', $data['expired_at'] ?? '-'],
                ['ENV Token (preview)', substr(env('OSS_TOKEN', '-'), 0, 15) . '...'],
                ['Cache Driver', config('cache.default')],
                ['Sekarang', now('Asia/Jakarta')->format('Y-m-d H:i:s')],
            ]
        );

        $expiredAt = \Carbon\Carbon::parse($data['expired_at'] ?? now());
        $sisaMenit = now()->diffInMinutes($expiredAt, false);

        if ($sisaMenit > 0) {
            $this->info("Token masih valid selama ±{$sisaMenit} menit lagi");
        } else {
            $this->error("Token SUDAH EXPIRED! (telat " . abs($sisaMenit) . " menit)");
        }
    }
}
