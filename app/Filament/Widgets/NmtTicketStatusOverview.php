<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Forms\Components\DatePicker;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\RawJs;

class NmtTicketStatusOverview extends ApexChartWidget
{
    use HasWidgetShield;
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'nmtTicketStatusOverview';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'NMT Ticket Status';
    protected static ?string $subheading = 'Summary of NMT Ticket Status';

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
                ->default(now()->startOfMonth()),
            DatePicker::make('date_end')
                ->default(now()->endOfDay()),
        ];
    }

    protected function getOptions(): array
    {

        $overallTicketStartDate = NmtTickets::where('status', 'OPEN')
            // ->whereNotIn('problem_detail', ['RENOVASI', 'RELOKASI', 'BENCANA ALAM'])
            ->orderBy('date_start', 'asc')
            ->value('ticket_id');

        // dd($overallTicketStartDate);

        $totalOpen = NmtTickets::where('status', 'OPEN')
            ->whereNotIn('problem_detail', ['RENOVASI', 'RELOKASI', 'BENCANA ALAM'])
            ->whereBetween('date_start', [
                Carbon::parse($overallTicketStartDate),
                Carbon::parse($this->filterFormData['date_start']),
            ])->count();

        // $poeCount = $poeTrend->map(function ($item) {
        //     // Decode string menjadi array, jika problem_json adalah string JSON
        //     $problemArray = $item->ticket_id; // Pastikan problem_json adalah JSON
        //     // Hitung berapa kali 'POE' muncul dalam array
        //     return $problemArray;
        //     // return $problemArray;
        // });

        // dd($poeCount);

        // $openTT = Trend::query(NmtTickets::where('status', 'OPEN'))
        $openTT = Trend::model(NmtTickets::class)
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('date_start')
            ->perDay()
            ->count();


        $closeTT = Trend::query(NmtTickets::where('status', 'CLOSED'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('closed_date')
            ->perDay()
            ->count();

        $totalTT = Trend::query(NmtTickets::where('status', 'OPEN')
            ->whereNotIn('problem_detail', ['RENOVASI', 'RELOKASI', 'BENCANA ALAM']))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('date_start')
            ->perDay()
            ->count()
            ->reduce(function ($carry, TrendValue $value) use (&$totalOpen) {
                // static $total = 14;
                $totalOpen += $value->aggregate;
                $carry[] = ['date' => $value->date, 'total' => $totalOpen];
                return $carry;
            }, []);

        // dd($totalTT);

        return [
            // 'theme' => [
            //     'mode' => 'light', //dark
            //     'palette' => 'palette4'
            // ],

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
                    'name' => 'Ticket Open',
                    'data' => $openTT->map(fn(TrendValue $value) => $value->aggregate),
                ],

                [
                    'name' => 'Closed Ticket',
                    // 'data' => [7, 4, 6, 10, 14, 7, 5, 9, 10, 15, 13, 18],
                    'data' => $closeTT->map(fn(TrendValue $value) => $value->aggregate),

                    // 'data' => array_column($closeTT, 'total'),
                ],

                // [
                //     'name' => 'Total Ticket',
                //     // 'data' => [7, 4, 6, 10, 14, 7, 5, 9, 10, 15, 13, 18],
                //     // 'data' => $closeTT->map(fn(TrendValue $value) => $value->aggregate),

                //     'data' => array_column($totalTT, 'total'),
                // ],
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
                // 'categories' => array_column($closeTT, 'date'),
                'categories' => $openTT->map(fn(TrendValue $value) => $value->date),
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

            // 'colors' => ['#80b918', '#f7b801'],
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
                // 'colors' => ['#80b918', '#f7b801'], // Sesuaikan warna dengan series
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
