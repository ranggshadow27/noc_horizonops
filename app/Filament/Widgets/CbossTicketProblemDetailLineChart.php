<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CbossTicketProblemDetailLineChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'cbossTicketProblemDetailLineChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'CBOSS Ticket Category';
    protected static ?string $subheading = 'Summary CBOSS Open (Relokasi, Renovasi, Bencana, Libur)';

    protected static ?string $pollingInterval = '60s';

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
                ->default(now()->subDays(14)->startOfDay()),
            DatePicker::make('date_end')
                ->default(now()->endOfDay()),
        ];
    }

    protected function getOptions(): array
    {
        $openTT = Trend::model(CbossTicket::class)
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('ticket_start')
            ->perDay()
            ->count();

        $dates = [];

        $renovasiCounts = [];
        $relokCounts = [];
        $bencanaCounts = [];
        $liburCounts = [];

        $currentDate = Carbon::parse($this->filterFormData['date_start'])->startOfDay();

        while ($currentDate->lte(Carbon::parse($this->filterFormData['date_end'])->endOfDay())) {
            // Hitung jumlah tiket yang masih Open pada tanggal ini
            $openTickets = CbossTicket::where('ticket_start', '<=', $currentDate)
                ->where('trouble_category', 'LIKE', "%RENOVASI%")
                ->where(function ($query) use ($currentDate) {
                    $query->whereNot('status', 'Closed')
                        ->orWhere('ticket_end', '>=', $currentDate);
                })
                ->count();

            $relokTickets = CbossTicket::where('ticket_start', '<=', $currentDate)
                ->where('problem_map', 'LIKE', "%RELOKASI%")
                ->where(function ($query) use ($currentDate) {
                    $query->whereNot('status', 'Closed')
                        ->orWhere('ticket_end', '>=', $currentDate);
                })
                ->count();

            $todayLiburClose = CbossTicket::where('trouble_category', 'LIKE', "%LIBUR%")
                ->where('ticket_end', '>=', $currentDate)
                ->where('ticket_end', '<=', $currentDate->endOfDay())
                ->count();

            $liburTickets = CbossTicket::where('ticket_start', '<=', $currentDate)
                ->where('trouble_category', 'LIKE', "%LIBUR%")
                ->where(function ($query) use ($currentDate) {
                    $query->whereNot('status', 'Closed')
                        ->orWhere('ticket_end', '>=', $currentDate);
                })
                ->count();

            $bencanaTickets = CbossTicket::where('ticket_start', '<=', $currentDate)
                ->where('trouble_category', 'LIKE', "%BENCANA%")
                ->where(function ($query) use ($currentDate) {
                    $query->whereNot('status', 'Closed')
                        ->orWhere('ticket_end', '>=', $currentDate);
                })
                ->count();

            $renovasiCounts[] = $openTickets;
            $relokCounts[] = $relokTickets;
            $bencanaCounts[] = $bencanaTickets;
            $liburCounts[] = $liburTickets - $todayLiburClose;

            $dates[] = $currentDate->format('d M');

            // Lanjut ke hari berikutnya
            $currentDate->addDay();
        }

        return [
            'chart' => [
                'type' => "line",
                'height' => 350,
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
                    'name' => 'Renovasi',
                    'data' => $renovasiCounts,
                ],

                [
                    'name' => 'Relokasi',
                    'data' => $relokCounts,
                ],

                [
                    'name' => 'Libur',
                    'data' => $liburCounts,
                ],

                [
                    'name' => 'Bencana Alam',
                    'data' => $bencanaCounts,
                ],

                // [
                //     'name' => 'Closed Ticket',
                //     // 'data' => [7, 4, 6, 10, 14, 7, 5, 9, 10, 15, 13, 18],
                //     'data' => $closeTT->map(fn(TrendValue $value) => $value->aggregate),

                //     // 'data' => array_column($closeTT, 'total'),
                // ],

                // [
                //     'name' => 'Total Ticket',
                //     // 'data' => [7, 4, 6, 10, 14, 7, 5, 9, 10, 15, 13, 18],
                //     // 'data' => $closeTT->map(fn(TrendValue $value) => $value->aggregate),

                //     'data' => array_column($totalTT, 'total'),
                // ],
            ],

            'legend' => [
                'position' => 'top',
                'fontSize' => '14px',
                'fontWeight' => 400,
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

            'colors' => ['#858599', '#FE4A49', '#FEC620', '#8D0101'],
            'stroke' => [
                // 'curve' => 'smooth',
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

            // 'fill' => [
            //     // 'type' => 'gradient',
            //     'gradient' => [
            //         // 'shade' => 'dark',
            //         // 'type' => 'horizontal',
            //         'enabled' => true,
            //         // 'gradientToColors' => ['#ea580c'],
            //         // 'inverseColors' => true,
            //         'opacityFrom' => 0.55,
            //         'opacityTo' => 0.0,
            //         // 'stops' => [0, 90, 100],
            //     ],
            // ],
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
                // formatter: function (value) {
                //     return value >= 10 ? value : "";
                // }
            },        }
        JS);
    }
}
