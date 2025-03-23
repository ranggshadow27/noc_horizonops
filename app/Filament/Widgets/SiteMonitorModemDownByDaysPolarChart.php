<?php

namespace App\Filament\Widgets;

use App\Models\SiteMonitor;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SiteMonitorModemDownByDaysPolarChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'siteMonitorModemDownByDaysPolarChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Sensor Downtime';
    protected static ?string $subheading = 'Overall sensor downtime (duration)';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {

        $now = Carbon::now();

        $modems = SiteMonitor::where('modem', 'Down')->whereNotNull('modem_last_up')->get();
        $routers = SiteMonitor::where('mikrotik', 'Down')->whereNotNull('mikrotik_last_up')->get();
        $ap1s = SiteMonitor::where('ap1', 'Down')->whereNotNull('ap1_last_up')->get();
        $ap2s = SiteMonitor::where('ap2', 'Down')->whereNotNull('ap2_last_up')->get();

        $modemCategories = [
            '<3 Days' => 0,
            '4-7 Days' => 0,
            '8-14 Days' => 0,
            '15-30 Days' => 0,
            '>30 Days' => 0,
        ];

        $routerCategories = [
            '<3 Days' => 0,
            '4-7 Days' => 0,
            '8-14 Days' => 0,
            '15-30 Days' => 0,
            '>30 Days' => 0,
        ];

        $ap1Categories = [
            '<3 Days' => 0,
            '4-7 Days' => 0,
            '8-14 Days' => 0,
            '15-30 Days' => 0,
            '>30 Days' => 0,
        ];

        $ap2Categories = [
            '<3 Days' => 0,
            '4-7 Days' => 0,
            '8-14 Days' => 0,
            '15-30 Days' => 0,
            '>30 Days' => 0,
        ];


        foreach ($modems as $modem) {
            $downDuration = $modem->modem_last_up->diffInDays($now);

            if ($downDuration <= 3) {
                $modemCategories['<3 Days']++;
            } elseif ($downDuration > 3 && $downDuration <= 7) {
                $modemCategories['4-7 Days']++;
            } elseif ($downDuration >= 8 && $downDuration <= 14) {
                $modemCategories['8-14 Days']++;
            } elseif ($downDuration >= 15 && $downDuration <= 30) {
                $modemCategories['15-30 Days']++;
            } else {
                $modemCategories['>30 Days']++;
            }
        }

        foreach ($routers as $router) {
            $routerDownDuration = $router->mikrotik_last_up->diffInDays($now);

            if ($routerDownDuration <= 3) {
                $routerCategories['<3 Days']++;
            } elseif ($routerDownDuration > 3 && $routerDownDuration <= 7) {
                $routerCategories['4-7 Days']++;
            } elseif ($routerDownDuration >= 8 && $routerDownDuration <= 14) {
                $routerCategories['8-14 Days']++;
            } elseif ($routerDownDuration >= 15 && $routerDownDuration <= 30) {
                $routerCategories['15-30 Days']++;
            } else {
                $routerCategories['>30 Days']++;
            }
        }

        foreach ($ap1s as $ap1) {
            $downDuration = $ap1->ap1_last_up->diffInDays($now);

            if ($downDuration <= 3) {
                $ap1Categories['<3 Days']++;
            } elseif ($downDuration > 3 && $downDuration <= 7) {
                $ap1Categories['4-7 Days']++;
            } elseif ($downDuration >= 8 && $downDuration <= 14) {
                $ap1Categories['8-14 Days']++;
            } elseif ($downDuration >= 15 && $downDuration <= 30) {
                $ap1Categories['15-30 Days']++;
            } else {
                $ap1Categories['>30 Days']++;
            }
        }

        foreach ($ap2s as $ap2) {
            $downDuration = $ap2->ap2_last_up->diffInDays($now);

            if ($downDuration <= 3) {
                $ap2Categories['<3 Days']++;
            } elseif ($downDuration > 3 && $downDuration <= 7) {
                $ap2Categories['4-7 Days']++;
            } elseif ($downDuration >= 8 && $downDuration <= 14) {
                $ap2Categories['8-14 Days']++;
            } elseif ($downDuration >= 15 && $downDuration <= 30) {
                $ap2Categories['15-30 Days']++;
            } else {
                $ap2Categories['>30 Days']++;
            }
        }


        return [
            'chart' => [
                'type' => 'bar',
                'height' => 375,
                'fontFamily' => 'inherit',
                'stacked' => true,
                'stackType' => '100%',
            ],

            'series' => [
                [
                    'name' => 'Modem',
                    'data' => array_values($modemCategories),
                ],
                [
                    'name' => 'Router',
                    'data' => array_values($routerCategories),
                ],
                [
                    'name' => 'Access Point 1',
                    'data' => array_values($ap1Categories),
                ],
                [
                    'name' => 'Access Point 2',
                    'data' => array_values($ap2Categories),
                ],
            ],

            'xaxis' => [
                'categories' => array_keys($modemCategories),
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 6,
                    'borderRadiusApplication' => 'end',
                    // 'dataLabels' => [
                    //     'position' => 'top',
                    // ],
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

            'dataLabels' => [
                'enabled' => true,
                // 'offsetX' => -25,
                'style' => [
                    'fontSize' => '12px',
                ]
            ],

            'fill' => [
                'opacity' => 1
            ],


            'legend' => [
                'position' => 'bottom',
            ]
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {

        return RawJs::make(
            <<<'JS'
            {
                // chart: {
                //     events: {
                //         dataPointSelection: (event, chartContext,config) => {

                //             let category = config.w.config;
                //             console.log(category); // Debugging

                //             // window.location.href = '/site-monitors';
                //             console.log("Tess");
                //         }
                //     }
                // },

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

                stroke: {
                    width: 2,
                    colors: ['#fff'],
                },

                tooltip: {
                    intersect: false,
                    shared: true,
                },

                dataLabels: {
                    formatter: function(val, opts) {
                        console.log(opts);
                        return opts.w.globals.series[opts.seriesIndex][opts.dataPointIndex];
                    }
                },
            }
            JS,
        );
    }
}
