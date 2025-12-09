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
                'type' => 'pie',
                'height' => 365,
                'fontFamily' => 'inherit',
            ],

            'series' => $problems->values()->toArray(),

            'labels' => $formattedLabels->toArray(),

            'legend' => [
                'labels' => [
                    // 'colors' => '#242424FF',
                    'fontWeight' => 600,
                ],
            ],

            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontFamily' => 'inherit',
                    // 'colors' => ['#212121'],
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            fill: {
                type: "gradient",
            },

            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],

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
                            size: '15%',

                        },
                    },
                },

                legend: {
                    fontFamily: 'inherit',
                    position: 'bottom',
                    formatter: function(val, opts) {
                        return val + " (" + opts.w.globals.series[opts.seriesIndex] + " TT)"
                    }
                },

                // stroke: {
                //     show: true,
                //     // curve: 'straight',
                //     // lineCap: 'butt',
                //     // colors: '#fff',
                //     width: 2,
                //     dashArray: 0,
                // },

                colors: ['#F25F5C', '#3C91E6', '#FEC620', '#FA824C', '#7FD8BE'],

                dataLabels: {
                    position: 'bottom',
                    // formatter(val, opts) {
                    //     const name = opts.w.globals.labels[opts.seriesIndex]
                    //     return [name, val.toFixed(1) + '%']
                    // },
                },
        }
        JS);
    }
}
