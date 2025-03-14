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
    protected static ?string $heading = 'Open by Area';
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
        $openTT = NmtTickets::query()->where('status', 'OPEN')->selectRaw('area_list.area, COUNT(nmt_tickets.ticket_id) as total_tickets')->join('area_list', 'area_list.province', '=', 'nmt_tickets.site_province')->groupBy('area_list.area')->orderBy('area_list.area', 'asc')->pluck('total_tickets', 'area_list.area');

        return [
            'chart' => [
                'type' => 'polarArea',
                'height' => 350,
            ],

            'series' => $openTT->values()->toArray(),

            'labels' => $openTT->keys()->toArray(),

            'legend' => [
                'position' => 'bottom',
                'fontFamily' => 'inherit',
                // 'fontWeight' => 600,

                'labels' => [
                    // 'colors' => '#9ca3af',
                    'fontWeight' => 600,
                ],
            ],

            'stroke' => [
                'colors' => '#fff',
                'width' => 5,
            ],

            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontFamily' => 'inherit',
                ],
            ],

            'yaxis' => [
                'show' => false,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            // 'theme' => [
            //     'monochrome' => [
            //         'enabled' => true,
            //         'shadeTo' => 'light',
            //         'shadeIntensity' => 0.6,
            //     ],
            // ],

            // 'plotOptions' => [
            //     'polarArea' => [
            //         'rings' => [
            //             'strokeWidth' => 1,
            //             'strokeColor' => '#e8e8e8',
            //         ],

            //         'spokes' => [
            //             'strokeWidth' => 1,
            //             'connectorColors' => '#e8e8e8',
            //         ],
            //     ],
            // ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(
            <<<'JS'
            {
                // theme: {
                //         monochrome: {
                //             enabled: true,
                //             shadeTo: 'light',
                //             shadeIntensity: 0.6
                //         }
                // },

                plotOptions: {
                        polarArea: {
                            rings: {
                                strokeWidth: 1
                            },
                            spokes: {
                                strokeWidth: 1
                            },
                        }
                },

                // dataLabels: {
                //     enabled: true,
                //     formatter: function (value) {
                //         return value >= 5 ? value : "";
                //     }
                // },
            }
            JS,
        );
    }
}
