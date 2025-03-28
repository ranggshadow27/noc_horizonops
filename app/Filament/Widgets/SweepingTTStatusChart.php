<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SweepingTTStatusChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'sweepingTTStatusChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'SweepingTTStatusChart';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $today = Carbon::today();

        $sweepingOpen = SweepingTicket::whereNot('status', 'CLOSED')
            ->whereDate('created_at', $today)
            ->selectRaw('classification, COUNT(*) as count')
            ->groupBy('classification')
            ->orderBy('classification', 'desc')
            ->pluck('count', 'classification');

        $sweepingClosed = SweepingTicket::where('status', 'CLOSED')
            ->whereDate('created_at', $today)
            ->selectRaw('classification, COUNT(*) as count')
            ->groupBy('classification')
            ->orderBy('classification', 'desc')
            ->pluck('count', 'classification');


        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'stacked' => false,
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
                    'name' => 'Open',
                    'data' => $sweepingOpen->values()->toArray(),
                ],
                [
                    'name' => 'Closed',
                    'data' => $sweepingClosed->values()->toArray(),
                ],
            ],

            'xaxis' => [
                // 'type' => 'datetime',
                'categories' => $sweepingClosed->keys()->toArray(),
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

            'colors' => ['#CB0101', '#45C8D9', '#FEC620'],

            'stroke' => [
                'width' => 1,
                'colors' => ['#fff']
            ]


        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            // theme: {
            //     mode:'light',
            //     palette: 'palette3',
            // },

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
                    borderRadiusWhenStacked: 'all', // 'all', 'last'
                    borderRadiusApplication: 'end', // 'around', 'end'
                    dataLabels: {
                        // offsetY: 30,
                        total: {
                            enabled: true,
                        style: {
                            fontSize: '12px',
                            // fontWeight: 900
                            }
                        }
                    }
                },
            },

            legend: {
                position: 'bottom',
                // offsetY: 40
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
