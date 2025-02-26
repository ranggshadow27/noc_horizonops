<?php

namespace App\Filament\Widgets;

use App\Models\TmoData;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Forms\Components\DatePicker;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class TmoDataFilterChart extends ApexChartWidget
{
    use HasWidgetShield;
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'tmoDataFilterChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'TMO Data Chart';
    protected static ?string $subheading = 'Summary of TMO Data';

    protected static ?string $pollingInterval = null;
    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->default(now()->subDays(20)),
            DatePicker::make('date_end')
                ->default(now()),
        ];
    }

    protected function getOptions(): array
    {
        $pendingTmo = Trend::query(TmoData::where('approval', 'Pending'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->perDay()
            ->count();

        $approvedTmo = Trend::query(TmoData::where('approval', 'Approved'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('updated_at')
            ->perDay()
            ->count();

        return [
            'chart' => [
                'type' => "area",
                'height' => 350,
                'foreColor' => "#ccc",
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'autoSelected' => "pan",
                    'tools' => [
                        'download' => true,
                        'selection' => false,
                        'zoom' => false,
                        'zoomin' => false,
                        'pan' => false,
                        'zoomout' => false,
                        'reset' => false,
                    ]
                ],
            ],

            'series' => [
                [
                    'name' => 'Pending',
                    // 'data' => [17, 5, 10, 10, 14, 4, 21, 19, 11, 13, 16, 8],
                    'data' => $pendingTmo->map(fn(TrendValue $value) => $value->aggregate),
                ],

                [
                    'name' => 'Approved',
                    // 'data' => [7, 4, 6, 10, 14, 7, 5, 9, 10, 15, 13, 18],
                    'data' => $approvedTmo->map(fn(TrendValue $value) => $value->aggregate),
                ],
            ],

            'legend' => [
                'markers' => [
                    'size' => 4,
                    'offsetX' => -5,
                ],

                'itemMargin' => [
                    'horizontal' => 15,
                    'vertical' => 0,
                ]
            ],

            'xaxis' => [
                // 'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'categories' => $approvedTmo->map(fn(TrendValue $value) => $value->date),
                'type' => 'datetime',
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            'yaxis' => [
                'min' => 0,
                'tickAmount' => 4,
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            'colors' => ['#80b918', '#f7b801'],

            'stroke' => [
                // 'curve' => 'smooth',
                'width' => 3,
            ],

            'grid' => [
                'borderColor' => "#2A2A2DFF",
                'opacity' => 0.5,
                'clipMarkers' => false,
                'yaxis' => [
                    'lines' => [
                        'show' => true
                    ]
                ],
            ],

            'dataLabels' => [
                'enabled' => false
            ],

            'tooltip' => [
                'enabled' => true,
                'theme' => 'dark',
            ],

            'fill' => [
                // 'type' => 'gradient',
                'gradient' => [
                    // 'shade' => 'dark',
                    // 'type' => 'horizontal',
                    'enabled' => true,
                    // 'gradientToColors' => ['#ea580c'],
                    // 'inverseColors' => true,
                    'opacityFrom' => 0.55,
                    'opacityTo' => 0.0,
                    // 'stops' => [0, 90, 100],
                ],
            ],
        ];
    }
}
