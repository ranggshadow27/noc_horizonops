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
use Filament\Resources\Components\Tab;

class ListSweepingTickets extends ListRecords
{
    protected static string $resource = SweepingTicketResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Show All'), // Menampilkan total semua ticket

            'major' => Tab::make('Major')
                ->icon('phosphor-warning-duotone') // Opsional: tambah ikon
                ->modifyQueryUsing(fn(Builder $query) => $query->where('classification', 'MAJOR')->where('status', '!=', 'CLOSED'))
                ->badge($this->getModel()::query()->where('classification', 'MAJOR')->count())
                ->badgeColor('danger'),

            'minor' => Tab::make('Minor')
                ->icon('phosphor-warning-circle-duotone')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('classification', 'MINOR')->where('status', '!=', 'CLOSED'))
                ->badge($this->getModel()::query()->where('classification', 'MINOR')->count())
                ->badgeColor('warning'),

            'warning' => Tab::make('Warning')
                ->icon('phosphor-bell-duotone')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('classification', 'WARNING'))
                ->badge($this->getModel()::query()->where('classification', 'WARNING')->count())
                ->badgeColor('gray'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('live_broadcast')
                ->label('Live Broadcast')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('Live Broadcast Monitor')
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    \Filament\Forms\Components\View::make('filament.pages.broadcast-monitor')
                        ->viewData([
                            'sessions' => $this->getActiveSessions()
                        ])
                ])
                ->action(fn() => null),   // tidak perlu action

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
                            ->native(false)
                            ->required(),
                    ]),

                    Textarea::make('template_message')
                        ->label('Template Pesan WA')
                        ->rows(6)
                        ->required()
                        ->helperText("placeholder: {pic_name}, {site_id}, {site_name}, {province}")
                        ->placeholder("Selamat Pagi Bapak/Ibu {pic_name},\n{site_name} - {province} masih offline, Mohon dibantu menyalakan Wifi Bakti nya."),

                    Select::make('interval_minutes')
                        ->label('Interval Kirim')
                        ->options([
                            5  => '5 menit',
                            8  => '8 menit',
                            10 => '10 menit',
                            12 => '12 menit',
                            15 => '15 menit',
                        ])
                        ->native(false)
                        ->default(10)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->createBroadcastSession($data);
                })
                ->successNotificationTitle('Broadcast Session berhasil dibuat!')
        ];
    }

    private function getActiveSessions()
    {
        return BroadcastSession::withCount([
            'logs as sent_count' => function ($query) {
                $query->whereNot('status', 'pending');
            },
            'logs as failed_count' => function ($query) {
                $query->where('status', 'failed');
            }
        ])
            ->whereIn('status', ['active', 'paused'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                // Tambahkan attribute dynamic untuk cek apakah sudah lewat 1 hari (24 jam)
                $session->is_expired = $session->started_at
                    ? Carbon::parse($session->started_at)->diffInHours(now()) >= 24
                    : false;

                return $session;
            });
    }

    public function pauseSession($id)
    {
        BroadcastSession::where('id', $id)->update(['status' => 'paused']);
        $this->js('window.location.reload()'); // refresh modal
    }

    public function resumeSession($id)
    {
        BroadcastSession::where('id', $id)->update(['status' => 'active']);
        $this->js('window.location.reload()');
    }

    public function stopSession($id)
    {
        BroadcastSession::where('id', $id)->update([
            'status' => 'stopped',
            'completed_at' => now()
        ]);
        $this->js('window.location.reload()');
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
                'Area 1' => env('WATZAP_AREA_1', 'BgHCbXKufj1ldJGG'),
                'Area 2' => env('WATZAP_AREA_2', 'Bi32gocKKZSctrak'),
                'Area 3' => env('WATZAP_AREA_3', 'RzdiWcizrpDgR6w0'),
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
            $area = $data['area'] ?? 'N/A';

            \Filament\Notifications\Notification::make()
                ->title('Success')
                ->body("Broadcast Session untuk area {$area} berhasil dibuat dengan {$session->total_logs} auto follow-up pesan.")
                ->success()
                ->send();
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

        // ======================
        // 1. Dari SiteDetail (hanya 1)
        // ======================
        if ($ticket->siteDetail) {
            $this->addNormalizedPic(
                $pics,
                $ticket->siteDetail->pic_name ?? 'PIC Site',
                $ticket->siteDetail->pic_number ?? ''
            );
        }

        // ======================
        // 2. Dari HaloBaktiTicket → ambil yang paling baru (latest)
        // ======================
        if ($ticket->haloBaktiTicket && $ticket->haloBaktiTicket->count() > 0) {
            $latestHb = $ticket->haloBaktiTicket->sortByDesc('created_at')->first();

            if ($latestHb) {
                $this->addNormalizedPic(
                    $pics,
                    $latestHb->pic_name ?? 'PIC HaloBakti',
                    $latestHb->pic_phone ?? $latestHb->pic_number ?? ''
                );
            }
        }

        // ======================
        // 3. Dari CbossTmo → ambil yang paling baru + support multiple nomor ( | atau / )
        // ======================
        if ($ticket->cbossTmo && $ticket->cbossTmo->count() > 0) {
            $latestCb = $ticket->cbossTmo->sortByDesc('created_at')->first();

            if ($latestCb) {
                $rawPhone = $latestCb->pic_phone ?? $latestCb->pic_number ?? '';
                $picName  = $latestCb->pic_name ?? 'PIC Cboss';

                // Handle multiple numbers dipisah | atau /
                $phoneList = preg_split('/[|\/]+/', $rawPhone);

                foreach ($phoneList as $phone) {
                    $this->addNormalizedPic($pics, $picName, trim($phone));
                }
            }
        }

        // ======================
        // Hapus duplikat berdasarkan nomor
        // ======================
        $uniquePics = collect($pics)
            ->unique('phone')
            ->values()
            ->all();

        return $uniquePics;
    }

    // Helper untuk normalisasi + validasi nomor
    private function addNormalizedPic(array &$pics, string $name, string $rawPhone): void
    {
        if (empty($rawPhone)) {
            return;
        }

        $phone = $this->normalizePhoneNumber($rawPhone);

        // Validasi: minimal 10 digit, maksimal 13 digit
        if (strlen($phone) < 10 || strlen($phone) > 13) {
            return; // skip nomor yang tidak valid
        }

        $pics[] = [
            'name'  => $name,
            'phone' => $phone,
        ];
    }

    // Normalisasi nomor
    private function normalizePhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace(['+', ' ', '-', '(', ')', '.'], '', $phone);

        // Ganti 62 jadi 0
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        // Kalau mulai dengan 8, tambah 0
        if (str_starts_with($phone, '8')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    private function renderTemplate(string $template, $ticket, array $pic): string
    {
        return str_replace([
            '{pic_name}',
            '{site_id}',
            '{site_name}',
            '{province}',
        ], [
            $pic['name'] ?? 'PIC',
            $ticket->site_id ?? '',
            $ticket->siteDetail?->site_name ?? '',
            $ticket->siteDetail?->province ?? '',
        ], $template);
    }

    // Query utama
    private function getSitesByFilter(string $area, string $classification)
    {
        $todayStart = Carbon::now()->startOfDay();   // 00:00 hari ini

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
