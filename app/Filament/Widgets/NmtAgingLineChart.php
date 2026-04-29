<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NmtAgingLineChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'nmtAgingLineChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'NMT Ticket Aging';
    protected static ?string $subheading = 'Average Aging NMT Open';

    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;


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
                ->default(now()->subDays(7)->startOfDay()),
            DatePicker::make('date_end')
                ->default(now()->endOfDay()),
        ];
    }

    protected function getOptions(): array
    {
        $openTT = Trend::model(NmtTickets::class)
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('date_start')
            ->perDay()
            ->count();

        $openCounts = [];
        $dates = [];

        $currentDate = Carbon::parse($this->filterFormData['date_start'])->startOfDay();

        while ($currentDate->lte(Carbon::parse($this->filterFormData['date_end'])->endOfDay())) {
            // Hitung jumlah tiket yang masih Open pada tanggal ini
            $totalTickets = NmtTickets::where('date_start', '<=', $currentDate)
                ->whereNot('problem_detail', 'LIKE', "%RENOVASI%")
                // ->whereNot('problem_detail', 'LIKE', "%LIBUR%")
                ->whereNot('problem_detail', 'LIKE', "%BENCANA%")
                ->whereNot('problem_classification', 'LIKE', "%RELOKASI%")
                ->where(function ($query) use ($currentDate) {
                    $query->where('status', '=', 'OPEN')
                        ->orWhere('closed_date', '>=', $currentDate);
                })
                ->count();

            $sumAging = NmtTickets::where('date_start', '<=', $currentDate)
                ->whereNot('problem_detail', 'LIKE', "%RENOVASI%")
                // ->whereNot('problem_detail', 'LIKE', "%LIBUR%")
                ->whereNot('problem_detail', 'LIKE', "%BENCANA%")
                ->whereNot('problem_classification', 'LIKE', "%RELOKASI%")
                ->where(function ($query) use ($currentDate) {
                    $query->where('status', '=', 'OPEN')
                        ->orWhere('closed_date', '>=', $currentDate);
                })
                ->sum('aging');

            $openCounts[] = intval($sumAging / $totalTickets);
            $dates[] = $currentDate->format('d M');

            // Lanjut ke hari berikutnya
            $currentDate->addDay();
        }

        return [
            // 'theme' => [
            //     'mode' => 'light', //dark
            //     'palette' => 'palette4'
            // ],

            'chart' => [
                'type' => "area",
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
                    'name' => 'Ticket Open',
                    'data' => $openCounts,
                ],
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
}
