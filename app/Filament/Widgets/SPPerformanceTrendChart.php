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
use Filament\Forms\Components\Toggle;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SPPerformanceTrendChart extends ApexChartWidget
{
    protected static ?string $chartId = 'spPerformanceTrendChart';
    protected static ?string $heading = 'Daily Tickets & Ranking by Service Provider';
    protected static ?string $subheading = 'Daily Tickets & Ranking by Service Provider';

    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '300s';
    protected static bool $deferLoading = true;

    protected function getHeading(): ?string
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        if (empty($selectedSpIds)) {
            return 'SP Performance Overview';
        }

        $sps = ServiceProvider::whereIn('sp_id', $selectedSpIds)->get();
        $names = $sps->pluck('sp_name')->implode(' vs ');

        return "{$names}";
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('sp_ids')
                ->label('Service Providers')
                ->multiple()
                // ->options(ServiceProvider::orderBy('sp_name')->pluck('sp_name', 'sp_id'))
                ->options(
                    ServiceProvider::whereIn('sp_name', ['DUTAKOM', 'KTP', 'MAHAGA', 'PIM', 'PSN', 'TELENET', 'XL'])
                        ->orderBy('sp_name')
                        ->pluck('sp_name', 'sp_id')
                )
                ->default(fn() => ServiceProvider::whereIn('sp_name', ['DUTAKOM', 'KTP', 'MAHAGA', 'PIM', 'PSN', 'TELENET', 'XL'])->pluck('sp_id')->toArray())
                ->searchable()
                ->reactive()
                ->placeholder('Pilih SP...')
                ->required(),

            DatePicker::make('date_start')
                ->label('Start Date')
                ->default(now()->subDays(7)->startOfDay())
                ->reactive(),

            DatePicker::make('date_end')
                ->label('End Date')
                ->default(now()->endOfDay())
                ->reactive(),

            Toggle::make('show_data_labels')
                ->label('Show Data Labels')
                ->default(false) // Default aktif/terlihat
                ->reactive(),
        ];
    }

    // File: SPPerformanceTrendChart.php

    protected function getOptions(): array
    {
        $filterData = $this->filterFormData;
        $selectedSpIds = $filterData['sp_ids'] ?? [];

        if (empty($selectedSpIds)) {
            return [
                'series' => [],
                'xaxis' => ['categories' => []],
            ];
        }

        // Cek status toggle show_data_labels (default true)
        $showDataLabels = $filterData['show_data_labels'] ?? true;

        $start = Carbon::parse($filterData['date_start'])->startOfDay();
        $end = Carbon::parse($filterData['date_end'])->endOfDay();

        // 1. Generate semua tanggal dalam rentang filter
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $series = [];
        $colors = ['#8B5CF6', '#EF4444', '#10B981', '#F59E0B', '#3B82F6', '#EC4899', '#005921'];
        $maxTicketInFilteredData = 0;

        foreach ($selectedSpIds as $spId) {
            $sp = ServiceProvider::find($spId);
            if (!$sp || !$sp->total_site) continue;

            $totalSite = $sp->total_site;

            // Trend Ticket
            $ticketTrend = Trend::query(SpPerformance::where('sp_id', $spId))
                ->between($start, $end)
                ->perDay()
                ->sum('today_ticket');

            // Trend Rank
            $rankTrend = Trend::query(SpPerformance::where('sp_id', $spId))
                ->between($start, $end)
                ->perDay()
                ->max('today_rank');

            $ticketData = $ticketTrend->map(fn(TrendValue $value) => [
                'date' => $value->date,
                'value' => $value->aggregate,
            ])->pluck('value', 'date')->toArray();

            $rankData = $rankTrend->map(fn(TrendValue $value) => [
                'date' => $value->date,
                'value' => $value->aggregate,
            ])->pluck('value', 'date')->toArray();

            $formattedData = [];
            foreach ($dates as $date) {
                $ticket = $ticketData[$date] ?? 0;
                $rank = $rankData[$date] ?? null;

                // --- PERUBAHAN DI SINI ---
                // Jika ticket == 0 ATAU null (atau tidak ada rank), LOMPATI tanggal ini!
                if ($ticket == 0 && empty($rank)) {
                    continue;
                }

                if ($ticket > $maxTicketInFilteredData) {
                    $maxTicketInFilteredData = $ticket;
                }

                $pct = ($ticket > 0 && $totalSite > 0) ? round(($ticket / $totalSite) * 100, 2) : 0;

                // ApexCharts bisa menerima x-value langsung berupa label tanggal di dalam object data
                $formattedData[] = [
                    'x' => Carbon::parse($date)->translatedFormat('d M'),
                    'y' => $ticket,
                    'rank' => $rank,
                    'pct' => $pct,
                ];
            }

            $series[] = [
                'name' => $sp->sp_name,
                'data' => $formattedData,
            ];
        }

        // Threshold Atas (+5%)
        $yMaxThreshold = $maxTicketInFilteredData > 0
            ? ceil($maxTicketInFilteredData * 1.05)
            : null;

        return [
            'chart' => [
                'type' => 'line',
                'height' => 500,
                'background' => '#ffffff00',
                'fontFamily' => 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
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
            'dataLabels' => [
                'enabled' => (bool) $showDataLabels, // Nilai boolean dinamis dari toggle
                'offsetY' => -8,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 'bold',
                    'colors' => ['#374151']
                ],
                'background' => [
                    'enabled' => true,
                    'foreColor' => '#ffffff',
                    'borderRadius' => 4,
                    'padding' => 4,
                    'opacity' => 0.9,
                    'borderWidth' => 1,
                    'borderColor' => '#e5e7eb'
                ]
            ],
            'animations' => [
                'enabled' => true,
                'dynamicAnimation' => [
                    'enabled' => false // Matikan animasi dinamis saat kalkulasi zoom/scroll
                ]
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 4,
            ],
            'markers' => [
                'size' => 6,
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff',
            ],
            'series' => $series,
            // HAPUS / JANGAN GUNAKAN 'xaxis' => ['categories' => $categories] KARENA TIAP TANGGAL SUDAH DI-MAP LEWAT 'x' DI ATAS
            'xaxis' => [
                'type' => 'category',
            ],
            'yaxis' => [
                'min' => 0,
                'max' => $yMaxThreshold,
            ],
            'colors' => array_slice($colors, 0, count($series)),
            'legend' => [
                'position' => 'top',
            ],
            'grid' => [
                'show' => true,
                'borderColor' => 'rgba(156, 163, 175, 0.2)',
                'strokeDashArray' => 5,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
    {
        dataLabels: {
            formatter: function (val, opts) {
                if (!val || val === 0) {
                    return '';
                }

                let rank = '';
                let pct = 0;

                try {
                    let dataObj = opts.w.config.series[opts.seriesIndex].data[opts.dataPointIndex];
                    rank = dataObj && dataObj.rank ?  dataObj.rank : '';
                    pct = dataObj && dataObj.pct ? dataObj.pct : 0;
                } catch (e) {}

                if (rank !== '') {
                    return rank;
                } else {
                    return pct + '%';
                }
            },
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: function(val, opts) {
                    if (!val && val !== 0) return val;

                    let rank = '-';
                    let pct = '0%';

                    try {
                        let dataObj = opts.w.config.series[opts.seriesIndex].data[opts.dataPointIndex];
                        if (dataObj) {
                            rank = dataObj.rank ?  dataObj.rank : '-';
                            pct = (dataObj.pct || 0) + '%';
                        }
                    } catch (e) {}

                    return val + ' Ticket | ' + pct + ' | Rank: ' + rank;
                }
            }
        }
    }
    JS);
    }

    protected function dehydrateStateUsing(): array
    {
        return [
            'options' => $this->getOptions(),
        ];
    }
}
