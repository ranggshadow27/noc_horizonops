<?php

namespace App\Filament\Resources\SweepingTicketResource\Pages;

use App\Filament\Resources\SweepingTicketResource;
use App\Models\BroadcastSession;
use App\Models\SweepingTicket;
use App\Models\SweepingTicketsFollowupLog;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\{Radio, Select, Textarea, Grid};
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\DB;

class ListSweepingTickets extends ListRecords
{
    protected static string $resource = SweepingTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('auto_broadcast')
                ->label('Auto Broadcast')
                ->icon('heroicon-o-megaphone')
                ->modalHeading('Auto Broadcast Follow-up')
                ->modalDescription('Setup follow-up sweeping ticket')
                ->modalWidth('lg')
                ->form([
                    Grid::make(2)->schema([
                        Radio::make('area')
                            ->label('Pilih Area')
                            ->options([
                                'Area 1' => 'Area 1',
                                'Area 2' => 'Area 2',
                                'Area 3' => 'Area 3',
                            ])
                            ->required(),

                        Select::make('classification')
                            ->label('Classification')
                            ->options([
                                'MAJOR' => 'MAJOR',
                                'MINOR' => 'MINOR',
                            ])
                            ->required(),
                    ]),

                    Textarea::make('template_message')
                        ->label('Template Pesan WA')
                        ->rows(6)
                        ->required()
                        ->placeholder("Halo {pic_name},\nSite {site_id} masih offline...\nMohon dibantu follow up-nya."),

                    Select::make('interval_minutes')
                        ->label('Interval Kirim')
                        ->options([
                            8  => '8 menit',
                            10 => '10 menit',
                            12 => '12 menit',
                            15 => '15 menit',
                        ])
                        ->default(10)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->createBroadcastSession($data);
                })
                ->successNotificationTitle('Broadcast Session berhasil dibuat!')
        ];
    }

    // Helper: Ambil site berdasarkan area
    private function getSitesByArea(string $area)
    {
        return SweepingTicket::query()
            ->whereHas('siteDetail', function ($q) use ($area) {
                $q->whereHas('area', function ($q2) use ($area) {
                    $q2->where('area', $area); // sesuaikan kolom lo
                });
            })
            ->where('status', '!=', 'resolved') // contoh filter
            ->with(['siteDetail'])
            ->get();
    }

    // Logic utama Create Session + Logs
    private function createBroadcastSession(array $data)
    {
        DB::beginTransaction();

        try {
            $numberKeyMap = [
                'Area 1' => 'BgHCbXKufj1ldJGG',   // GANTI dengan number_key asli lo
                'Area 2' => 'Bi32gocKKZSctrak',
                'Area 3' => 'azswas',
            ];

            $number_key = $numberKeyMap[$data['area']] ?? null;

            // Ambil data ticket sesuai filter
            $tickets = $this->getSitesByFilter($data['area'], $data['classification']);

            if ($tickets->isEmpty()) {
                throw new \Exception('Tidak ada data Sweeping Ticket yang sesuai filter.');
            }

            $session = BroadcastSession::create([
                'name'              => "Followup {$data['area']} - {$data['classification']} - " . now()->format('d M Y H:i'),
                'area'              => $data['area'],
                'number_key'        => $number_key,
                'template_message'  => $data['template_message'],
                'interval_minutes'  => $data['interval_minutes'],
                'status'            => 'active',
                'started_at'        => now(),
                'created_by'        => auth()->id(),
            ]);

            $logs = [];

            foreach ($tickets as $ticket) {
                $pics = $this->getAllPicsFromTicket($ticket);

                foreach ($pics as $pic) {
                    $finalMessage = $this->renderTemplate($data['template_message'], $ticket, $pic);

                    $logs[] = [
                        'broadcast_session_id' => $session->id,
                        'sweeping_id'          => $ticket->sweeping_id,
                        'number_key'           => $number_key,
                        'pic_phone'            => $pic['phone'],
                        'pic_name'             => $pic['name'] ?? 'PIC',
                        'message'              => $finalMessage,
                        'status'               => 'pending',
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }
            }

            if (!empty($logs)) {
                SweepingTicketsFollowupLog::insert($logs);
                $session->update(['total_logs' => count($logs)]);
            }

            DB::commit();
            // \Filament\Notifications\Notification::make()
            //     ->title('Success')
            //     ->body("Berhasil input data area {$data['area']} dengan total {count($logs)}")
            //     ->success()
            //     ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            \Filament\Notifications\Notification::make()
                ->title('Gagal Membuat Broadcast')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getAllPicsFromTicket($ticket): array
    {
        $pics = [];

        // 1. Dari SiteDetails
        if ($ticket->siteDetail) {
            $phone = $this->normalizePhoneNumber($ticket->siteDetail->pic_number ?? '');
            if (!empty($phone) && strlen($phone) >= 10) {
                $pics[] = [
                    'name'  => $ticket->siteDetail->pic_name ?? 'PIC',
                    'phone' => $phone,
                ];
            }
        }

        // 2. Dari HaloBaktiTicket (hasMany)
        if ($ticket->haloBaktiTicket && $ticket->haloBaktiTicket->count() > 0) {
            foreach ($ticket->haloBaktiTicket as $hb) {
                $phone = $this->normalizePhoneNumber($hb->pic_phone ?? $hb->pic_number ?? '');
                if (!empty($phone) && strlen($phone) >= 10) {
                    $pics[] = [
                        'name'  => $hb->pic_name ?? 'PIC',
                        'phone' => $phone,
                    ];
                }
            }
        }

        // 3. Dari CbossTmo (hasMany)
        if ($ticket->cbossTmo && $ticket->cbossTmo->count() > 0) {
            foreach ($ticket->cbossTmo as $cb) {
                $phone = $this->normalizePhoneNumber($cb->pic_phone ?? $cb->pic_number ?? '');
                if (!empty($phone) && strlen($phone) >= 10) {
                    $pics[] = [
                        'name'  => $cb->pic_name ?? 'PIC',
                        'phone' => $phone,
                    ];
                }
            }
        }

        // Hapus duplikat berdasarkan nomor telepon
        $uniquePics = collect($pics)
            ->unique('phone')
            ->values()
            ->all();

        return $uniquePics;
    }

    private function renderTemplate(string $template, $ticket, array $pic): string
    {
        return str_replace([
            '{pic_name}',
            '{site_id}',
            '{site_name}',
        ], [
            $pic['name'] ?? 'PIC',
            $ticket->site_id ?? '',
            $ticket->siteDetail->site_name ?? $ticket->site_id ?? '',
        ], $template);
    }

    // Normalisasi nomor WA Indonesia
    private function normalizePhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace(['+', ' ', '-', '(', ')'], '', $phone); // bersihkan karakter aneh

        // Kalau diawali 62, ganti jadi 0
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        // Kalau diawali 8 (tanpa 0), tambahin 0 di depan
        if (str_starts_with($phone, '8')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    // Refresh site options
    private function refreshSiteOptions(callable $set, ?string $area, ?string $classification)
    {
        if ($area && $classification) {
            $sites = $this->getSitesByFilter($area, $classification);
            $set('sites', $sites->pluck('sweeping_id')->toArray());
        } else {
            $set('sites', []);
        }
    }

    // Ambil site options untuk dropdown
    private function getSiteOptions(?string $area, ?string $classification)
    {
        if (!$area || !$classification) return [];

        return $this->getSitesByFilter($area, $classification)
            ->pluck('site_id', 'sweeping_id')
            ->toArray();
    }

    // Query utama
    private function getSitesByFilter(string $area, string $classification)
    {
        $todayStart = Carbon::yesterday()->startOfDay();   // 00:00 hari ini

        return SweepingTicket::query()
            ->where('classification', $classification)
            ->whereNot('status', 'CLOSED')               // sesuaikan kalau nama statusnya beda
            ->where('created_at', '>=', $todayStart)
            ->whereHas('siteDetail.area', function ($q) use ($area) {
                $q->where('area', $area);            // sesuaikan nama kolom province/area lo
            })
            ->with([
                'siteDetail',           // penting untuk pic_name & pic_number
                'haloBaktiTicket',      // kalau hasMany
                'cbossTmo'              // kalau hasMany
            ])
            ->get();
    }
}
