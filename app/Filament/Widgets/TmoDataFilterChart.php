<?php

namespace App\Filament\Widgets;

use App\Models\TmoData;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Forms\Components\DatePicker;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\RawJs;

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
                ->default(now()->addDays(1)),
        ];
    }

    protected function getOptions(): array
    {
        $pmTMO = Trend::query(TmoData::where('tmo_type', 'Preventive Maintenance'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perDay()
            ->count();

        $cmTMO = Trend::query(TmoData::where('tmo_type', 'Corrective Maintenance'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
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
                    'name' => 'Preventive',
                    // 'data' => [17, 5, 10, 10, 14, 4, 21, 19, 11, 13, 16, 8],
                    'data' => $pmTMO->map(fn(TrendValue $value) => $value->aggregate),
                ],

                [
                    'name' => 'Corrective',
                    // 'data' => [7, 4, 6, 10, 14, 7, 5, 9, 10, 15, 13, 18],
                    'data' => $cmTMO->map(fn(TrendValue $value) => $value->aggregate),
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
                'categories' => $cmTMO->map(fn(TrendValue $value) => $value->date),
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
                'curve' => 'smooth',
                'width' => 3,
            ],

            'grid' => [
                'strokeDashArray' => 10,
                // 'borderColor' => "#B6B6B6FF",
                'position' => 'back',
                // 'clipMarkers' => false,
                'yaxis' => [
                    'lines' => [
                        'show' => true
                    ]
                ],
            ],

            'dataLabels' => [
                'enabled' => true, // Menampilkan nilai di setiap titik
                'offsetY' => -10,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 'bold',
                    // 'colors' => ['#fff'], // Ubah warna teks agar terlihat jelas
                ],
                'background' => [
                    'enabled' => false, // Tambahkan background ke label
                    'borderRadius' => 3,
                    'opacity' => 0.7
                ]
            ],

            'markers' => [
                'size' => 4, // Ukuran titik
                'colors' => ['#80b918', '#f7b801'], // Sesuaikan warna dengan series
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff', // Warna garis luar
                'hover' => [
                    'size' => 8 // Ukuran saat di-hover
                ]
            ],

            // 'tooltip' => [
            //     'enabled' => true,
            //     'theme' => 'dark',
            // ],

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

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            // theme: {
            //     mode:'light',
            //     palette: 'palette3',
            // },

            dataLabels: {
                enabled: true,
                formatter: function (value) {
                    return value >= 5 ? value : "";
                }
            },        }
        JS);
    }
}
