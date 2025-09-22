<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use App\Models\SiteDetail;
use App\Models\SiteLog;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class SummarySiteTable extends BaseWidget
{
    public $month;
    public $year;
    public $monthName;

    public function mount(): void
    {
        // Set default ke bulan dan tahun sekarang
        $this->month = $this->month ?? Carbon::now()->month;
        $this->year = $this->year ?? Carbon::now()->year;
    }

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    public function table(Table $table): Table
    {
        // Ambil nilai dari filter atau properti
        $selectedMonth = $this->filters['date_filter']['month'] ?? $this->month;
        $selectedYear = $this->filters['date_filter']['year'] ?? $this->year;

        // Hitung jumlah hari di bulan terpilih
        $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

        // Tentukan pembagi: hari ini untuk bulan sekarang, atau semua hari untuk bulan lalu
        $divider = Carbon::create($selectedYear, $selectedMonth, 1)->isCurrentMonth()
            ? min(Carbon::now()->day, $daysInMonth)
            : $daysInMonth;

        // Cek apakah bulan depan (atau masa depan)
        $isFutureMonth = Carbon::create($selectedYear, $selectedMonth, 1)->isFuture();

        // Query untuk ambil semua site dan modem_uptime dari SiteLog berdasarkan bulan/tahun terpilih
        $siteLogs = SiteLog::select('site_id', 'created_at', 'modem_uptime')
            ->whereYear('created_at', $selectedYear)
            ->whereMonth('created_at', $selectedMonth)
            ->get()
            ->groupBy(['site_id', function ($log) {
                return Carbon::parse($log->created_at)->day;
            }]);

        return $table
            ->query(
                SiteDetail::query()
                    ->with(['siteLogs' => function ($query) use ($selectedYear, $selectedMonth) {
                        $query->whereYear('created_at', $selectedYear)
                            ->whereMonth('created_at', $selectedMonth);
                    }])
            )
            ->columns([
                TextColumn::make('site_name')
                    ->label('Site Name')
                    ->sortable()
                    ->description(fn(SiteDetail $record): string => $record->site_id, position: 'above')
                    ->limit(22)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable(['site_id', 'site_name', 'province']),
                // Generate kolom dinamis untuk setiap tanggal di bulan terpilih
                ...collect(range(1, $daysInMonth))->map(function ($day) use ($selectedMonth, $selectedYear) {
                    return IconColumn::make("day_$day")
                        ->label(Carbon::create($selectedYear, $selectedMonth, $day)->format('d'))
                        ->getStateUsing(function (SiteDetail $record) use ($day) {
                            // Akses data dari relasi yang sudah di-eager load
                            $log = $record->siteLogs->first(function ($log) use ($day) {
                                return Carbon::parse($log->created_at)->day == $day;
                            });
                            return $log['modem_uptime'] ?? '-';
                        })
                        ->icon(function ($state) {
                            if ($state === '-' || $state === null) {
                                return 'phosphor-dot-duotone';
                            }
                            $uptime = (int) $state;

                            return match (true) {
                                $uptime >= 5 => 'phosphor-record-duotone',
                                $uptime > 2 && $uptime < 5 => 'phosphor-radio-button-duotone',
                                $uptime <= 2 => 'phosphor-x-circle-duotone',
                                default => 'phosphor-x-circle-duotone',
                            };

                            // return match (true) {
                            //     $uptime >= 5 => 'phosphor-arrow-circle-up-duotone',
                            //     $uptime > 2 && $uptime < 5 => 'phosphor-warning-circle-duotone',
                            //     $uptime <= 2 => 'phosphor-arrow-circle-down-duotone',
                            //     default => 'phosphor-dots-three-circle-duotone',
                            // };
                        })
                        ->tooltip(function ($state) {
                            if ($state !== '-') {
                                return "Uptime: " . $state . "/6";
                            }
                        })
                        ->color(function ($state) {
                            if ($state === '-' || $state === null) {
                                return null;
                            }
                            $uptime = (int) $state;
                            return match (true) {
                                $uptime >= 5 => 'success',
                                $uptime > 2 && $uptime < 5 => 'warning',
                                $uptime <= 2 => 'danger',
                                default => null,
                            };
                        });
                })->toArray(),
                // Kolom Online
                TextColumn::make('online_count')
                    ->label('Online')
                    ->getStateUsing(function (SiteDetail $record) use ($divider, $isFutureMonth) {
                        if ($isFutureMonth) {
                            return '0x';
                        }
                        // Akses data dari relasi yang sudah di-eager load
                        $logs = $record->siteLogs;
                        $onlineDays = $logs->filter(function ($log) {
                            $uptime = $log['modem_uptime'] ?? null;
                            return $uptime !== null && (int) $uptime > 2;
                        })->count();
                        if ($logs->isEmpty()) {
                            return "0x";
                        }
                        return "$onlineDays";
                    })
                    ->description(function (SiteDetail $record) use ($divider, $isFutureMonth) {
                        if ($isFutureMonth) {
                            return '0%';
                        }
                        $logs = $record->siteLogs;
                        $onlineDays = $logs->filter(function ($log) {
                            $uptime = $log['modem_uptime'] ?? null;
                            return $uptime !== null && (int) $uptime > 2;
                        })->count();
                        if ($logs->isEmpty()) {
                            return '0%';
                        }
                        $percentage = ($onlineDays / $divider) * 100;
                        return number_format($percentage, 1) . '%';
                    }, position: 'below')
                    ->color(function (SiteDetail $record) use ($siteLogs, $divider, $isFutureMonth) {
                        if ($isFutureMonth || $siteLogs->get($record->site_id, collect([]))->isEmpty()) {
                            return 'gray';
                        }
                        $logs = $siteLogs->get($record->site_id, collect([]));
                        $onlineDays = $logs->filter(function ($dayLogs) {
                            $uptime = $dayLogs->first()['modem_uptime'] ?? null;
                            return $uptime !== null && (int) $uptime > 2;
                        })->count();
                        $percentage = ($onlineDays / $divider) * 100;
                        return match (true) {
                            $percentage > 70 => 'success',
                            $percentage >= 30 && $percentage <= 70 => 'warning',
                            $percentage < 30 => 'danger',
                            default => 'gray',
                        };
                    })
                    ->icon(function (SiteDetail $record) use ($siteLogs, $divider, $isFutureMonth) {
                        if ($isFutureMonth || $siteLogs->get($record->site_id, collect([]))->isEmpty()) {
                            return 'phosphor-arrow-circle-down-duotone';
                        }
                        $logs = $siteLogs->get($record->site_id, collect([]));
                        $onlineDays = $logs->filter(function ($dayLogs) {
                            $uptime = $dayLogs->first()['modem_uptime'] ?? null;
                            return $uptime !== null && (int) $uptime > 2;
                        })->count();
                        $percentage = ($onlineDays / $divider) * 100;
                        return match (true) {
                            $percentage > 70 => 'phosphor-arrow-circle-up-duotone',
                            $percentage >= 30 && $percentage <= 70 => 'phosphor-warning-circle-duotone',
                            $percentage < 30 => 'phosphor-arrow-circle-down-duotone',
                            default => 'phosphor-arrow-circle-down-duotone',
                        };
                    }),

                // // Kolom Offline
                // TextColumn::make('offline_count')
                //     ->label('Offline')
                //     ->color('gray')
                //     ->icon('phosphor-arrow-circle-down-duotone')
                //     ->getStateUsing(function (SiteDetail $record) use ($siteLogs, $divider, $isFutureMonth) {
                //         if ($isFutureMonth) {
                //             return '0';
                //         }
                //         $logs = $siteLogs->get($record->site_id, collect([]));
                //         $offlineDays = $logs->filter(function ($dayLogs) {
                //             $uptime = $dayLogs->first()['modem_uptime'] ?? null;
                //             return $uptime !== null && (int) $uptime <= 2;
                //         })->count();
                //         if ($logs->isEmpty()) {
                //             return "0";
                //         }
                //         return "$offlineDays";
                //     })
                //     ->description(function (SiteDetail $record) use ($siteLogs, $divider, $isFutureMonth) {
                //         if ($isFutureMonth) {
                //             return '0%';
                //         }
                //         $logs = $siteLogs->get($record->site_id, collect([]));
                //         $offlineDays = $logs->filter(function ($dayLogs) {
                //             $uptime = $dayLogs->first()['modem_uptime'] ?? null;
                //             return $uptime !== null && (int) $uptime <= 2;
                //         })->count();
                //         if ($logs->isEmpty()) {
                //             return '0%';
                //         }
                //         $percentage = ($offlineDays / $divider) * 100;
                //         return number_format($percentage, 1) . '%';
                //     }, position: 'below'),
            ])
            ->filters([
                Filter::make('date_filter')
                    ->form([
                        Select::make('month')
                            ->native(false)
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(Carbon::now()->month)
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->month = $state;
                                $this->dispatch('refreshTable');
                            }),
                        Select::make('year')
                            ->native(false)
                            ->label('Tahun')
                            ->options(collect(range(2024, Carbon::now()->year))->mapWithKeys(fn($year) => [$year => $year]))
                            ->default(Carbon::now()->year)
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->year = $state;
                                $this->dispatch('refreshTable');
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        return $query;
                    }),
            ])
            ->headerActions([
                // Action::make('refresh')
                //     ->label('Refresh Data')
                //     ->icon('heroicon-o-arrow-path')
                //     ->action(function () {
                //         $this->dispatch('refreshTable');
                //     }),
                ActionGroup::make([
                    ExportAction::make('export_csv')
                        ->icon('phosphor-file-csv-duotone')
                        ->label("Export to CSV")
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                                ->withFilename(fn() => 'Summary_Site_' . Carbon::create($selectedYear, $selectedMonth, 1)->format('M_Y') . '.csv')
                                ->withChunkSize(50), // Turunin chunk size biar lebih aman
                        ]),

                    ExportAction::make('export_xlsx')
                        ->icon('phosphor-file-xls-duotone')
                        ->label("Export to XLSX")
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->withFilename(fn() => 'Summary_Site_' . Carbon::create($selectedYear, $selectedMonth, 1)->format('M_Y') . '.xlsx')
                                ->withChunkSize(50), // Turunin chunk size biar lebih aman
                        ]),
                ])
                    ->icon('heroicon-m-arrow-down-tray')
                    ->label("Export Data")
                    ->tooltip("Export Data"),
            ])
            ->heading(Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y') . ' Summary')
            ->description('All Site Summary BAKTI RTGS Mahaga per Month.')
            ->persistFiltersInSession()
            ->actions([])
            ->bulkActions([])
            ->paginated([2, 5, 10, 20])
            ->defaultPaginationPageOption(2);
    }
}
