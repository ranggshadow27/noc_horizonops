<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CbossTicketByProblemClassChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'cbossTicketByProblemClassChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Problem Classification';
    protected static ?string $subheading = 'Total CBOSS Tickets Open by Problem Classification (Live)';

    protected static ?string $pollingInterval = '60s';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $problems = CbossTicket::whereNot('status', 'Closed')
            ->selectRaw('problem_map, COUNT(*) as count')
            ->groupBy('problem_map')
            ->orderBy('problem_map', 'desc')
            ->pluck('count', 'problem_map');

        $labelMapping = [
            'MASALAH SUMBER DAYA LISTRIK' => 'Power',
            'LAYANAN AI SEMENTARA DIMATIKAN' => 'Sengaja Dimatikan',
            'LAYANAN AI TIDAK DIGUNAKAN' => 'Tidak Digunakan',
            'MASALAH PERANGKAT IDU' => 'Perangkat IDU',
            'MASALAH PERANGKAT ODU' => 'Perangkat ODU',
            'PENGAJUAN RELOKASI' => 'Relokasi',
            'LINK TIDAK TERDETEKSI / OFFLINE' => 'Offline',
            '-' => 'Belum Teridentifikasi',
        ];

        $formattedLabels = $problems->keys()->map(function ($label) use ($labelMapping) {
            return $labelMapping[$label] ?? $label;
        });

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'fontFamily' => 'inherit',
            ],

            'series' => [
                [
                    'name' => 'Total Open: ',
                    'data' => $problems->values()->toArray(),
                ],
            ],

            'xaxis' => [
                'categories' => $formattedLabels->toArray(),
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            // 'yaxis' => [
            //     'labels' => [
            //         'style' => [
            //             'fontFamily' => 'inherit',
            //         ],
            //     ],
            // ],

            'colors' => ['#FEB019'],

            // 'stroke' => [
            //     'width' => 0,
            //     'colors' => ['#F50B0BFF']
            // ],

            'grid' => [
                'strokeDashArray' => 10,
                'position' => 'back',
                'yaxis' => [
                    'lines' => [
                        'show' => true
                    ]
                ],
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
        return RawJs::make(<<<'JS'
        {
            // theme: {
            //     mode:'light',
            //     palette: 'palette3',
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

            dataLabels: {
                enabled: true,
                offsetX: 16,
                style: {
                    colors: ['#212121']
                },
            },

            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    borderRadiusApplication: 'end', // 'around', 'end'
                    dataLabels: {
                        // offsetY: 30,
                        hideOverflowingLabels: true,
                        position: 'top',
                        orientation: 'horizontal',
                        total: {
                            enabled: true,
                            formatter: function(val, opts) {
                                return val;
                            },
                        style: {
                            fontSize: '12px',
                            // fontWeight: 900
                            }
                        }
                    }
                },
            },
        }
        JS);
    }
}
