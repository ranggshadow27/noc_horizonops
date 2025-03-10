<?php

namespace App\Filament\Widgets;

use App\Models\TmoData;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TmoDeviceProblemChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'tmoDeviceProblemChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Tmo Device Problem';
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
                ->default(Carbon::now()->startOfMonth()),
            DatePicker::make('date_end')
                ->default(now()),
        ];
    }


    protected function getOptions(): array
    {
        $device = ["POE", "Mikrotik", "Grandstream", "Modem", "Transceiver", "Adaptor Modem", "Adaptor Router"];

        $poeTrend = TmoData::where('problem_json', 'like', '%POE%')
            ->whereBetween('tmo_start_date', [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ])->get();

        $poeCount = $poeTrend->map(function ($item) {
            // Decode string menjadi array, jika problem_json adalah string JSON
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            // Hitung berapa kali 'POE' muncul dalam array
            return count(array_filter($problemArray, fn($value) =>  str_contains(Str::lower($value), 'poe')));
            // return $problemArray;
        })->sum();

        $routerTrend = TmoData::where('problem_json', 'like', '%ROUTER%')
            ->whereBetween('tmo_start_date', [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ])->get();

        $routerCount = $routerTrend->map(function ($item) {
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            return count(array_filter($problemArray, fn($value) =>  Str::lower($value) === 'router mikrotik'));
            // return $problemArray;
        })->sum();

        $routergwnCount = $routerTrend->map(function ($item) {
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            return count(array_filter($problemArray, fn($value) =>  Str::lower($value) === 'router grandstream'));
            // return $problemArray;
        })->sum();

        $modemTrend = TmoData::where('problem_json', 'like', '%MODEM%')
            ->whereBetween('tmo_start_date', [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ])->get();

        $modemCount = $modemTrend->map(function ($item) {
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            return count(array_filter($problemArray, fn($value) =>  str_contains(Str::lower($value), 'modem')));
            // return $problemArray;
        })->sum();

        $transceiverTrend = TmoData::where('problem_json', 'like', '%TRANSCEIVER%')
            ->whereBetween('tmo_start_date', [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ])->get();

        $transceiverCount = $transceiverTrend->map(function ($item) {
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            return count(array_filter($problemArray, fn($value) =>  str_contains(Str::lower($value), 'transceiver')));
            // return $problemArray;
        })->sum();

        $adaptormTrend = TmoData::where('problem_json', 'like', '%ADAPTOR MODEM%')
            ->whereBetween('tmo_start_date', [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ])->get();

        $adaptormCount = $adaptormTrend->map(function ($item) {
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            return count(array_filter($problemArray, fn($value) =>  str_contains(Str::lower($value), 'adaptor modem')));
            // return $problemArray;
        })->sum();

        $adaptorrTrend = TmoData::where('problem_json', 'like', '%ADAPTOR ROUTER%')
            ->whereBetween('tmo_start_date', [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ])->get();

        $adaptorrCount = $adaptorrTrend->map(function ($item) {
            $problemArray = $item->problem_json; // Pastikan problem_json adalah JSON
            return count(array_filter($problemArray, fn($value) =>  str_contains(Str::lower($value), 'adaptor router')));
            // return $problemArray;
        })->sum();

        // dd($poeCount);


        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                // 'stacked' => true,
                'fontFamily' => 'inherit',
            ],

            'series' => [
                [
                    'name' => 'POE',
                    'group' => 'pm',
                    'data' => [$poeCount, $routerCount, $routergwnCount, $modemCount, $transceiverCount, $adaptormCount, $adaptorrCount]
                    // 'data' => $poe->map(fn(TrendValue $value) => $value->aggregate),
                ],
                // [
                //     'name' => 'Router',
                //     'group' => 'pm',
                //     // 'data' => [11, 24, 15, 31, 8, 14]
                //     'data' => $router->map(fn(TrendValue $value) => $value->aggregate),
                // ],
            ],

            'xaxis' => [
                'categories' => $device,
            ],

            'stroke' => [
                'width' => 0,
                'colors' => ['#fff']
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

            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 12,
                    'borderRadiusApplication' => 'end',
                ]
            ],

            'dataLabels' => [
                'style' => [
                    'fontSize' => '12px',
                ]
            ],

            'fill' => [
                'opacity' => 1
            ],


            'legend' => [
                'position' => 'bottom',
            ]
        ];
    }
}
