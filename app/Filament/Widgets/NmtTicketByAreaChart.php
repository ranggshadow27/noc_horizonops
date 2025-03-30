<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NmtTicketByAreaChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'nmtTicketByAreaChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Tickets Open by Area';
    protected static ?string $subheading = 'Summary of NMT Ticket Open by Area';


    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

    // protected function getFormSchema(): array
    // {
    //     return [DatePicker::make('date_start')->default(now()->subMonths(2)), DatePicker::make('date_end')->default(now()->endOfDay())];
    // }

    protected function getOptions(): array
    {
        $openTT = NmtTickets::query()->where('status', 'OPEN')
            ->selectRaw('area_list.area, COUNT(nmt_tickets.ticket_id) as total_tickets')
            ->join('area_list', 'area_list.province', '=', 'nmt_tickets.site_province')
            ->groupBy('area_list.area')->orderBy('area_list.area', 'asc')
            ->pluck('total_tickets', 'area_list.area');

        return [
            'chart' => [
                'type' => 'polarArea',
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
                chart: {
                    events: {
                        dataPointSelection: (event, chartContext,config) => {

                            let category = config.w.config;
                            console.log(category); // Debugging

                            // window.location.href = '/site-monitors';
                            console.log("Tess");
                        }
                    }
                },

                fill: {
                    opacity: 0.8,
                },

                legend: {
                    fontFamily: 'inherit',
                    position: 'bottom',
                    formatter: function(val, opts) {
                        return val + " (" + opts.w.globals.series[opts.seriesIndex] + " Tickets)"
                    }
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

                dataLabels: {
                    // formatter: function (value) { return value; },
                },

                plotOptions: {
                        polarArea: {
                            rings: {
                                strokeWidth: 1,
                            },
                            spokes: {
                                strokeWidth: 5,
                            },
                        }
                },
            }
            JS,
        );
    }
}
