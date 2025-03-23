<?php

namespace App\Filament\Widgets;

use App\Models\SiteMonitor;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SiteMonitorRouterDownByDaysPolarChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'siteMonitorRouterDownByDaysPolarChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Sensor Status';
    protected static ?string $subheading = 'Sensor status by modem currently down';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {

        $openTT = SiteMonitor::query()->where('modem', 'Down')
            ->selectRaw('status, COUNT(site_id) as total_status')
            ->groupBy('status')->orderBy('status', 'asc')
            ->pluck('total_status', 'status');


        return [
            'chart' => [
                'type' => 'donut',
                'height' => 380,
                'fontFamily' => 'inherit',
            ],

            'series' => $openTT->values()->toArray(),

            'labels' => $openTT->keys()->toArray(),

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
                    'opacity' => .4,
                ]
            ],

            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '50%',
                    ],
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
                        return val + " (" + opts.w.globals.series[opts.seriesIndex] + " Site)"
                    }
                },


                plotOptions: {
                    pie: {
                        startAngle: 0,
                        endAngle: 360,
                    }
                },

                colors: ['#00A676', '#FFCF56', '#F18805', '#D7263D'],

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
