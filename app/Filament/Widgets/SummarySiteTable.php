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

class SummarySiteTable extends BaseWidget
{
    public $month;
    public $year;

    public function mount(): void
    {
        // Set default ke bulan dan tahun sekarang
        $this->month = $this->month ?? Carbon::now()->month;
        $this->year = $this->year ?? Carbon::now()->year;
    }

    public function table(Table $table): Table
    {
        // Ambil nilai dari filter atau properti
        $selectedMonth = $this->filters['date_filter']['month'] ?? $this->month;
        $selectedYear = $this->filters['date_filter']['year'] ?? $this->year;

        // Hitung jumlah hari di bulan terpilih
        $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

        // Query untuk ambil semua site dan modem_uptime dari SiteLog berdasarkan bulan/tahun terpilih
        $siteLogs = SiteLog::select('site_id', 'created_at', 'modem_uptime')
            ->whereYear('created_at', $selectedYear)
            ->whereMonth('created_at', $selectedMonth)
            ->get()
            ->groupBy(['site_id', function ($log) {
                return Carbon::parse($log->created_at)->day;
            }]);

        return $table
            ->query(SiteDetail::query())
            ->columns([
                TextColumn::make('site_name')
                    ->label('Site Name')
                    ->sortable()
                    ->description(fn(SiteDetail $record): string => $record->site_id, position: 'above')
                    ->searchable(['site_id', 'site_name', 'province']),
                // Generate kolom dinamis untuk setiap tanggal di bulan terpilih
                ...collect(range(1, $daysInMonth))->map(function ($day) use ($siteLogs, $selectedMonth, $selectedYear) {
                    return IconColumn::make("day_$day")
                        ->label(Carbon::create($selectedYear, $selectedMonth, $day)->format('d M'))
                        ->getStateUsing(function (SiteDetail $record) use ($day, $siteLogs) {
                            // Ambil modem_uptime untuk site_id dan tanggal tertentu
                            return $siteLogs->get($record->site_id, collect([]))
                                ->get($day, collect([]))
                                ->first()['modem_uptime'] ?? '-';
                        })
                        ->icon(function ($state) {
                            // Handle kalau state adalah '-' atau null
                            if ($state === '-' || $state === null) {
                                return 'phosphor-dot-duotone';
                            }
                            // Konversi state ke integer untuk perbandingan
                            $uptime = (int) $state;
                            return match (true) {
                                $uptime >= 5 => 'phosphor-arrow-circle-up-duotone',
                                $uptime > 2 && $uptime < 5 => 'phosphor-warning-circle-duotone',
                                $uptime <= 2 => 'phosphor-arrow-circle-down-duotone',
                                default => 'phosphor-dots-three-circle-duotone',
                            };
                        })
                        ->tooltip(function ($state) {
                            if ($state !== '-') {
                                return "Uptime: " . $state . "/6";
                            }
                        })
                        ->color(function ($state) {
                            // Handle kalau state adalah '-' atau null
                            if ($state === '-' || $state === null) {
                                return null;
                            }
                            // Konversi state ke integer untuk perbandingan
                            $uptime = (int) $state;
                            return match (true) {
                                $uptime >= 5 => 'success',
                                $uptime > 2 && $uptime < 5 => 'warning',
                                $uptime <= 2 => 'danger',
                                default => null,
                            };
                        });
                })->toArray(),
            ])
            ->filters([
                Filter::make('date_filter')
                    ->form([
                        Select::make('month')
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
                            }),
                        Select::make('year')
                            ->label('Tahun')
                            ->options(collect(range(2020, Carbon::now()->year))->mapWithKeys(fn($year) => [$year => $year]))
                            ->default(Carbon::now()->year)
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->year = $state;
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        return $query;
                    }),
            ])
            ->persistFiltersInSession()
            ->actions([])
            ->bulkActions([]);
    }
}
