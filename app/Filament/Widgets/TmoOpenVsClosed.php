<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use App\Models\CbossTmo;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TmoOpenVsClosed extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'tmoOpenVsClosed';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Progress Summary';
    protected static ?string $subheading = 'Overall TMO Maintenance Corrective & Preventive';

    protected static ?string $pollingInterval = '60s';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {

        $cmData = Trend::query(
            CbossTmo::whereRaw('NOT JSON_CONTAINS(LOWER(action), \'"pm"\')')
        )
            ->between(
                start: Carbon::parse(now()->subMonth(2)),
                end: Carbon::parse(now()->addMonths(3))
            )
            ->dateColumn('tmo_date')
            ->perMonth()
            ->count();

        $pmData = Trend::query(
            CbossTmo::whereRaw('JSON_CONTAINS(LOWER(action), \'"pm"\')')
        )
            ->between(
                start: Carbon::parse(now()->subMonth(2)),
                end: Carbon::parse(now()->addMonths(3))
            )
            ->dateColumn('tmo_date')
            ->perMonth()
            ->count();


        return [
            'chart' => [
                'type' => 'bar',
                'height' => 375,
                'stacked' => true,
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
                    'name' => 'TMO Corrective',
                    'data' => $cmData->map(fn(TrendValue $value) => $value->aggregate),
                ],
                [
                    'name' => 'TMO Preventive',
                    'data' => $pmData->map(fn(TrendValue $value) => $value->aggregate),
                ],
            ],

            'xaxis' => [
                // 'type' => 'datetime',
                'categories' => $pmData->map(fn(TrendValue $value) => Carbon::parse($value->date)->translatedFormat('M')),
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

            'colors' => ['#ef4444', '#10b981'], // Hijau untuk PM, Merah untuk CM

            'legend' => [
                'fontSize' => '14px',
                'fontWeight' => 400,
            ],

            'stroke' => [
                'width' => 1,
                'colors' => ['#fff']
            ],
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
