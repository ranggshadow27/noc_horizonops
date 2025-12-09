<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;


class CbossTicketDeviceProblem extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'cbossTicketTroubleCategoryChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'CBOSS Ticket by Trouble Category';
    protected static ?string $subheading = 'CBOSS Tickets Currently Open (by Trouble Category)';

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
        $openTT = CbossTicket::whereNot('status', 'Closed')
            ->whereNotNull('trouble_category')
            ->get();

        $troubleCategories = [
            'Transceiver' => 0,
            'POE' => 0,
            'Antenna' => 0,
            'Router' => 0,
            'Modem' => 0,
            'Access Point' => 0,
            'Stabillizer' => 0,
        ];

        foreach ($openTT as $tt) {
            $category = $tt->trouble_category;

            if (str_contains(strtolower($category), 'transceiver')) {
                $troubleCategories['Transceiver']++;
            } elseif (str_contains(strtolower($category), 'poe')) {
                $troubleCategories['POE']++;
            } elseif (str_contains(strtolower($category), 'dish')) {
                $troubleCategories['Antenna']++;
            } elseif (str_contains(strtolower($category), 'router') && !str_contains(strtolower($category), 'adaptor')) {
                $troubleCategories['Router']++;
            } elseif (str_contains(strtolower($category), 'modem') && !str_contains(strtolower($category), 'adaptor')) {
                $troubleCategories['Modem']++;
            } elseif (str_contains(strtolower($category), 'access point')) {
                $troubleCategories['Access Point']++;
            } elseif (str_contains(strtolower($category), 'stabillizer')) {
                $troubleCategories['Stabillizer']++;
            }
        }

        return [
            'chart' => [
                'type' => 'polarArea',
                'height' => 390,
                'fontFamily' => 'inherit',
            ],
            'series' => array_values($troubleCategories),
            'labels' => array_keys($troubleCategories),
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
                    'opacity' => 0.2,
                ],
                'style' => [
                    'fontSize' => '16px',
                    'fontWeight' => 600,
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
                fill: {
                    opacity: 0.8,
                    // type: 'gradient',
                },
                legend: {
                    fontFamily: 'inherit',
                    position: 'bottom',
                    formatter: function(val, opts) {
                        // return val + " (" + opts.w.globals.series[opts.seriesIndex] + " TT)"
                        return val
                    }
                },
                plotOptions: {
                    pie: {
                        dataLabels: {
                            offset: 10,
                            minAngleToShowLabel: 10,
                        },
                        customScale: 0.9,
                        startAngle: 0,
                        endAngle: 360,
                        donut: {
                            size: '55%',
                            labels: {
                                show: false,
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
                colors: ['#00A676', '#A3B9C9', '#F18805', '#D7263D', '#7B0D1E', '#3B82F6', '#A49E8D'],
                dataLabels: {
                    formatter: function(val, opts) {
                        return opts.w.globals.series[opts.seriesIndex] + " Ticket(s)"
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
            }
            JS,
        );
    }
}
