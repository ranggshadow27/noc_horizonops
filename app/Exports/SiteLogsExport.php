<?php

namespace App\Exports;

use App\Models\SiteLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SiteLogsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $site_id;

    public function __construct(string $site_id)
    {
        $this->site_id = $site_id;
    }

    public function query()
    {
        return SiteLog::query()->where('site_id', $this->site_id);
    }

    public function headings(): array
    {
        return [
            'Log ID',
            'Site ID',
            'Modem Uptime (min)',
            'Modem Last Up',
            'Traffic Uptime (min)',
            'Sensor Status',
            'NMT Ticket',
            'Created At',
        ];
    }

    public function map($siteLog): array
    {
        return [
            $siteLog->site_log_id,
            $siteLog->site_id,
            $siteLog->modem_uptime * 10,
            $siteLog->modem_last_up ? $siteLog->modem_last_up->toDateTimeString() : '-',
            $siteLog->traffic_uptime * 10,
            $siteLog->sensor_status ?? '-',
            $siteLog->nmt_ticket ?? '-',
            $siteLog->created_at->toDateTimeString(),
        ];
    }
}
