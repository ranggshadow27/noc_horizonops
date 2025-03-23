<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NmtTicketByProblemClassChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'nmtTicketByProblemClassChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Problem Classification';
    protected static ?string $subheading = 'Total NMT Tickets Open by Problem Classification (Live)';


    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {

        $problems = NmtTickets::where('status', 'OPEN')
            ->selectRaw('problem_classification, COUNT(*) as count')
            ->groupBy('problem_classification')
            ->orderBy('problem_classification', 'desc')
            ->pluck('count', 'problem_classification');

        $labelMapping = [
            'MASALAH SUMBER DAYA LISTRIK' => 'Power',
            'LAYANAN AI SEMENTARA DIMATIKAN' => 'Sengaja Dimatikan',
            'MASALAH PERANGKAT IDU' => 'Perangkat IDU',
            'MASALAH PERANGKAT ODU' => 'Perangkat ODU',
            'PENGAJUAN RELOKASI' => 'Relokasi',
            'OFFLINE' => 'Offline',
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

            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 6,
                    'borderRadiusApplication'=> 'end', // 'around', 'end'
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
        }
        JS);
    }
}
