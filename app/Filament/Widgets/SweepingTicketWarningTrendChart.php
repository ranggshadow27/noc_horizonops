<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Filament\Forms\Components\DatePicker;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SweepingTicketWarningTrendChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'sweepingTicketWarningTrendChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Warning Sweeping Overview';
    protected static ?string $subheading = 'Trends in Sweeping Warning Sites by Classification';

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
        $openTT = Trend::model(SweepingTicket::class)
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->count();

        $warningClose = Trend::query(SweepingTicket::where('classification', 'WARNING')
            ->where('status', 'CLOSED'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->count();

        $warningNso = Trend::query(SweepingTicket::where('classification', 'WARNING')
            ->where('status', 'LIKE', '%FU KE NSO%'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->count();


        $warningTerFU = Trend::query(SweepingTicket::where('classification', 'WARNING')
            ->where('status', 'LIKE', '%FOLLOW UP%'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->count();

        $warningPending = Trend::query(SweepingTicket::where('classification', 'WARNING')
            ->where('status', 'LIKE', '%TIDAK RESPON%'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->count();

        $warningOpen = Trend::query(SweepingTicket::where('classification', 'WARNING')
            ->where('status', 'OPEN'))
            ->between(
                start: Carbon::parse($this->filterFormData['date_start'])->startOfDay(),
                end: Carbon::parse($this->filterFormData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->count();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 380,
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
                    'name' => 'On Progress',
                    'data' => $warningNso->map(function (TrendValue $value, $index) use ($warningTerFU) {
                        return $value->aggregate + $warningTerFU[$index]->aggregate;
                    }),
                ],

                [
                    'name' => 'Closed',
                    'data' => $warningClose->map(fn(TrendValue $value) => $value->aggregate),

                ],

                [
                    'name' => 'Pending',
                    'data' => $warningPending->map(
                        function (TrendValue $value, $index) use ($warningOpen) {
                            return $value->aggregate + $warningOpen[$index]->aggregate;
                        }
                    ),
                ],
            ],

            'xaxis' => [
                'categories' => $openTT->map(fn(TrendValue $value) => Carbon::parse($value->date)->translatedFormat('d M')),
            ],

            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],

            // 'colors' => ['#f59e0b'],

            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 'bold',
                    // 'colors' => ['#fff'], // Ubah warna teks agar terlihat jelas
                ],
                'background' => [
                    'enabled' => true, // Tambahkan background ke label
                    'borderRadius' => 3,
                    'opacity' => 0.7
                ],
            ],

            'grid' => [
                'strokeDashArray' => 10,
                'position' => 'back',
                'clipMarkers' => true,
                'yaxis' => [
                    'lines' => [
                        'show' => true
                    ]
                ],
            ],

            'legend' => [
                'fontSize' => '14px',
                'fontWeight' => 600,
            ],

        ];
    }
}
