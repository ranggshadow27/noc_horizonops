<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CbossTicketOpenDurationChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'cbossTicketOpenDurationChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'CBOSS Ticket Aging';
    protected static ?string $subheading = 'CBOSS Tickets Currently Open (duration)';

    protected static ?string $pollingInterval = '60s';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $now = Carbon::now();

        $openTT = CbossTicket::whereNot('status', 'Closed')->whereNotNull('ticket_start')->get();

        $openTTCategories = [
            '<3 Days' => 0,
            '4-7 Days' => 0,
            '8-14 Days' => 0,
            '15-30 Days' => 0,
            '>30 Days' => 0,
        ];

        foreach ($openTT as $tt) {
            $downDuration = Carbon::parse($tt->ticket_start)->diffInDays($now);

            if ($downDuration <= 3) {
                $openTTCategories['<3 Days']++;
            } elseif ($downDuration > 3 && $downDuration <= 7) {
                $openTTCategories['4-7 Days']++;
            } elseif ($downDuration >= 8 && $downDuration <= 14) {
                $openTTCategories['8-14 Days']++;
            } elseif ($downDuration >= 15 && $downDuration <= 30) {
                $openTTCategories['15-30 Days']++;
            } else {
                $openTTCategories['>30 Days']++;
            }
        }

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 390,
                'fontFamily' => 'inherit',
            ],

            'series' => array_values($openTTCategories),

            'labels' => array_keys($openTTCategories),

            'legend' => [
                'labels' => [
                    'colors' => '#9ca3af',
                    'fontWeight' => 600,
                ],
            ],

            'dataLabels' => [
                'enabled' => true,
                'dropShadow' => [
                    'enabled' => true,
                    'opacity' => .2,
                ]
            ],

            'yaxis' => [
                'show' => false,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            'legend' => [
                'fontSize' => '14px',
                'fontWeight' => 400,
            ],
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
                    opacity: 0.8,
                    type: 'gradient',
                },

                legend: {
                    fontFamily: 'inherit',
                    position: 'bottom',
                    formatter: function(val, opts) {
                        return val + " (" + opts.w.globals.series[opts.seriesIndex] + " TT)"
                    }
                },

                plotOptions: {
                    pie: {
                        dataLabels: {
                            offset: 10,
                            minAngleToShowLabel: 10,
                        },
                        customScale: .9,
                        startAngle: 0,
                        endAngle: 360,
                        donut: {
                            size: '55%',
                            labels: {
                                show: true,
                                value: {
                                    show: true,
                                    fontFamily: 'inherit',
                                    fontSize: 24,
                                    fontWeight: 600,
                                    formatter: function (val) {
                                        return val + " TT"
                                    }
                                },
                                total: {
                                    show: true,
                                    // showAlways: true,
                                    label: 'Tickets Total',
                                    offsetY: 120,
                                    fontSize: 16,
                                    fontFamily: 'inherit',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => {
                                            return a + b
                                        }, 0)
                                    },
                                },
                            },
                        },
                    },
                },

                colors: ['#00A676', '#FEC620', '#F18805', '#D7263D','#7B0D1E'],

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

                dataLabels: {
                    // formatter: function(val, opts) {
                    //     return opts.w.globals.series[opts.seriesIndex] + "Site"
                    // }
                },
            }
            JS,
        );
    }
}
