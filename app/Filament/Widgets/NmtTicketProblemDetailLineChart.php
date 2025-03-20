<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NmtTicketProblemDetailLineChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'nmtTicketProblemDetailLineChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'NmtTicketProblemDetailLineChart';

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
        // Hitung total tiket Open Renovasi sebelum start date
        $initialrelokasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_classification', 'PENGAJUAN RELOKASI')
            ->whereDate('date_start', '<', Carbon::parse($this->filterFormData['date_start']))
            ->count();


        // Ambil data per hari dengan Laravel Trend
        $trendrelokasi = Trend::query(NmtTickets::where('status', 'OPEN')
            ->where('problem_classification', 'PENGAJUAN RELOKASI'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('date_start')
            ->perDay()
            ->count();

        // Hitung akumulasi mulai dari initialCount
        $relokasiDates = [];
        $relokasiCounts = [];
        $relokasiSum = $initialrelokasi; // Mulai dari jumlah tiket sebelum start date

        foreach ($trendrelokasi as $data) {
            $relokasiSum += $data->aggregate; // Akumulasi dari hari sebelumnya
            $relokasiDates[] = Carbon::parse($data->date)->format('d M');
            $relokasiCounts[] = $relokasiSum;
        }

        // Hitung total tiket Open Renovasi sebelum start date
        $initialRenovasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'RENOVASI GEDUNG')
            ->whereDate('date_start', '<', Carbon::parse($this->filterFormData['date_start']))
            ->count();


        // Ambil data per hari dengan Laravel Trend
        $trendRenovasi = Trend::query(NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'RENOVASI GEDUNG'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('date_start')
            ->perDay()
            ->count();

        // Hitung akumulasi mulai dari initialCount
        $renovasiDates = [];
        $renovasiCounts = [];
        $renovasiSum = $initialRenovasi; // Mulai dari jumlah tiket sebelum start date

        foreach ($trendRenovasi as $data) {
            $renovasiSum += $data->aggregate; // Akumulasi dari hari sebelumnya
            $renovasiDates[] = Carbon::parse($data->date)->format('d M');
            $renovasiCounts[] = $renovasiSum;
        }

        // Hitung total tiket Open Renovasi sebelum start date
        $initialOpen = NmtTickets::where('status', 'OPEN')
            ->whereDate('date_start', '<', Carbon::parse($this->filterFormData['date_start']))
            ->count();

        // Ambil data per hari dengan Laravel Trend
        $trendOpen = Trend::query(NmtTickets::where('status', 'OPEN'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('date_start')
            ->perDay()
            ->count();

        // Hitung akumulasi mulai dari initialCount
        $openDates = [];
        $openCounts = [];
        $openSum = $initialOpen; // Mulai dari jumlah tiket sebelum start date

        foreach ($trendOpen as $data) {
            $openSum += $data->aggregate; // Akumulasi dari hari sebelumnya
            $openDates[] = Carbon::parse($data->date)->format('d M');
            $openCounts[] = $openSum;
        }

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
                    'name' => 'Ticket Renovasi',
                    'data' => $renovasiCounts,
                ],

                [
                    'name' => 'Ticket Relokasi',
                    'data' => $relokasiCounts,
                ],

                [
                    'name' => 'Ticket Open',
                    'data' => $openCounts,
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
                'categories' => $renovasiDates,
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
