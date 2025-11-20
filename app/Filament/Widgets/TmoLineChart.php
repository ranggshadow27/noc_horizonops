<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\CbossTmo;
use Filament\Forms\Components\DatePicker;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class TmoLineChart extends ApexChartWidget
{
    protected static ?string $chartId = 'tmoActivityChart';
    protected static ?string $heading = 'TMO Summary';
    protected static ?string $subheading = 'Daily Mahaga RTGS TMO Progression';

    protected static ?string $pollingInterval = '60s';
    protected int | string | null $cachedFor = 300; // optional: cache 5 menit

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->label('Pick Date Start')
                ->default(now()->subDays(18)->startOfDay()),
            DatePicker::make('date_end')
                ->label('Pick Date End')
                ->default(now()->endOfDay()),
        ];
    }

    protected function getOptions(): array
    {
        $start = Carbon::parse($this->filterFormData['date_start'] ?? now()->subDays(18))->startOfDay();
        $end   = Carbon::parse($this->filterFormData['date_end'] ?? now())->endOfDay();

        // Total semua TMO
        $totalTrend = Trend::model(CbossTmo::class)
            ->between(start: $start, end: $end)
            ->perDay()
            ->dateColumn('tmo_date')
            ->count();

        // Preventive
        $pmTrend = Trend::query(CbossTmo::whereRaw('JSON_CONTAINS(LOWER(action), \'"pm"\')'))
            ->between(start: $start, end: $end)
            ->perDay()
            ->dateColumn('tmo_date')
            ->count();

        // New Instalation
        $instalasiTrend = Trend::query(CbossTmo::whereRaw('JSON_CONTAINS(LOWER(action), \'"instalasi"\')'))
            ->between(start: $start, end: $end)
            ->perDay()
            ->dateColumn('tmo_date')
            ->count();

        // Corrective
        $cmTrend = Trend::query(
            CbossTmo::whereRaw('NOT JSON_CONTAINS(LOWER(action), \'"pm"\')')
                ->whereRaw('NOT JSON_CONTAINS(LOWER(action), \'"instalasi"\')')
        )
            ->between(start: $start, end: $end)
            ->perDay()
            ->dateColumn('tmo_date')
            ->count();

        // Buat date range lengkap biar urutan pasti sama
        $dateRange = collect();
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateRange->push($current->toDateString());
            $current->addDay();
        }

        // Fungsi helper buat ambil aggregate atau 0
        $getValue = fn($trend, $date) => $trend->firstWhere('date', $date)?->aggregate ?? 0;

        // Khusus New Instalation: kalau 0 → null (garis putus)
        $getInstalasiValue = fn($date) => ($agg = $getValue($instalasiTrend, $date)) > 0 ? $agg : null;

        // Build data dengan null hanya untuk New Instalation
        $pmData        = $dateRange->map(fn($d) => $getValue($pmTrend, $d))->toArray();
        $instalasiData = $dateRange->map($getInstalasiValue)->toArray(); // <--- ini yang bikin garis putus
        $cmData        = $dateRange->map(fn($d) => $getValue($cmTrend, $d))->toArray();
        $totalData     = $dateRange->map(fn($d) => $getValue($totalTrend, $d))->toArray();

        // Labels tanggal
        $dates = $dateRange->map(fn($d) => Carbon::parse($d)->format('d M'));

        return [
            'chart' => [
                'type' => 'line',
                'height' => 350,
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
            'series' => [
                [
                    'name' => 'Preventive Maintenance',
                    'data' => $pmData,
                    'type' => 'line',
                ],
                [
                    'name' => 'New Instalation',
                    'data' => $instalasiData, // garis biru akan putus kalau 0
                    'type' => 'line',
                ],
                [
                    'name' => 'Corrective Maintenance',
                    'data' => $cmData,
                    'type' => 'line',
                ],
                [
                    'name' => 'Total TMO',
                    'data' => $totalData,
                    'type' => 'area',
                ],
            ],
            'xaxis' => [
                'categories' => $dates,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#10b981', '#3b82f6', '#ef4444', '#BBC7A4'],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [4, 4, 4, 3],
            ],
            'tooltip' => [
                'x' => [
                    'format' => 'dd MMM',
                ],
            ],
            'fill' => [
                'opacity' => [1, 1, 1, 0.15],
                'gradient' => [
                    'inverseColors' => false,
                    'shade' => 'light',
                    'type' => "vertical",
                    'opacityFrom' => 0.85,
                    'opacityTo' => 0.55,
                    'stops' => [0, 100, 100, 100]
                ]
            ],
            'grid' => [
                'strokeDashArray' => 10,
                'position' => 'back',
                'yaxis' => [
                    'lines' => [
                        'show' => true
                    ]
                ],
            ],
            'markers' => [
                'size' => 4,
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff',
                'hover' => [
                    'size' => 8
                ]
            ],
            'legend' => [
                'fontSize' => '14px',
            ],
            'dataLabels' => [
                'enabled' => [false, false, false, true], // hanya Total yang ada label
                'offsetY' => -5,
                'style' => [
                    'fontSize' => '14px',
                    'fontWeight' => 'bold',
                ],
                'background' => [
                    'enabled' => false,
                    'borderRadius' => 3,
                    'opacity' => 0.7
                ]
            ],
        ];
    }
}
