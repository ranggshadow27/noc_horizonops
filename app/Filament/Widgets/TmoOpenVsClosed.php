<?php

namespace App\Filament\Widgets;

use App\Models\CbossTmo;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TmoOpenVsClosed extends ApexChartWidget
{
    protected static ?string $chartId = 'tmoOpenVsClosed';
    protected static ?string $heading = 'TMO Total';
    protected static ?string $subheading = 'Overall TMO Progress per Month';

    protected static ?string $pollingInterval = '60s';

    protected function getOptions(): array
    {
        // Query data per bulan (2 bulan lalu sampai 3 bulan ke depan)
        $cmData = Trend::query(
            CbossTmo::whereRaw('NOT JSON_CONTAINS(LOWER(action), \'"pm"\')')
                ->whereRaw('NOT JSON_CONTAINS(LOWER(action), \'"instalasi"\')')
        )
            ->between(
                start: Carbon::parse(now()->subMonths(2)),
                end: Carbon::parse(now()->addMonths(3))
            )
            ->dateColumn('tmo_date')
            ->perMonth()
            ->count();

        $pmData = Trend::query(
            CbossTmo::whereRaw('JSON_CONTAINS(LOWER(action), \'"pm"\')')
        )
            ->between(
                start: Carbon::parse(now()->subMonths(2)),
                end: Carbon::parse(now()->addMonths(3))
            )
            ->dateColumn('tmo_date')
            ->perMonth()
            ->count();

        // Tambah New Instalation
        $instalasiData = Trend::query(
            CbossTmo::whereRaw('JSON_CONTAINS(LOWER(action), \'"instalasi"\')')
        )
            ->between(
                start: Carbon::parse(now()->subMonths(2)),
                end: Carbon::parse(now()->addMonths(3))
            )
            ->dateColumn('tmo_date')
            ->perMonth()
            ->count();

        // Pastikan semua bulan punya data (biar categories urut & ga bolong)
        $allMonths = collect();
        $current = now()->subMonths(2)->startOfMonth();
        $endDate = now()->addMonths(3)->endOfMonth();

        while ($current->lte($endDate)) {
            $allMonths->push($current->copy());
            $current->addMonth();
        }

        $getValue = fn($trend, $date) => $trend->firstWhere('date', $date->format('Y-m'))?->aggregate ?? 0;

        $pmValues        = $allMonths->map(fn($m) => $getValue($pmData, $m))->toArray();
        $instalasiValues = $allMonths->map(fn($m) => $getValue($instalasiData, $m))->toArray();
        $cmValues        = $allMonths->map(fn($m) => $getValue($cmData, $m))->toArray();

        $categories = $allMonths->map(fn($m) => $m->translatedFormat('M Y'))->toArray();
        // Contoh output: "Okt 2025", "Nov 2025", "Des 2025", dll

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 375,
                'stacked' => true,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'show' => true,
                    'tools' => [
                        'download' => true,
                        'pan' => false,
                        'zoom' => false,
                        'zoomin' => false,
                        'zoomout' => false,
                        'reset' => false,
                    ],
                ]
            ],
            'series' => [
                [
                    'name' => 'TMO Corrective',
                    'data' => $cmValues,
                ],
                [
                    'name' => 'TMO Preventive',
                    'data' => $pmValues,
                ],
                [
                    'name' => 'New Instalation',
                    'data' => $instalasiValues,
                ],
            ],

            'xaxis' => [
                'categories' => $categories,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
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

            // Warna sesuai urutan series di atas
            'colors' => ['#ef4444', '#10b981', '#3b82f6'], // PM hijau, Instalasi biru, CM merah

            'legend' => [
                'fontSize' => '14px',
                'fontWeight' => 400,
            ],

            'stroke' => [
                'width' => 1,
                'colors' => ['#fff']
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            toolbar: {
                show: false
            },

            zoom: {
                enabled: true
            },

            dataLabels: {
                offsetX: 0,
            },

            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    borderRadiusWhenStacked: 'all',
                    borderRadiusApplication: 'end',
                    dataLabels: {
                        total: {
                            enabled: true,
                            style: {
                                fontSize: '12px',
                            }
                        }
                    }
                },
            },

            legend: {
                position: 'bottom',
            },

            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }],

            fill: {
                type: "gradient",
                gradient: {
                    shade: 'light',
                    opacityFrom: .9,
                    opacityTo: 1,
                    type: "vertical",
                    shadeIntensity: .2,
                    stops: [0, 60, 100],
                },
            },
        }
        JS);
    }
}
