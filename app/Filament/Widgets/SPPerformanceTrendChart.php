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
                ->options(ServiceProvider::orderBy('sp_name')->pluck('sp_name', 'sp_id'))
                ->default(fn() => ServiceProvider::whereIn('sp_name', ['PSN', 'MAHAGA'])->pluck('sp_id')->toArray())
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
        ];
    }

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

        $start = Carbon::parse($filterData['date_start'])->startOfDay();
        $end = Carbon::parse($filterData['date_end'])->endOfDay();

        // Generate semua tanggal
        $dates = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $series = [];
        $colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
        $maxTicketInFilteredData = 0;

        foreach ($selectedSpIds as $spId) {
            $sp = ServiceProvider::find($spId);
            if (!$sp || !$sp->total_site) continue;

            $totalSite = $sp->total_site;

            // Trend untuk Ticket (sum today_ticket)
            $ticketTrend = Trend::query(SpPerformance::where('sp_id', $spId))
                ->between($start, $end)
                ->perDay()
                ->sum('today_ticket');

            // Trend untuk Rank (max/avg today_rank)
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

                if ($ticket > $maxTicketInFilteredData) {
                    $maxTicketInFilteredData = $ticket;
                }

                $pct = ($ticket > 0 && $totalSite > 0) ? round(($ticket / $totalSite) * 100, 2) : 0;

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

        $categories = collect($dates)
            ->map(fn($d) => Carbon::parse($d)->translatedFormat('d M'))
            ->values()
            ->toArray();

        // Threshold Atas (+5%)
        $yMaxThreshold = $maxTicketInFilteredData > 0
            ? ceil($maxTicketInFilteredData * 1.05)
            : null;

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
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
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '55%',
                    'borderRadius' => 10,
                    'borderRadiusApplication' => 'end',
                    'dataLabels' => [
                        'position' => 'center', // Posisi label didalam bar
                        'orientation' => 'vertical',
                    ],
                ],
            ],
            'series' => $series,
            'xaxis' => [
                'categories' => $categories,
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
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 5,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
        {
            dataLabels: {
                enabled: true,
                // orientation: 'vertical', // Membuat teks vertikal searah batang bar
                formatter: function (val, opts) {
                    if (!val || val === 0) {
                        return '';
                    }

                    let rank = '';
                    let pct = 0;

                    try {
                        let dataObj = opts.w.config.series[opts.seriesIndex].data[opts.dataPointIndex];
                        rank = dataObj && dataObj.rank ? '#' + dataObj.rank : '';
                        pct = dataObj && dataObj.pct ? dataObj.pct : 0;
                    } catch (e) {}

                    if (rank !== '') {
                        return rank + ' (' + pct + '%)';
                    } else {
                        return pct + '%';
                    }
                },
                offsetY: 0,
                style: {
                    fontSize: '11px',
                    fontWeight: 'bold',
                    colors: ['#ffffff'] // Warna teks putih agar kontras di dalam bar
                },
                background: {
                    enabled: false
                }
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
                                rank = dataObj.rank ? '#' + dataObj.rank : '-';
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
