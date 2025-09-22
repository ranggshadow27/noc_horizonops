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
use Illuminate\Support\Facades\Cache;

class SummarySiteTable extends BaseWidget
{
    public $month;
    public $year;
    public $monthName;
    public $siteLogs; // Property untuk simpan siteLogs preload per halaman

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
        $selectedMonth = $this->filters['date_filter']['month'] ?? $this->month;
        $selectedYear = $this->filters['date_filter']['year'] ?? $this->year;
        $daysInMonth = Carbon::create($selectedYear, $selectedMonth, 1)->daysInMonth;

        // Preload di luar closure
        $siteIds = SiteDetail::query()
            ->when($table->getFilters(), fn($q) => $table->applyFilters($q))
            ->when($table->getSortColumn(), fn($q) => $table->applySorting($q))
            ->forPage($table->getCurrentPage(), $table->getRecordsPerPage())
            ->pluck('site_id');

        $this->siteLogs = SiteLog::select('site_id', 'created_at', 'modem_uptime')
            ->whereIn('site_id', $siteIds)
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
                    ->label('Grok Site Name') // Ubah label untuk debug
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
                ...collect(range(1, $daysInMonth))->map(function ($day) use ($selectedYear, $selectedMonth) {
                    return IconColumn::make("day_$day")
                        ->label(Carbon::create($selectedYear, $selectedMonth, $day)->format('d'))
                        ->getStateUsing(function (SiteDetail $record) use ($day) {
                            // Pakai $this->siteLogs yang sudah preload
                            return $this->siteLogs->get($record->site_id, collect([]))
                                ->get($day, collect([]))
                                ->first()['modem_uptime'] ?? '-';
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
                ActionGroup::make([
                    ExportAction::make('export_csv')
                        ->icon('phosphor-file-csv-duotone')
                        ->label("Export to CSV")
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                                ->withFilename(fn() => 'Summary_Site_' . Carbon::create($selectedYear, $selectedMonth, 1)->format('M_Y') . '.csv')
                                ->withChunkSize(50),
                        ]),
                    ExportAction::make('export_xlsx')
                        ->icon('phosphor-file-xls-duotone')
                        ->label("Export to XLSX")
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->withFilename(fn() => 'Summary_Site_' . Carbon::create($selectedYear, $selectedMonth, 1)->format('M_Y') . '.xlsx')
                                ->withChunkSize(50),
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
            ->paginated([5, 10, 20])
            ->defaultPaginationPageOption(5);
    }
}
