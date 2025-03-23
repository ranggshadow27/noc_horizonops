<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NmtTicketTeknisNonTeknisChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'nmtTicketTeknisNonTeknisChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Teknis/Non Teknis';
    protected static ?string $subheading = 'NMT Ticket Currently Open (Teknis/Non Teknis)';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $openTT = NmtTickets::where('status', 'OPEN')
            ->count();

        $nonTeknis = $openTT > 0 ? (NmtTickets::where('problem_type', 'NON TEKNIS')
            ->where('status', 'OPEN')
            ->count() / $openTT) * 100 : 0;

        $teknis = $openTT > 0 ? (NmtTickets::where('problem_type', 'TEKNIS')
            ->where('status', 'OPEN')
            ->count() / $openTT) * 100 : 0;

        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 390,
                'fontFamily' => 'inherit',
            ],

            'series' => [round($nonTeknis), round($teknis)],

            // 'plotOptions' => [
            //     'radialBar' => [
            //         'hollow' => [
            //             'margin' => 5,
            //             'size' => '40%',
            //         ],
            //         'dataLabels' => [
            //             'show' => true,
            //             'name' => [
            //                 'show' => true,
            //                 'fontFamily' => 'inherit'
            //             ],
            //             'value' => [
            //                 'show' => true,
            //                 'fontFamily' => 'inherit',
            //                 'fontWeight' => 600,
            //                 'fontSize' => '20px'
            //             ],
            //         ],

            //     ],
            // ],
            // 'stroke' => [
            //     'lineCap' => 'round',
            // ],

            'labels' => ['Non-Teknis', 'Teknis'],
            'colors' => ['#B2B09B' , '#B91372'],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
            {
                legend: {
                    show: true,
                    position: 'bottom',
                    formatter: function (val, opts) {
                        // console.log(opts.w);
                        return val + " - " + opts.w.globals.series[opts.seriesIndex] + '%';
                    }
                },

                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        opacityTo: 1,
                        type: "vertical",
                        shadeIntensity: .2,
                        stops: [0, 60, 100],
                        opacityFrom: 1,
                    },
                },

                plotOptions: {
                    radialBar: {
                        offsetY: 0,
                        startAngle: 0,
                        endAngle: 300,
                        size: undefined,
                        inverseOrder: false,
                        hollow: {
                            margin: 5,
                            size: '30%',
                            // background: 'transparent',
                            dropShadow: {
                                enabled: true,
                                top: 0,
                                left: 0,
                                blur: 3,
                                opacity: 0.5,
                            },
                        },
                        track: {
                            show: true,
                            background: '#C7C8CBFF',
                            // strokeWidth: '6%',
                            opacity: .2,
                            margin: 3, // margin is in pixels
                        },
                        dataLabels: {
                            name: {
                                show: false,
                            },
                            value: {
                                show: true,
                                color: 'inherit',
                                offsetY: 10,
                                fontSize: '20px',
                            }
                        },
                        barLabels: {
                            enabled: true,
                            useSeriesColors: true,
                            offsetX: -8,
                            fontSize: '14px',
                            formatter: function(seriesName, opts) {
                                return seriesName
                            },
                        },
                    },
                },
            }
        JS);
    }
}
