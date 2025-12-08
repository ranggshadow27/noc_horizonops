<?php

namespace App\Filament\Widgets;

use App\Models\TmoData;
use Filament\Forms\Components\DatePicker;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TMOAreaChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'tMOAreaChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Maintenance Overall';
    protected static ?string $subheading = 'Summary of TMO PM/CM Data per Area';

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
                ->default(now()->subMonth(1)),
            DatePicker::make('date_end')
                ->default(now()->addMonth(4)),
        ];
    }

    protected function getOptions(): array
    {
        $approvedPMArea1 = Trend::query(
            TmoData::where('tmo_type', 'Preventive Maintenance')
                // ->where('tmo_type', 'Preventive Maintenance')
                ->whereHas('area', function (Builder $query) {
                    $query->where('area', 'Area 1'); // Filter berdasarkan area
                })
        )
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perMonth()
            ->count();

        $approvedCMArea1 = Trend::query(
            TmoData::where('tmo_type', 'Corrective Maintenance')
                // ->where('tmo_type', 'Corrective Maintenance')
                ->whereHas('area', function (Builder $query) {
                    $query->where('area', 'Area 1'); // Filter berdasarkan area
                })
        )
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perMonth()
            ->count();

        $approvedPMArea2 = Trend::query(
            TmoData::where('tmo_type', 'Preventive Maintenance')
                // ->where('tmo_type', 'Corrective Maintenance')
                ->whereHas('area', function (Builder $query) {
                    $query->where('area', 'Area 2'); // Filter berdasarkan area
                })
        )
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perMonth()
            ->count();

        $approvedCMArea2 = Trend::query(
            TmoData::where('tmo_type', 'Corrective Maintenance')
                // ->where('tmo_type', 'Corrective Maintenance')
                ->whereHas('area', function (Builder $query) {
                    $query->where('area', 'Area 2'); // Filter berdasarkan area
                })
        )
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perMonth()
            ->count();

        $approvedPMArea3 = Trend::query(
            TmoData::where('tmo_type', 'Preventive Maintenance')
                // ->where('tmo_type', 'Corrective Maintenance')
                ->whereHas('area', function (Builder $query) {
                    $query->where('area', 'Area 3'); // Filter berdasarkan area
                })
        )
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perMonth()
            ->count();

        $approvedCMArea3 = Trend::query(
            TmoData::where('tmo_type', 'Corrective Maintenance')
                // ->where('tmo_type', 'Corrective Maintenance')
                ->whereHas('area', function (Builder $query) {
                    $query->where('area', 'Area 3'); // Filter berdasarkan area
                })
        )
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->dateColumn('tmo_start_date')
            ->perMonth()
            ->count();


        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'stacked' => true,
                'fontFamily' => 'inherit',
            ],

            'series' => [
                [
                    'name' => 'Area 1 PM',
                    'group' => 'pm',
                    // 'data' => [11, 24, 15, 31, 8, 14]
                    'data' => $approvedPMArea1->map(fn(TrendValue $value) => $value->aggregate),
                ],
                [
                    'name' => 'Area 1 CM',
                    'group' => 'cm',
                    // 'data' => [12, 6, 4, 4, 18, 13]
                    'data' => $approvedCMArea1->map(fn(TrendValue $value) => $value->aggregate),

                ],

                [
                    'name' => 'Area 2 PM',
                    'group' => 'pm',
                    // 'data' => [24, 12, 21, 13, 7, 9]
                    'data' => $approvedPMArea2->map(fn(TrendValue $value) => $value->aggregate),

                ],
                [
                    'name' => 'Area 2 CM',
                    'group' => 'cm',
                    // 'data' => [5, 4, 12, 8, 12, 14]
                    'data' => $approvedCMArea2->map(fn(TrendValue $value) => $value->aggregate),

                ],

                [
                    'name' => 'Area 3 PM',
                    'group' => 'pm',
                    // 'data' => [5, 3, 12, 18, 16, 5]
                    'data' => $approvedPMArea3->map(fn(TrendValue $value) => $value->aggregate),

                ],
                [
                    'name' => 'Area 3 CM',
                    'group' => 'cm',
                    // 'data' => [13, 36, 20, 13, 12, 6]
                    'data' => $approvedCMArea3->map(fn(TrendValue $value) => $value->aggregate),

                ],
                // [
                //     'name' => 'Q2 Budget',
                //     'group' => 'budget',
                //     'data' => [13, 36, 20, 8, 13, 27]
                // ],
                // [
                //     'name' => 'Q2 Actual',
                //     'group' => 'actual',
                //     'data' => [20, 40, 25, 10, 12, 28]
                // ]
            ],

            'xaxis' => [
                // 'categories' => [
                //     'Online advertising',
                //     'Sales Training',
                //     'Print advertising',
                //     'Catalogs',
                //     'Meetings',
                //     'Public relations'
                // ],
                'categories' => $approvedPMArea1->map(fn(TrendValue $value) => Carbon::parse($value->date)->translatedFormat('M y')),

                // 'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            ],

            'stroke' => [
                'width' => 0,
                'colors' => ['#fff']
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

            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
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

            'colors' => ['#80b918', '#434343FF', '#aacc00', '#5A5A5AFF', '#d4d700', '#7E7E7EFF'],


            'legend' => [
                'position' => 'bottom',
                // 'offsetY' => 40
            ]

        ];
    }
}
