<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SweepingTicketResource\Pages;
use App\Filament\Resources\SweepingTicketResource\RelationManagers;
use App\Models\AreaList;
use App\Models\BroadcastSession;
use App\Models\SweepingTicket;
use App\Models\SweepingTicketsFollowupLog;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\{Radio, Select, Textarea, Grid};
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Support\Facades\DB;


class SweepingTicketResource extends Resource
{
    protected static ?string $model = SweepingTicket::class;

    protected static ?string $navigationIcon = 'phosphor-broom-duotone';

    protected static ?string $navigationLabel = 'Sweeping Ticket';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'Sweeping Ticket';
    protected static ?string $modelLabel = 'Sweeping Ticket';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('created_at', '>=', Carbon::now()->subMonths(2));
    }

    public static function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['siteDetail']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('classification')
                    ->required()
                    ->maxLength(30),
                Forms\Components\Textarea::make('problem_classification')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cboss_tt')
                    ->maxLength(30),
                Forms\Components\Textarea::make('cboss_problem')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->orderBy('classification')->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('sweeping_id')
                    ->searchable()
                    ->label("Sweeping ID")
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('site_id')
                    ->copyable()
                    ->label("Site ID")
                    ->hidden(),

                Tables\Columns\TextColumn::make('siteDetail.site_name')
                    ->copyable()
                    ->label("Site Name")
                    ->limit(45)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('site_id', 'like', "%{$search}%")
                            ->orWhereHas('siteDetail', function (Builder $query) use ($search) {
                                $query->where('site_name', 'like', "%{$search}%");
                            });
                    })
                    ->description(fn($record): string => $record->siteDetail->site_id, 'above'),

                Tables\Columns\TextColumn::make('siteDetail.province')
                    ->label("Province")
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->description(fn($record): string => $record->siteDetail->area->area)
                    ->searchable(),

                Tables\Columns\TextColumn::make('classification')
                    ->badge()
                    ->label("Classification")
                    ->alignCenter()
                    ->color(function ($state) {
                        if ($state === "MAJOR") {
                            return 'danger';
                        }

                        if ($state === "MINOR") {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->formatStateUsing(fn($state) => ucfirst(strtolower($state)))
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label("Status")
                    ->description(fn($record) => $record->problem_classification
                        ? "{$record->problem_classification}"
                        : null)
                    ->searchable(),

                Tables\Columns\TextColumn::make('cboss_tt')
                    ->default("-")
                    ->label("CBOSS TT")
                    ->description(fn($record) => $record->cboss_problem
                        ? "{$record->cboss_problem}"
                        : null)
                    ->searchable(),

                Tables\Columns\TextColumn::make('followup_status')
                    ->label('Follow-up WA')
                    ->badge()
                    ->icon(fn(string $state): ?string => match ($state) {
                        'sent'      => 'heroicon-o-check-circle',
                        'delivered' => 'heroicon-o-check-circle',
                        'read'      => 'heroicon-o-envelope-open',
                        'pending'   => 'heroicon-o-clock',
                        'failed'    => 'heroicon-o-x-circle',
                        default     => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'sent', 'delivered', 'read' => 'success',
                        'pending'                   => 'warning',
                        'failed'                    => 'danger',
                        default                     => 'gray',
                    })
                    ->alignCenter()
                    ->description(fn($record) => $record->total_attempts > 0
                        ? "{$record->total_attempts} attempts"
                        : null)
                    ->default('Belum ada')
                    ->tooltip(function ($record) {
                        $latestLog = $record->followupLogs()->latest('id')->first();

                        if (!$latestLog) {
                            return 'auto follow-up not found';
                        }

                        if (in_array($latestLog->status, ['failed', 'sent', 'read'])) {
                            $response = $latestLog->api_response;

                            $status  = $response['status'] ?? $response['Status'] ?? $latestLog->status ?? '-';
                            $message = $response['message'] ?? $response['Message'] ?? $latestLog->error_message ?? '-';

                            return "{$status} | {$message}";
                        }

                        return match ($latestLog->status) {
                            'pending' => 'waiting queue to deliver',
                            default   => ucfirst($latestLog->status),
                        };
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('classification')
                //     ->label("Classification")
                //     ->native(false)
                //     ->options(fn() => SweepingTicket::query()->pluck('classification', 'classification')),

                Tables\Filters\SelectFilter::make('status')
                    ->label("Status")
                    ->native(false)
                    ->options(fn() => SweepingTicket::query()->pluck('status', 'status')),

                Tables\Filters\SelectFilter::make('area')
                    ->label("Area")
                    ->options(fn() => AreaList::all()->pluck('area', 'area'))
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('siteDetail.area', function (Builder $query) use ($data) {
                                $query->where('area', $data['value']);
                            });
                        }
                    }),

                Tables\Filters\SelectFilter::make('followup_status')
                    ->label('Status Follow-up WA')
                    ->native(false)
                    ->options([
                        'no_log'    => 'No Follow-up',
                        'pending'   => 'Pending',
                        'sent'      => 'Sent',
                        // 'delivered' => 'Delivered',
                        'read'      => 'Read',
                        'failed'    => 'Failed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'no_log') {
                            return $query->whereDoesntHave('followupLogs');
                        }

                        return $query->whereHas('followupLogs', function ($q) use ($data) {
                            $q->where('status', $data['value'])
                                ->whereRaw('id = (SELECT MAX(id) FROM sweeping_tickets_followup_logs WHERE sweeping_id = sweeping_tickets.sweeping_id)');
                        });
                    }),

                DateRangeFilter::make('created_at')
                    ->label('Date Created'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('live_broadcast')
                    ->label('Broadcast Management')
                    ->icon('phosphor-gear-duotone')
                    ->color('gray')
                    ->button()
                    ->modalHeading('Whatsapp Broadcast Management')
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([
                        \Filament\Forms\Components\View::make('filament.pages.broadcast-monitor')
                            ->viewData([
                                'sessions' => static::getActiveSessionsForModal()   // pakai static
                            ])
                    ])
                    ->action(fn() => null),   // tidak perlu action

                Action::make('auto_broadcast')
                    ->label('Start Auto-Broadcast')
                    ->icon('phosphor-broadcast-duotone')
                    ->modalHeading('Auto Broadcast Tickets')
                    ->modalDescription('Setup whatsapp follow-up sweeping ticket')
                    ->modalWidth('lg')
                    ->form([
                        Radio::make('area')
                            ->label('Select Area')
                            ->inline()
                            ->columnSpanFull()
                            ->options([
                                'Area 1' => 'Area 1',
                                'Area 2' => 'Area 2',
                                'Area 3' => 'Area 3',
                            ])
                            ->descriptions([
                                'Area 1' => 'Sumatera, Kepri, Jawa, Bali',
                                'Area 2' => 'Kalimantan, Sulawesi',
                                'Area 3' => 'Nusa Tenggara, Maluku, Papua',
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

                        Textarea::make('template_message')
                            ->label('Whatsapp Template Message')
                            ->rows(6)
                            ->required()
                            ->helperText("placeholder: {pic_name}, {site_id}, {site_name}, {province}")
                            ->placeholder("Selamat Pagi Bapak/Ibu {pic_name},\n{site_name} - {province} masih offline, Mohon dibantu menyalakan Wifi Bakti nya."),

                        Select::make('interval_minutes')
                            ->label('Sent Trigger Interval')
                            ->options([
                                5  => '5 min',
                                // 8  => '8 min',
                                10 => '10 min',
                                // 12 => '12 min',
                                15 => '15 min',
                                20 => '20 min',
                            ])
                            ->native(false)
                            ->default(10)
                            ->required(),
                    ])
                    ->action(fn(array $data) => static::createBroadcastSession($data))
                    ->successNotificationTitle('Broadcast Session berhasil dibuat!')
            ])
            ->heading("Mahaga Sweeping Tickets")
            ->description("Sweeping Major Minor Warning Site - Network Operation Center. ")
            ->emptyStateHeading('No Sweeping Ticket yet')
            ->emptyStateDescription('Once you have been import Sweeping Ticket, it will appear here.')
            ->emptyStateIcon('phosphor-ticket-duotone')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->deferLoading()
            ->poll(null)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSweepingTickets::route('/'),
            // 'create' => Pages\CreateSweepingTicket::route('/create'),
            // 'edit' => Pages\EditSweepingTicket::route('/{record}/edit'),
        ];
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

    // === METHOD STATIC UNTUK MODAL LIVE BROADCAST ===
    public static function getActiveSessionsForModal()
    {
        return BroadcastSession::withCount([
            'logs as sent_count' => fn($query) => $query->whereNot('status', 'pending'),
            'logs as failed_count' => fn($query) => $query->where('status', 'failed'),
        ])
            ->whereIn('status', ['active', 'paused'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                $session->is_expired = $session->started_at
                    ? Carbon::parse($session->started_at)->diffInHours(now()) >= 24
                    : false;
                return $session;
            });
    }

    // === CREATE BROADCAST SESSION (Static) ===
    public static function createBroadcastSession(array $data)
    {
        DB::beginTransaction();

        try {
            $numberKeyMap = [
                'Area 1' => env('WATZAP_AREA_1', 'BgHCbXKufj1ldJGG'),
                'Area 2' => env('WATZAP_AREA_2', 'Bi32gocKKZSctrak'),
                'Area 3' => env('WATZAP_AREA_3', 'RzdiWcizrpDgR6w0'),
            ];

            $number_key = $numberKeyMap[$data['area']] ?? null;

            $tickets = self::getSitesByFilterStatic($data['area'], $data['classification']);

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
                $pics = self::getAllPicsFromTicketStatic($ticket);

                foreach ($pics as $pic) {
                    $finalMessage = self::renderTemplateStatic($data['template_message'], $ticket, $pic);
                    $finalMessage = str_replace(['\\n', '\n'], "\n", $finalMessage);

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

            \Filament\Notifications\Notification::make()
                ->title('Success')
                ->body("Broadcast Session untuk area {$data['area']} berhasil dibuat dengan {$session->total_logs} pesan.")
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

    // Get Sites By Filter (Static)
    private static function getSitesByFilterStatic(string $area, string $classification)
    {
        $todayStart = Carbon::now()->startOfDay();

        return SweepingTicket::query()
            ->where('classification', $classification)
            ->whereNot('status', 'CLOSED')
            ->where('created_at', '>=', $todayStart)
            ->whereHas('siteDetail.area', fn($q) => $q->where('area', $area))
            ->with(['siteDetail', 'haloBaktiTicket', 'cbossTmo'])
            ->get();
    }

    // Get All Pics
    private static function getAllPicsFromTicketStatic($ticket): array
    {
        $pics = [];

        // SiteDetail
        if ($ticket->siteDetail) {
            self::addNormalizedPicStatic($pics, $ticket->siteDetail->pic_name ?? 'PIC Site', $ticket->siteDetail->pic_number ?? '');
        }

        // HaloBakti (latest)
        if ($ticket->haloBaktiTicket?->count() > 0) {
            $latest = $ticket->haloBaktiTicket->sortByDesc('created_at')->first();
            if ($latest) {
                self::addNormalizedPicStatic($pics, $latest->pic_name ?? 'PIC HaloBakti', $latest->pic_phone ?? $latest->pic_number ?? '');
            }
        }

        // CbossTmo (latest + multiple phone)
        if ($ticket->cbossTmo?->count() > 0) {
            $latest = $ticket->cbossTmo->sortByDesc('created_at')->first();
            if ($latest) {
                $rawPhone = $latest->pic_phone ?? $latest->pic_number ?? '';
                $picName  = $latest->pic_name ?? 'PIC Cboss';
                $phoneList = preg_split('/[|\/]+/', $rawPhone);

                foreach ($phoneList as $phone) {
                    self::addNormalizedPicStatic($pics, $picName, trim($phone));
                }
            }
        }

        return collect($pics)->unique('phone')->values()->all();
    }

    private static function addNormalizedPicStatic(array &$pics, string $name, string $rawPhone): void
    {
        if (empty($rawPhone)) return;

        $phone = self::normalizePhoneNumberStatic($rawPhone);

        if (strlen($phone) < 10 || strlen($phone) > 13) return;

        $pics[] = ['name' => $name, 'phone' => $phone];
    }

    private static function normalizePhoneNumberStatic(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace(['+', ' ', '-', '(', ')', '.'], '', $phone);

        if (str_starts_with($phone, '62')) $phone = '0' . substr($phone, 2);
        if (str_starts_with($phone, '8')) $phone = '0' . $phone;

        return $phone;
    }

    private static function renderTemplateStatic(string $template, $ticket, array $pic): string
    {
        $message = str_replace([
            '{pic_name}',
            '{site_id}',
            '{site_name}',
            '{province}'
        ], [
            $pic['name'] ?? 'PIC',
            $ticket->site_id ?? '',
            $ticket->siteDetail?->site_name ?? '',
            $ticket->siteDetail?->province ?? '',
        ], $template);

        return str_replace(['\\n', '\n'], "\n", $message);
    }
}
