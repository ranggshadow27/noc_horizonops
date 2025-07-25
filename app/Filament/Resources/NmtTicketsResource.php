<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NmtTicketsResource\Pages;
use App\Filament\Resources\NmtTicketsResource\RelationManagers;
use App\Filament\Resources\NmtTicketsResource\Widgets\NmtTicketsResourceOverview;
use App\Models\AreaList;
use App\Models\NmtTickets;
use App\Models\SiteDetail;
use App\Models\SiteMonitor;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;

class NmtTicketsResource extends Resource
{
    protected static ?string $model = NmtTickets::class;

    protected static ?string $navigationLabel = 'NMT Ticket';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'NMT Ticket';
    protected static ?string $modelLabel = 'NMT Ticket';

    protected static ?string $navigationIcon = 'phosphor-tag-chevron-duotone';
    protected static ?int $navigationSort = 1;

    public static function getWidgets(): array
    {
        return [
            NmtTicketsResourceOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return NmtTickets::where('status', "OPEN")
            ->whereHas('siteMonitor', function ($query) {
                $query->where('modem_last_up', '=', null)->orWhere('modem_last_up', '>=', now()->subDay());
            })
            ->count(); // Hitung total data
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('date_start')
                    ->required(),
                Forms\Components\TextInput::make('aging')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('problem_classification')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('problem_detail')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('problem_type')
                    ->required()
                    ->maxLength(20),
                Forms\Components\Textarea::make('update_progress')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['site']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->orderByDesc('status')->orderByDesc('aging'))
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label("Ticket ID")
                    ->copyable()
                    ->description(fn($record): string => "Target Online: " . Carbon::parse($record->target_online)->format("d M Y"))
                    ->searchable(),

                Tables\Columns\TextColumn::make('cboss_tt')
                    ->label('CBOSS TT')
                    ->hidden(),

                Tables\Columns\TextColumn::make('site_id')
                    ->label('Site ID')
                    ->hidden(),

                Tables\Columns\TextColumn::make('site.site_name')
                    ->copyable()
                    ->label("Site Name")
                    ->limit(30)
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
                            ->orWhereHas('site', function (Builder $query) use ($search) {
                                $query->where('site_name', 'like', "%{$search}%");
                            });
                    })
                    ->description(fn($record): string => $record->site->site_id, 'above'),

                Tables\Columns\TextColumn::make('site_province')
                    ->label("Province")
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->description(fn($record): string => $record->area->area)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label("Status")
                    ->badge()
                    ->color(function ($state) {
                        if (str_contains($state, "OPEN")) {
                            return "warning";
                        } elseif (str_contains($state, "CLOSE")) {
                            return 'success';
                        } else {
                            return 'gray';
                        }
                    })
                    ->formatStateUsing(function ($state) {
                        $data = $state;

                        if (str_contains($data, "OPEN")) {
                            $data = "OPEN";
                        } else if (str_contains($data, "CLOSE")) {
                            $data = "CLOSED";
                        }

                        return Str::title($data);
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_start')
                    ->label("Date Start TT")
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format("d M Y"))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('aging')
                    ->label("Aging")
                    ->formatStateUsing(fn($state) => $state > 1 ? $state . " days" : $state . " day")
                    ->tooltip(
                        fn($record) => "Date Start : " . Carbon::parse($record->date_start)->translatedFormat('d M Y')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_online')
                    ->label('Target Online')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format("d M Y"))
                    ->hidden(),

                Tables\Columns\TextColumn::make('closed_date')
                    ->label('Closed Date')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format("d M Y"))
                    ->hidden(),

                Tables\Columns\TextColumn::make('siteMonitor.modem_last_up')
                    ->label('Modem Last Up')
                    ->default("Online")
                    ->badge()
                    ->color(function ($state) {
                        if ($state === "Online") {
                            return 'success'; // Jika "Up", warna hijau (success)
                        }

                        $modemTime = Carbon::parse($state);
                        $now = Carbon::now();

                        // Jika selisih kurang dari atau sama dengan 3 hari → success (hijau)
                        // Jika lebih dari 3 hari → danger (merah)
                        return $modemTime->diffInHours($now) <= 6 ? 'success' : 'gray';
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state === "Online") {
                            return "Online";
                        }

                        Carbon::setLocale('en');

                        return Carbon::parse($state)
                            ->diffForHumans();
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('siteMonitor.sensor_status')
                    ->label('Sensor Status')
                    ->default("Online")
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('problem_classification')
                    ->label("Problem Classification")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('problem_detail')
                    ->label("Problem Detail")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('problem_type')
                    ->label("Type")
                    ->badge()->color(fn(string $state): string => match ($state) {
                        'TEKNIS' => 'primary',
                        'NON TEKNIS' => 'secondary',
                        'Belum Ada Info' => 'danger',
                    })
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label("Status")
                    ->native(false)
                    // ->multiple()
                    ->options(fn() => NmtTickets::query()->pluck('status', 'status')),

                Tables\Filters\SelectFilter::make('problem_classification')
                    ->label("Problem Classification")
                    ->native(false)
                    ->multiple()
                    ->options(fn() => NmtTickets::query()->pluck('problem_classification', 'problem_classification')),

                Tables\Filters\SelectFilter::make('aging')
                    ->label('Aging/Duration')
                    ->options([
                        'warning' => '≤ 3 days',
                        'minor' => '> 4-7 days',
                        'major' => '> 8-14 days',
                        'critical' => '> 14-30 days',
                        'sp1' => '> 30 days',
                    ])
                    ->native(false)
                    ->multiple()
                    ->modifyQueryUsing(function (Builder $query, array $state) {
                        if (!isset($state['values']) || empty($state['values'])) {
                            return $query; // Jika tidak ada filter yang dipilih, kembalikan query tanpa filter
                        }

                        return $query->whereHas('siteMonitor', function (Builder $query) use ($state) {
                            $query->where(function (Builder $subQuery) use ($state) {
                                foreach ($state['values'] as $value) {
                                    if ($value === 'warning') {
                                        $subQuery->orWhere('aging', '<=', 3);
                                    } elseif ($value === 'minor') {
                                        $subQuery->orWhere(function (Builder $q) {
                                            $q->where('aging', '>=', 4)->where('aging', '<=', 7);
                                        });
                                    } elseif ($value === 'major') {
                                        $subQuery->orWhere(function (Builder $q) {
                                            $q->where('aging', '>=', 8)->where('aging', '<=', 14);
                                        });
                                    } elseif ($value === 'critical') {
                                        $subQuery->orWhere(function (Builder $q) {
                                            $q->where('aging', '>=', 14)->where('aging', '<=', 30);
                                        });
                                    } elseif ($value === 'sp1') {
                                        $subQuery->orWhere('aging', '>', 30);
                                    }
                                }
                            });
                        });
                    }),

                Tables\Filters\SelectFilter::make('area')
                    ->label("Area")
                    ->options(fn() => AreaList::all()->pluck('area', 'area'))
                    ->native(false)
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }
                        return $query->whereHas('area', fn($query) => $query->where('area', $state['value']));
                    }),

                Tables\Filters\SelectFilter::make('modem_last_up')
                    ->label('Modem Last Up')
                    ->native(false)
                    ->options([
                        'now' => 'Up (Online)',
                        'recent' => '≤ 1 days ago',
                        'old' => '> 2 days ago',
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $state) {
                        if (!isset($state['value']) || empty($state['value'])) {
                            return $query; // Jika tidak ada filter yang dipilih, kembalikan query tanpa filter
                        }

                        return $query->whereHas('siteMonitor', function ($query) use ($state) {
                            if ($state['value'] === 'now') {
                                $query->whereNull('modem_last_up');
                            } elseif ($state['value'] === 'recent') {
                                $query->where('modem_last_up', '>=', now()->subDays(1))->orWhereNull('modem_last_up');
                            } elseif ($state['value'] === 'old') {
                                $query->where('modem_last_up', '<', now()->subDays(2));
                            }
                        });
                    }),

                Tables\Filters\SelectFilter::make('siteStatus')
                    ->label("Site Status")
                    ->native(false)
                    ->options(fn() => SiteMonitor::all()->pluck('status', 'status'))
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }
                        return $query->whereHas('siteMonitor', fn($query) => $query->where('status', $state['value']));
                    }),

                Tables\Filters\SelectFilter::make('sensorStatus')
                    ->label('Sensor Status')
                    ->native(false)
                    ->options(function () {
                        // Ambil semua sensor_status yang unik dari SiteMonitor, tambahkan 'Unknown Sensor Status'
                        $statuses = SiteMonitor::whereNotNull('sensor_status')
                            ->pluck('sensor_status', 'sensor_status')
                            ->toArray();

                        // Tambahkan 'Unknown Sensor Status' ke opsi
                        return $statuses + ['Unknown Sensor Status' => 'Unknown Sensor Status'];
                    })
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }

                        if ($state['value'] === 'Unknown Sensor Status') {
                            // Filter untuk record yang tidak punya relasi siteMonitor
                            return $query->whereHas('siteMonitor', function ($query) use ($state) {
                                $query->whereNull('sensor_status');
                            });
                        }

                        // Filter untuk status sensor tertentu
                        return $query->whereHas('siteMonitor', function ($query) use ($state) {
                            $query->where('sensor_status', $state['value']);
                        });
                    }),

                DateRangeFilter::make('target_online')
                    ->label('Target Online Date'),

                DateRangeFilter::make('date_start')
                    ->label('Start Date'),

                DateRangeFilter::make('closed_date')
                    ->label('Actual Online Date'),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->label("Details")
                    ->modalHeading("Ticket Detail"),
            ])
            ->headerActions([
                ActionGroup::make([
                    CopyAction::make('generate_priority_report')
                        ->label('Priority Report')
                        ->color('gray')
                        ->copyable(function ($livewire) {
                            return static::generatePriorityReport($livewire);
                        })
                        ->requiresConfirmation(false)
                        ->icon('phosphor-chat-teardrop-text-duotone'),

                    CopyAction::make('generate_report')
                        ->label('Report with Detail')
                        ->color('gray')
                        ->copyable(function ($livewire) {
                            return static::generateReportFromTable($livewire);
                        })
                        ->requiresConfirmation(false)
                        ->icon('phosphor-files'),

                    CopyAction::make('generate_report')
                        ->color('gray')
                        ->label('PMU Report')
                        ->successNotificationTitle('Report copied to clipboard')
                        ->copyable(function () {
                            return static::generateReportString();
                        })
                        ->icon('phosphor-file-txt-duotone'),
                ])->label('Generate Text Report')
                    ->icon('phosphor-circles-four-duotone')
                    ->size(ActionSize::Medium)
                    ->color('gray')
                    ->button(),

                Action::make('Import Data')
                    ->button()
                    ->label("Get Data from GSheet")
                    ->action(fn() => Artisan::call('fetch:nmt-tickets'))
                    ->requiresConfirmation()
                    ->successNotificationTitle('Data berhasil diimport')
                    ->icon('phosphor-plus-circle-duotone'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->poll('20s')
            ->deferLoading()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->heading("Mahaga NMT Tickets")
            ->description("Summary of BAKTI RTGS Trouble Tickets - Network Operation Center. ")
            ->emptyStateHeading('No NMT Ticket yet')
            ->emptyStateDescription('Once you have been import NMT Ticket, it will appear here.')
            ->emptyStateIcon('phosphor-ticket-duotone');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNmtTickets::route('/'),
            'create' => Pages\CreateNmtTickets::route('/create'),
            // 'edit' => Pages\EditNmtTickets::route('/{record}/edit'),
        ];
    }

    protected static function generatePriorityReport($livewire): string
    {
        Carbon::setLocale('id');
        $now = Carbon::now();
        $date = $now->translatedFormat('d F Y');

        $query = $livewire->getFilteredSortedTableQuery()
            ->with(['site'])
            ->where(function ($query) use ($now) {
                $query->where('status', 'OPEN')
                    ->orWhere(function ($query) use ($now) {
                        $query->where('status', 'CLOSED')
                            ->whereDate('closed_date', $now->startOfDay());
                    });
            })
            ->orderBy('aging', 'desc');

        $records = $query->get();

        $report = "Berikut TT Prioritas, tanggal {$date}:\n\n";
        $groupedRecords = $records->groupBy('aging')->sortKeysDesc();

        $counter = 1;
        foreach ($groupedRecords as $aging => $tickets) {
            $report .= "Aging {$aging} Hari\n";
            $report .= "=========\n";
            foreach ($tickets as $ticket) {
                $siteName = $ticket->site ? $ticket->site->site_name : 'Unknown';
                $statusEmoji = $ticket->status === 'OPEN' ? '❌' : '✅';
                $report .= "{$counter}. {$ticket->site_id} - {$siteName} {$statusEmoji}\n";
                $counter++;
            }
            $report .= "\n";
        }

        $report .= "Terimakasih";
        return $report;
    }

    protected static function generateReportFromTable($livewire): string
    {
        // Set Carbon locale to Indonesian
        Carbon::setLocale('id');

        // Get the current table query with filters and sorting
        $query = $livewire->getFilteredSortedTableQuery()->with(['site.cbossTicket' => function ($query) {
            $query->whereNot('status', 'Closed')->latest('updated_at')->take(1);
        }, 'area'])
            // ->where('status', '!=', 'Closed')
        ;

        $records = $query->get();

        // Split records into non-school holiday and school holiday
        $nonSchoolHolidayRecords = $records->filter(fn($record) => strtolower($record->problem_detail ?? '') !== 'libur sekolah');
        $schoolHolidayRecords = $records->filter(fn($record) => strtolower($record->problem_detail ?? '') === 'libur sekolah');

        // Calculate ticket counts per area (non-school holiday only)
        $areaCounts = $nonSchoolHolidayRecords->groupBy('area.area')->map->count()->toArray();
        $totalTickets = $nonSchoolHolidayRecords->count();

        // Sort area counts by area name (ascending)
        ksort($areaCounts);

        // Build the report string
        $now = Carbon::now();
        $report = "Dear All,\n\n";
        $report .= "Berikut Summary *TT OPEN* dengan durasi diatas 14hari (Overdue TT), " . $now->translatedFormat('d F Y') . ":\n\n";

        // Add area summary
        foreach ($areaCounts as $areaName => $count) {
            $areaName = $areaName ?? 'Unknown Area';
            $report .= "- {$areaName}: *{$count} Tiket*\n";
        }
        $report .= "- Overdue Total: *{$totalTickets} Tiket*\n\n";

        // Group non-school holiday records by area and sort by area name
        $groupedRecords = $nonSchoolHolidayRecords->groupBy('area.area')->sortKeys();

        foreach ($groupedRecords as $areaName => $areaRecords) {
            if ($areaRecords->isEmpty()) {
                continue; // Skip areas with no tickets
            }
            $areaName = $areaName ?? 'Unknown Area';
            $report .= "────────── ❌ TT Overdue *{$areaName}* ──────────\n";

            foreach ($areaRecords as $record) {
                $report .= "> {$record->site_id} - {$record->site->site_name} - {$record->site->province}\n";

                // Format target_online with Indonesian month
                $targetOnlineFormat = $record->target_online
                    ? Carbon::parse($record->target_online)->translatedFormat('d F Y')
                    : 'No target online set';
                if ($record->target_online && Carbon::parse($record->target_online)->isPast()) {
                    $targetOnlineFormat = "*{$targetOnlineFormat}* `‼️`";
                }
                $report .= "Target Online: {$targetOnlineFormat} | Aging `{$record->aging} Hari`\n";

                // Fetch the latest OPEN cboss_ticket
                $latestCbossTicket = $record->site ? $record->site->cbossTicket()->whereNot('status', 'Closed')->latest('updated_at')->first() : null;

                // Handle PO (already an array)
                $poData = $record->area && is_array($record->area->po) ? $record->area->po : [];
                $poString = !empty($poData) ? implode(', ', $poData) : 'No PO data';

                // Conditional logic for Nusa Tenggara Timur
                if ($record->site && strtolower($record->site->province) === 'nusa tenggara timur') {
                    $administrativeArea = strtolower($record->site->administrative_area ?? '');
                    $targetAreaAnjar = ['sumba'];
                    $targetAreaFirman = ['kupang', 'timor tengah', 'timur tengah', 'malaka', 'belu', 'rote', 'ndao', 'raijua', 'sabu', 'alor'];
                    $targetAreaNovan = ['manggarai', 'nagekeo', 'ngada', 'ende', 'sikka', 'flores', 'lembata', 'sika'];

                    // Check if administrative_area contains any targetAreaFirman
                    $isTargetAreaFirman = array_reduce($targetAreaFirman, fn($carry, $area) => $carry || stripos($administrativeArea, $area) !== false, false);
                    if ($isTargetAreaFirman) {
                        $filteredPo = array_filter($poData, fn($po) => $po === 'Firman');
                        $poString = !empty($filteredPo) ? implode(', ', $filteredPo) : 'No Firman-related PO found';
                    }

                    // Check if administrative_area contains any targetAreaAnjar
                    $isTargetAreaAnjar = array_reduce($targetAreaAnjar, fn($carry, $area) => $carry || stripos($administrativeArea, $area) !== false, false);
                    if ($isTargetAreaAnjar) {
                        $filteredPo = array_filter($poData, fn($po) => $po === 'Anjar');
                        $poString = !empty($filteredPo) ? implode(', ', $filteredPo) : 'No Anjar-related PO found';
                    }

                    // Check if administrative_area contains any targetAreaNovan
                    $isTargetAreaNovan = array_reduce($targetAreaNovan, fn($carry, $area) => $carry || stripos($administrativeArea, $area) !== false, false);
                    if ($isTargetAreaNovan) {
                        $filteredPo = array_filter($poData, fn($po) => $po === 'Novan');
                        $poString = !empty($filteredPo) ? implode(', ', $filteredPo) : 'No Novan-related PO found';
                    }
                }

                $cbossTT = $record->cboss_tt ?? 'NO TICKET FOUND';
                $detailProblem = $record->problem_detail ?? 'NO TICKET FOUND';

                // Add detail_action to the report
                $report .= "Problem : {$cbossTT} | *{$detailProblem}*\n";
                $report .= "*Update CBOSS*:\n" . ($latestCbossTicket ? $latestCbossTicket->detail_action : 'No open ticket found') . ", CC : @{$poString}\n\n";
            }
        }

        // Add Libur Sekolah section (if not empty)
        if ($schoolHolidayRecords->isNotEmpty()) {
            $schoolHolidayCount = $schoolHolidayRecords->count();
            $report .= "────────── ❕ Libur Sekolah (*{$schoolHolidayCount} Tickets*) ──────────\n";
            $schoolHolidayGrouped = $schoolHolidayRecords->groupBy('area.area')->sortKeys();
            foreach ($schoolHolidayGrouped as $areaName => $areaRecords) {
                foreach ($areaRecords as $record) {
                    $report .= "> {$record->site_id} - {$record->site->site_name} - {$record->site->province}\n";
                }
            }

            $report .= "\n";
        }

        $report .= "Terimakasih";

        return $report;
    }

    protected static function generateReportString(): string
    {
        // Ambil waktu saat ini
        Carbon::setLocale('id');

        // Ambil waktu saat ini
        $now = Carbon::now();
        $date = $now->translatedFormat('d F Y'); // Contoh: Minggu, 20 April 2025
        $timeOfDay = static::getTimeOfDay($now);

        // Query untuk mengelompokkan data
        $closed = NmtTickets::where('status', 'CLOSED')
            ->whereDate('closed_date', $now->startOfDay())
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $renovasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%renovasi%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $relokasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_classification', 'LIKE', '%relokasi%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $liburSekolah = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%libur%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $bencanaAlam = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%bencana%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $open = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'NOT LIKE', '%renovasi%')
            ->where('problem_classification', 'NOT LIKE', '%relokasi%')
            ->where('problem_detail', 'NOT LIKE', '%libur%')
            ->where('problem_detail', 'NOT LIKE', '%bencana%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        // Hitung total
        $totalClosed = $closed->count();
        $totalRenovasi = $renovasi->count();
        $totalRelokasi = $relokasi->count();
        $totalLiburSekolah = $liburSekolah->count();
        $totalBencanaAlam = $bencanaAlam->count();
        $totalOpen = $open->count();

        $totalTickets = $totalOpen + $totalClosed + $totalBencanaAlam + $totalLiburSekolah + $totalRelokasi + $totalRenovasi;

        // Buat string header report
        $report = "Selamat $timeOfDay,\n\n";
        $report .= "Berikut Update TT PT. MAHAGA PRATAMA $date:\n\n";
        $report .= "> CATEGORY : SL\n";
        $report .= "\n*Total Ticket* : $totalTickets\n\n";
        $report .= "* ✅ Closed\t\t\t: $totalClosed\t\n";

        // Tambahkan hanya kategori dengan jumlah > 0 ke summary
        if ($totalOpen > 0) {
            $report .= "* ❌ Open\t\t\t: $totalOpen\t\n";
        }
        if ($totalRenovasi > 0) {
            $report .= "* ⚠️ Renovasi\t\t: $totalRenovasi\t\n";
        }
        if ($totalRelokasi > 0) {
            $report .= "* 🚫 Relokasi\t\t: $totalRelokasi\t\n";
        }
        if ($totalLiburSekolah > 0) {
            $report .= "* ❕ Libur Sekolah\t: $totalLiburSekolah\t\n";
        }
        if ($totalBencanaAlam > 0) {
            $report .= "* ❗ Bencana Alam\t: $totalBencanaAlam\t\n";
        }

        $report .= "\n";

        // Detail per kategori
        if ($totalClosed > 0) {
            $report .= static::generateCategoryDetails('✅ TT CLOSED', $closed, true, false, '✅');
        }
        if ($totalOpen > 0) {
            $report .= static::generateCategoryDetails('❌ TT OPEN', $open, false, false, '❌');
        }
        if ($totalRenovasi > 0) {
            $report .= static::generateCategoryDetails('🚫 RELOKASI', $relokasi, false, true, '🚫');
        }
        if ($totalRelokasi > 0) {
            $report .= static::generateCategoryDetails('⚠️ RENOVASI', $renovasi, false, true, '⚠️');
        }
        if ($totalLiburSekolah > 0) {
            $report .= static::generateCategoryDetails('❕ LIBUR SEKOLAH', $liburSekolah, false, false, '❕');
        }
        if ($totalBencanaAlam > 0) {
            $report .= static::generateCategoryDetails('❗ BENCANA ALAM', $bencanaAlam, false, true, '❗');
        }

        $report .= "Terimakasih, CC: Pak @Dodo.";

        return $report;
    }

    protected static function getTimeOfDay(Carbon $time): string
    {
        $hour = $time->hour;
        if ($hour >= 4 && $hour < 11) return 'Pagi';
        if ($hour >= 11 && $hour < 15) return 'Siang';
        if ($hour >= 15 && $hour < 18) return 'Sore';
        return 'Malam';
    }

    protected static function generateCategoryDetails(string $title, $tickets, bool $isClosed, bool $isNoTargetOnline, string $emoji): string
    {
        $details = "========================================================\n\n$title :\n\n";

        Carbon::setLocale('id');
        foreach ($tickets as $ticket) {
            $siteName = $ticket->site ? $ticket->site->site_name : 'Unknown';
            $details .= "> {$ticket->site_id} - $siteName $emoji" . "\n";
            if ($isClosed) {
                $actualOnline = Carbon::parse($ticket->actual_online)->format('d F Y');
                $details .= "Actual Online\t: $actualOnline\n";
            } else if (!$isNoTargetOnline) {
                $details .= "Durasi Open\t: {$ticket->aging} Hari\n";
                $targetOnline = Carbon::parse($ticket->target_online)->format('d F Y');
                $details .= "Target Online\t: $targetOnline\n";
                $details .= "Progress\t\t: {$ticket->update_progress}\n";
            } else {
                $details .= "Durasi Open\t: {$ticket->aging} Hari\n";
                $details .= "Target Online\t: -\n";
                $details .= "Progress\t\t: {$ticket->update_progress}\n";
            }
            $details .= "\n";
        }

        return $details;
    }
}
