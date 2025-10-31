<?php

namespace App\Filament\Widgets;

use App\Models\ServiceProvider;
use App\Models\SpPerformance;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\RawJs;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SpPerformanceTrendChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'spPerformanceTrendChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $subheading = 'Daily Ticket Percentage by Service Provider';
    protected static ?string $heading = 'Daily Ticket Percentage by Service Provider';

    /**
     * Dynamic Heading
     */
    protected function getHeading(): ?string
    {
        $filterData = $this->filterFormData;
        $spId = $filterData['sp_id'] ?? "";
        $sp = ServiceProvider::find($spId);

        return $sp ? "{$sp->sp_name} Performance Overview . Total Site {$sp->total_site}" : 'SP Performance Overview';
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getFormSchema(): array
    {
        return [
            Select::make('sp_id')
                ->label('Service Provider')
                ->options(ServiceProvider::pluck('sp_name', 'sp_id'))
                ->default(function () {
                    $sp = ServiceProvider::where('sp_name', 'PSN')->first() // Ganti 'MHG' sesuai nama SP yang diinginkan
                        ?? ServiceProvider::orderBy('sp_name')->first(); // Fallback ke SP pertama urut alfabet
                    return $sp?->sp_id;
                })
                ->native(false)
                ->searchable()
                ->required()
                ->reactive(), // Pastiin re-render pas ganti SP

            Select::make('sp_id2')
                ->label('Service Provider')
                ->options(ServiceProvider::pluck('sp_name', 'sp_id'))
                ->default(function () {
                    $sp = ServiceProvider::where('sp_name', 'MAHAGA')->first() // Ganti 'MHG' sesuai nama SP yang diinginkan
                        ?? ServiceProvider::orderBy('sp_name')->first(); // Fallback ke SP pertama urut alfabet
                    return $sp?->sp_id;
                })
                ->native(false)
                ->searchable()
                ->required()
                ->reactive(), // Pastiin re-render pas ganti SP

            DatePicker::make('date_start')
                ->default(now()->subDays(10)->startOfDay())
                ->reactive(),
            DatePicker::make('date_end')
                ->default(now()->addDays(4)->endOfDay())
                ->reactive(),
        ];
    }

    protected function getOptions(): array
    {
        $filterData = $this->filterFormData;
        $spId = $filterData['sp_id'];
        $spId2 = $filterData['sp_id2'];

        $sp = ServiceProvider::find($spId);
        $sp2 = ServiceProvider::find($spId2);

        if ($sp) {
            $sp_name = $sp->sp_name; // Ambil sp_name dari objek $sp
            $sp_name2 = $sp2->sp_name; // Ambil sp_name dari objek $sp
            // Gunakan $sp_name sesuai kebutuhan
        }

        if (!$sp && !$sp2) {
            return []; // Kalo no SP, chart kosong
        }

        $totalSite = $sp->total_site ?: 1; // Hindari divide by zero, default 1

        $ticketTrends = Trend::query(SpPerformance::where('sp_id', $spId))
            ->between(
                start: Carbon::parse($filterData['date_start'])->startOfDay(),
                end: Carbon::parse($filterData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->sum('today_ticket');

        $percentages = $ticketTrends->map(function (TrendValue $value) use ($totalSite) {
            return round(($value->aggregate / $totalSite) * 100, 2);
        });

        $ticketTrends2 = Trend::query(SpPerformance::where('sp_id', $spId2))
            ->between(
                start: Carbon::parse($filterData['date_start'])->startOfDay(),
                end: Carbon::parse($filterData['date_end'])->endOfDay(),
            )
            ->dateColumn('created_at')
            ->perDay()
            ->sum('today_ticket');

        $percentages2 = $ticketTrends2->map(function (TrendValue $value) use ($totalSite) {
            return round(($value->aggregate / $totalSite) * 100, 2);
        });

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 625,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'autoSelected' => 'pan',
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
                    'name' => $sp_name . ' Ticket Percentage (%)',
                    'data' => $percentages,
                ],
                [
                    'name' => $sp_name2 . ' Ticket Percentage (%)',
                    'data' => $percentages2,
                ],
            ],

            'xaxis' => [
                'categories' => $ticketTrends->map(fn(TrendValue $value) => Carbon::parse($value->date)->translatedFormat('d M')),
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

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
        {
            plotOptions: {
                bar: {
                    borderRadius: 10,
                    borderRadiusApplication: 'end',
                    columnWidth: '50%',
                    barHeight: '50%',
                    dataLabels: {
                        position: 'top',
                    },
                }
            },

            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: "horizontal",
                    shadeIntensity: 0.25,
                    gradientToColors: undefined,
                    inverseColors: true,
                    opacityFrom: 0.85,
                    opacityTo: 0.85,
                    stops: [50, 0, 100]
                },
            },

            stroke: {
                width: 0
            },

            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    if (val == 0) {
                        return "";
                    } else {
                        return val + "%";
                    }
                },
                offsetY: -20,
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold',
                    colors: ["#304758"]
                }
            }
        }
        JS);
    }
}
