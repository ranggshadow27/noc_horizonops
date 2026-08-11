<?php

namespace App\Filament\Widgets;

use App\Models\ServiceProvider;
use App\Models\SpPerformance;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Toggle;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SPRankTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'spRankTrendChart';
    protected static ?string $heading = 'Daily Rank Trend';
    protected static ?string $subheading = 'Daily Ranking by Service Provider';

    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '300s';
    protected static bool $deferLoading = true;

    protected function getHeading(): ?string
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        if (empty($selectedSpIds)) {
            return 'SP Rank Overview';
        }

        // $sps = ServiceProvider::whereIn('sp_id', $selectedSpIds)->get();
        // $names = $sps->pluck('sp_name')->implode(' vs ');

        return 'SP Rank Overview';
    }

    protected function getFormSchema(): array
    {
        return [

            Select::make('sp_ids')
                ->label('Service Providers')
                ->multiple()
                ->options(
                    ServiceProvider::whereIn('sp_name', ['DUTAKOM', 'KTP', 'MAHAGA', 'PIM', 'PSN', 'TELENET', 'XL'])
                        ->orderBy('sp_name')
                        ->pluck('sp_name', 'sp_id')
                )
                ->default(fn() => ServiceProvider::whereIn('sp_name', ['DUTAKOM', 'KTP', 'MAHAGA', 'PIM', 'PSN', 'TELENET', 'XL'])->pluck('sp_id')->toArray())
                ->searchable()
                ->reactive()
                ->placeholder('Pilih SP...')
                ->required(),

            DatePicker::make('date_start')
                ->label('Start Date')
                ->default(now()->subDays(7)->startOfDay())
                ->reactive(),

            DatePicker::make('date_end')
                ->label('End Date')
                ->default(now()->endOfDay())
                ->reactive(),

            Toggle::make('show_data_labels')
                ->label('Show Data Labels')
                ->default(false) // Default aktif/terlihat
                ->reactive(),
        ];
    }

    protected function getOptions(): array
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        // Cek status toggle show_data_labels (default true)
        $showDataLabels = $filterData['show_data_labels'] ?? true;

        if (empty($selectedSpIds)) {
            return [
                'series' => [],
                'xaxis' => ['categories' => []],
            ];
        }

        $start = Carbon::parse($filterData['date_start'] ?? now()->subDays(7))->startOfDay();
        $end = Carbon::parse($filterData['date_end'] ?? now())->endOfDay();

        // 1. Generate rentang tanggal
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $series = [];
        $colors = ['#8B5CF6', '#EF4444', '#10B981', '#F59E0B', '#3B82F6', '#EC4899', '#005921'];

        // Loop untuk setiap SP yang dipilih di filter
        foreach ($selectedSpIds as $spId) {
            $sp = ServiceProvider::find($spId);
            if (!$sp) continue;

            // Query Trend today_rank per SP
            $rankTrend = Trend::query(SpPerformance::where('sp_id', $spId))
                ->between($start, $end)
                ->perDay()
                ->max('today_rank');

            $rankData = $rankTrend->map(fn(TrendValue $value) => [
                'date' => $value->date,
                'value' => $value->aggregate,
            ])->pluck('value', 'date')->toArray();

            // 2. Format data & SKIP tanggal jika rank null / 0 (Pertahankan Logika Asli)
            $formattedSeriesData = [];

            foreach ($dates as $date) {
                $rank = $rankData[$date] ?? null;

                // --- FILTER PERTAHANKAN LOGIKA ASLI ---
                if (empty($rank) || $rank <= 0) {
                    continue;
                }

                $formattedSeriesData[] = [
                    'x' => Carbon::parse($date)->translatedFormat('d M'),
                    'y' => (int) $rank,
                ];
            }

            $series[] = [
                'name' => $sp->sp_name,
                'data' => $formattedSeriesData,
            ];
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 500,
                'background' => '#ffffff00',
                'fontFamily' => 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'toolbar' => [
                    'autoSelected' => 'pan',
                    'tools' => [
                        'download' => true,
                        'selection' => false,
                        'zoom' => false,
                        'zoomin' => false,
                        'pan' => false,
                        'zoomout' => false,
                        'reset' => false,
                    ]
                ],
            ],
            'series' => $series,

            // --- TAMBAHKAN KODE DATALABELS DI SINI ---
            'dataLabels' => [
                'enabled' => (bool) $showDataLabels, // Nilai boolean dinamis dari toggle
                'offsetY' => -8,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 'bold',
                    'colors' => ['#374151']
                ],
                'background' => [
                    'enabled' => true,
                    'foreColor' => '#ffffff',
                    'borderRadius' => 4,
                    'padding' => 4,
                    'opacity' => 0.9,
                    'borderWidth' => 1,
                    'borderColor' => '#e5e7eb'
                ]
            ],

            'xaxis' => [
                'type' => 'category',
            ],
            'yaxis' => [
                'reversed' => true,
                'min' => 1,
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 4,
            ],
            'markers' => [
                'size' => 6,
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff',
            ],
            'colors' => array_slice($colors, 0, count($series)),
            'legend' => [
                'position' => 'top',
            ],
            'grid' => [
                'show' => true,
                'borderColor' => 'rgba(156, 163, 175, 0.2)',
                'strokeDashArray' => 5,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
    {
        yaxis: {
            labels: {
                formatter: function (val) {
                    return val ? Math.round(val) : '';
                }
            }
        },
        dataLabels: {
            formatter: function (val) {
                return val ? val : '';
            }
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: function(val) {
                    return val ? val : 'No Data';
                }
            }
        }
    }
    JS);
    }
}
