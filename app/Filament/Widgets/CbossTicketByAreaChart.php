<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CbossTicketByAreaChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'cbossTicketByAreaChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'CBOSS Tickets by Area';
    protected static ?string $subheading = 'Summary of CBOSS Ticket Open by Area';

    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $openTT = CbossTicket::query()->whereNot('status', 'Closed')
            ->selectRaw('area_list.area, COUNT(cboss_tickets.ticket_id) as total_tickets')
            ->join('area_list', 'area_list.province', '=', 'cboss_tickets.province')
            ->groupBy('area_list.area')->orderBy('area_list.area', 'asc')
            ->pluck('total_tickets', 'area_list.area');

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 390,
                'fontFamily' => 'inherit',
            ],

            'series' => $openTT->values()->toArray(),

            'labels' => $openTT->keys()->toArray(),

            'stroke' => [
                'colors' => '#fff',
                'width' => 2,
            ],

            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontFamily' => 'inherit',
                ],
            ],

            'legend' => [
                'fontSize' => '14px',
                'fontWeight' => 400,
            ],

            'yaxis' => [
                'show' => false,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
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

                colors: ['#00A676', '#FEC620', '#2699D7'],

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
