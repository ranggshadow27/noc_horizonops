<?php

namespace App\Filament\Widgets;

use App\Models\SpPerformance;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SPRankTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'spRankTrendChart';
    protected static ?string $heading = 'Daily Rank Trend';
    protected static ?string $subheading = 'Daily Ranking TT Mahaga';

    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '300s';
    protected static bool $deferLoading = true;

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->label('Start Date')
                ->default(now()->subDays(7)->startOfDay())
                ->reactive(),

            DatePicker::make('date_end')
                ->label('End Date')
                ->default(now()->endOfDay())
                ->reactive(),
        ];
    }

    protected function getOptions(): array
    {
        $filterData = $this->filterFormData;

        $start = Carbon::parse($filterData['date_start'] ?? now()->subDays(14))->startOfDay();
        $end = Carbon::parse($filterData['date_end'] ?? now())->endOfDay();

        // Generate rentang tanggal
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Query Trend Khusus SPID-019 untuk today_rank
        $rankTrend = Trend::query(SpPerformance::where('sp_id', 'SPID-019'))
            ->between($start, $end)
            ->perDay()
            ->max('today_rank');

        $rankData = $rankTrend->map(fn(TrendValue $value) => [
            'date' => $value->date,
            'value' => $value->aggregate,
        ])->pluck('value', 'date')->toArray();

        // Ubah null menjadi 0 atau angka murni agar Livewire tidak error saat serialize array
        $ranks = collect($dates)->map(function ($date) use ($rankData) {
            $val = $rankData[$date] ?? null;
            return $val !== null ? (int) $val : null;
        })->values()->toArray();

        $categories = collect($dates)
            ->map(fn($d) => Carbon::parse($d)->translatedFormat('d M'))
            ->values()
            ->toArray();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400,
                'fontFamily' => 'inherit',
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
            'series' => [
                [
                    'name' => 'Rank (SPID-019)',
                    'data' => $ranks,
                ],
            ],
            'xaxis' => [
                'categories' => $categories,
            ],
            'yaxis' => [
                'reversed' => true, // Rank #1 paling atas
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
            'colors' => ['#3B82F6'],
            'grid' => [
                'show' => true,
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 5,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        // Masukkan kode JavaScript untuk formatter di sini agar aman dari serialisasi Livewire
        return RawJs::make(<<<JS
        {
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val ? '#' + Math.round(val) : '';
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val ? '#' + val : '';
                },
                offsetY: -8,
                style: {
                    fontSize: '12px',
                    fontWeight: 'bold',
                    colors: ['#3B82F6']
                },
                background: {
                    enabled: true,
                    foreColor: '#ffffff',
                    borderRadius: 4,
                    padding: 4,
                    opacity: 0.9,
                    borderWidth: 1,
                    borderColor: '#3B82F6'
                }
            },
            tooltip: {
                enabled: true,
                y: {
                    formatter: function(val) {
                        return val ? 'Rank #' + val : 'No Data';
                    }
                }
            }
        }
        JS);
    }

    // PAKSA LIVEWIRE HANYA SIMPAN ARRAY PRIMITIF
    protected function dehydrateStateUsing(): array
    {
        return [
            'options' => $this->getOptions(),
        ];
    }
}
