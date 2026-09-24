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
                ->default(false)
                ->reactive(),
        ];
    }

    protected function getOptions(): array
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        $showDataLabels = $filterData['show_data_labels'] ?? true;

        if (empty($selectedSpIds)) {
            return [
                'series' => [],
                'xaxis' => ['categories' => []],
            ];
        }

        $start = Carbon::parse($filterData['date_start'] ?? now()->subDays(7))->startOfDay();
        $end = Carbon::parse($filterData['date_end'] ?? now())->endOfDay();

        // 1. Generate SELURUH rentang tanggal harian (semua data tetap di-load)
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $series = [];
        $colors = ['#8B5CF6', '#EF4444', '#10B981', '#F59E0B', '#3B82F6', '#EC4899', '#005921'];
        $globalMaxY = 0;

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

            // 2. Format data harian penuh tanpa ada yang di-skip di backend
            $formattedSeriesData = [];

            foreach ($dates as $date) {
                $rank = $rankData[$date] ?? null;

                if (empty($rank) || $rank <= 0) {
                    continue;
                }

                $rankValue = (int) $rank;

                if ($rankValue > $globalMaxY) {
                    $globalMaxY = $rankValue;
                }

                $formattedSeriesData[] = [
                    'x' => Carbon::parse($date)->translatedFormat('d M'),
                    'y' => $rankValue,
                ];
            }

            $series[] = [
                'name' => $sp->sp_name,
                'data' => $formattedSeriesData,
            ];
        }

        // Tentukan batas Y max: Nilai Max Terbesar + 5
        $yMaxBoundary = $globalMaxY > 0 ? ($globalMaxY + 2) : 10;

        return [
            'chart' => [
                'type' => 'area',
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

            'dataLabels' => [
                'enabled' => (bool) $showDataLabels,
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
                // Sembunyikan garis penanda (ticks) berlebih agar tidak padat
                'tickAmount' => 14,
            ],
            'yaxis' => [
                'min' => 0,
                'max' => $yMaxBoundary,
                'stepSize' => 5,
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],

            'markers' => [
                'size' => 0,
            ],

            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 1,
                    'inverseColors' => false,
                    'opacityFrom' => 0.45,
                    'opacityTo' => 0.05,
                    'stops' => [5, 50, 100, 100],
                ],
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
        xaxis: {
            labels: {
                formatter: function (val, timestamp, opts) {
                    if (!val) return '';

                    // Ambil total titik data yang ada di chart saat ini
                    let totalCategories = opts && opts.w && opts.w.globals.labels ? opts.w.globals.labels.length : 0;
                    let currentIndex = opts ? opts.i : -1;

                    // Jika data <= 14, tampilkan semua label tanggal
                    if (totalCategories <= 14) {
                        return val;
                    }

                    // Tentukan max 14 label teks yang boleh muncul
                    let maxLabels = 14;

                    // Selalu tampilkan label pertama (Start Date) dan label terakhir (End Date)
                    if (currentIndex === 0 || currentIndex === totalCategories - 1) {
                        return val;
                    }

                    // Hitung kelipatan indeks agar label terdistribusi merata maks 14 label
                    let step = Math.floor(totalCategories / (maxLabels - 1));
                    if (step > 0 && currentIndex % step === 0) {
                        return val;
                    }

                    // Jika bukan titik interval, kosongkan teks labelnya (namun titik valuenya tetap ada di chart)
                    return '';
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return val ? Math.round(val) : '';
                }
            },
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
