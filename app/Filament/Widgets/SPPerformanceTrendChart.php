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
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SPPerformanceTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'spPerformanceTrendChart';
    protected static ?string $heading = 'Daily Ticket Percentage by Service Provider';
    protected static ?string $subheading = 'Daily Ticket Percentage by Service Provider';

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        if (empty($selectedSpIds)) {
            return 'SP Performance Overview';
        }

        $sps = ServiceProvider::whereIn('sp_id', $selectedSpIds)->get();
        $names = $sps->pluck('sp_name')->implode(' vs ');
        // $totalSites = $sps->sum('total_site');

        return "{$names}";
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('sp_ids')
                ->label('Service Providers')
                ->multiple()
                ->options(ServiceProvider::orderBy('sp_name')->pluck('sp_name', 'sp_id'))
                ->default(fn() => ServiceProvider::whereIn('sp_name', ['PSN', 'MAHAGA'])->pluck('sp_id')->toArray())
                ->searchable()
                ->reactive()
                ->placeholder('Pilih SP...')
                ->required(),

            DatePicker::make('date_start')
                ->label('Start Date')
                ->default(now()->subDays(60)->startOfDay())
                ->reactive(),

            DatePicker::make('date_end')
                ->label('End Date')
                ->default(now()->subDays(50)->endOfDay())
                ->reactive(),
        ];
    }

    protected function getOptions(): array
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        if (empty($selectedSpIds)) {
            return [
                'series' => [],
                'xaxis' => ['categories' => []],
            ];
        }

        $start = Carbon::parse($filterData['date_start'])->startOfDay();
        $end = Carbon::parse($filterData['date_end'])->endOfDay();

        // Generate semua tanggal
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $series = [];
        $colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];

        foreach ($selectedSpIds as $spId) {
            $sp = ServiceProvider::find($spId);
            if (!$sp || !$sp->total_site) continue;

            $totalSite = $sp->total_site;

            // PAKAI Trend
            $trend = Trend::query(SpPerformance::where('sp_id', $spId))
                ->between($start, $end)
                ->perDay()
                ->sum('today_ticket');

            // UBAH TrendValue → array primitif
            $trendData = $trend->map(fn(TrendValue $value) => [
                'date' => $value->date,
                'value' => $value->aggregate,
            ])->pluck('value', 'date')->toArray();

            // Isi semua tanggal, kosong = 0
            $percentages = collect($dates)->map(function ($date) use ($trendData, $totalSite) {
                $total = $trendData[$date] ?? 0;

                // KALAU TOTAL = 0 → return null, bukan 0.0
                return $total > 0 ? round(($total / $totalSite) * 100, 2) : null;
            })->values()->toArray();

            $series[] = [
                'name' => $sp->sp_name . ' (%)',
                'data' => $percentages,
            ];
        }

        $categories = collect($dates)
            ->map(fn($d) => Carbon::parse($d)->translatedFormat('d M'))
            ->values()
            ->toArray();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 625,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'autoSelected' => "pan",
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
            'xaxis' => [
                'categories' => $categories,
            ],
            // 'yaxis' => [
            //     'min' => 0,
            //     'max' => 100,
            //     'labels' => [
            //         'formatter' => RawJs::make("function(v) { return v + '%'; }"),
            //     ],
            // ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 6,
            ],
            'markers' => [
                'size' => 5,
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff', // Warna garis luar

            ],
            'colors' => array_slice($colors, 0, count($series)),
            'legend' => [
                'position' => 'top',
            ],
            // 'tooltip' => [
            //     'shared' => true,
            //     'intersect' => false,
            //     'y' => [
            //         'formatter' => RawJs::make("function(v) { return v + '%'; }"),
            //     ],
            // ],
            'grid' => [
                'show' => true,
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 5,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
        {
            chart: {
                dropShadow: {
                    enabled: true,
                    color: '#000',
                    top: 12,
                    left: 7,
                    blur: 10,
                    opacity: 0.1
                },
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val > 0 ? val.toFixed(1) + '%' : '';
                },
                offsetY: -5,
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    // colors: ['#1f2937']
                },
                background: {
                    enabled: false,
                    foreColor: '#fff',
                    borderRadius: 2,
                    padding: 4,
                    opacity: 0.9,
                    borderWidth: 1,
                    borderColor: '#e5e7eb'
                }
            },
            fill: {
                opacity: .4
            }
        }
        JS);
    }

    // KUNCI UTAMA: PAKSA LIVEWIRE HANYA SIMPAN ARRAY PRIMITIF
    protected function dehydrateStateUsing(): array
    {
        return [
            'options' => $this->getOptions(),
        ];
    }
}
