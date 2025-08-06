<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\CbossTmo;
use Filament\Forms\Components\DatePicker;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;

class TmoLineChart extends ApexChartWidget
{
    protected static ?string $chartId = 'tmoMaintenanceChart';
    protected static ?string $heading = 'TMO Maintenance';
    protected static ?string $subheading = 'Preventive vs Corrective Maintenance';
    // protected static ?int $contentHeight = 350;

    protected static ?string $pollingInterval = '60s';

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
        $start = Carbon::parse($this->filterFormData['date_start'])->startOfDay();
        $end = Carbon::parse($this->filterFormData['date_end'])->endOfDay();

        // Data Preventive Maintenance (action mengandung "PM")
        $pmData = Trend::query(
            CbossTmo::whereRaw('JSON_CONTAINS(LOWER(action), \'"pm"\')')
        )
            ->between(start: $start, end: $end)
            ->perDay()
            ->dateColumn('tmo_date')
            ->count()
            ->pluck('aggregate')
            ->toArray();

        // Data Corrective Maintenance (action tidak mengandung "PM")
        $cmData = Trend::query(
            CbossTmo::whereRaw('NOT JSON_CONTAINS(LOWER(action), \'"pm"\')')
        )
            ->between(start: $start, end: $end)
            ->perDay()
            ->dateColumn('tmo_date')
            ->count()
            ->pluck('aggregate')
            ->toArray();

        // Label untuk sumbu X (tanggal 7 hari terakhir)
        $labels = [];
        for ($i = 14; $i >= 0; $i--) {
            $labels[] = Carbon::today('Asia/Jakarta')->subDays($i)->format('d M');
        }

        return [
            'chart' => [
                'type' => 'line',
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
                    'name' => 'Preventive Maintenance',
                    'data' => $pmData,
                ],
                [
                    'name' => 'Corrective Maintenance',
                    'data' => $cmData,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#10b981', '#ef4444'], // Hijau untuk PM, Merah untuk CM
            'stroke' => [
                'curve' => 'smooth',
                'width' => 4,
            ],
            'tooltip' => [
                'x' => [
                    'format' => 'dd MMM',
                ],
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
            'markers' => [
                'size' => 4, // Ukuran titik
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff', // Warna garis luar
                'hover' => [
                    'size' => 8 // Ukuran saat di-hover
                ]
            ],
            'legend' => [
                'fontSize' => '14px',
            ],
        ];
    }
}
