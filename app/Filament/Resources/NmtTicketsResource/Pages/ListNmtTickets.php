<?php

namespace App\Filament\Resources\NmtTicketsResource\Pages;

use App\Filament\Resources\NmtTicketsResource;
use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Support\Str;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Webbingbrasil\FilamentCopyActions\Pages\Actions\CopyAction;

class ListNmtTickets extends ListRecords
{
    protected static string $resource = NmtTicketsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ExportAction::make('csv')
                    ->icon('phosphor-file-csv-duotone')
                    ->label("Export to CSV")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('NMT Ticket Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('id')->contains(4)) {
                                    return $query->where('created_by', auth()->id());
                                }

                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                                    return $query->where('engineer_name', auth()->user()->name);
                                }
                            })
                    ]),


                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('NMT Ticket Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)) {
                                    return $query;
                                }

                                if (auth()->user()->roles->pluck('id')->contains(4)) {
                                    return $query->where('created_by', auth()->id());
                                }

                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                                    return $query->where('engineer_name', auth()->user()->name);
                                }
                            })
                    ]),

                CopyAction::make('generate_report')
                    ->label('Generate PMU Report')
                    ->copyable(function () {
                        return static::generateReportString();
                    })
                    ->icon('phosphor-file-txt-duotone'),

            ])
                ->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),

            Action::make('Import Data')
                ->label("Get Data")
                ->action(fn() => Artisan::call('fetch:nmt-tickets'))
                ->requiresConfirmation()
                ->successNotificationTitle('Data berhasil diimport'),
        ];
    }

    protected static function generateReportString(): string
    {
        // Ambil waktu saat ini
        Carbon::setLocale('id');

        // Ambil waktu saat ini
        $now = Carbon::now();
        $date = $now->translatedFormat('l, d F Y'); // Contoh: Minggu, 20 April 2025
        $timeOfDay = static::getTimeOfDay($now);

        // Query untuk mengelompokkan data
        $closed = NmtTickets::where('status', 'CLOSED')
            ->whereDate('closed_date', $now->startOfDay())
            ->with('site')
            ->get();

        $renovasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%renovasi%')
            ->with('site')
            ->get();

        $relokasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_classification', 'LIKE', '%relokasi%')
            ->with('site')
            ->get();

        $liburSekolah = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%libur%')
            ->with('site')
            ->get();

        $bencanaAlam = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%bencana%')
            ->with('site')
            ->get();

        $open = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'NOT LIKE', '%renovasi%')
            ->where('problem_classification', 'NOT LIKE', '%relokasi%')
            ->where('problem_detail', 'NOT LIKE', '%libur%')
            ->where('problem_detail', 'NOT LIKE', '%bencana%')
            ->with('site')
            ->get();

        // Hitung total
        $totalClosed = $closed->count();
        $totalRenovasi = $renovasi->count();
        $totalRelokasi = $relokasi->count();
        $totalLiburSekolah = $liburSekolah->count();
        $totalBencanaAlam = $bencanaAlam->count();
        $totalOpen = $open->count();
        $totalTickets = $totalOpen + $totalClosed;

        // Buat string header report
        $report = "Selamat $timeOfDay,\n\n";
        $report .= "Berikut Report TT tanggal $date:\n\n";
        $report .= "> CATEGORY SL\n";
        $report .= "* ✅ Closed\t: $totalClosed\t\n";
        $report .= "* ❌ Open\t: $totalOpen\t\n";
        $report .= "* ⚠️ Renovasi\t: $totalRenovasi\t\n";
        $report .= "* 🚫 Relokasi\t: $totalRelokasi\t\n";
        $report .= "* ❕ Libur Sekolah\t: $totalLiburSekolah\t\n";
        $report .= "* ❗ Bencana Alam\t: $totalBencanaAlam\t\n\n";
        $report .= "* Total TT\t: $totalTickets\n\n";

        // Detail per kategori
        $report .= static::generateCategoryDetails('✅ TT CLOSED', $closed, true);
        $report .= static::generateCategoryDetails('❌ TT OPEN', $open);
        $report .= static::generateCategoryDetails('🚫 RELOKASI', $relokasi);
        $report .= static::generateCategoryDetails('⚠️ RENOVASI', $renovasi);
        $report .= static::generateCategoryDetails('❕ LIBUR SEKOLAH', $liburSekolah);
        $report .= static::generateCategoryDetails('❗ BENCANA ALAM', $bencanaAlam);

        $report .= "Terimakasih, CC: Pak @Dodo.";

        return $report;
    }

    protected static function getTimeOfDay(Carbon $time): string
    {
        $hour = $time->hour;
        if ($hour >= 5 && $hour < 11) return 'Pagi';
        if ($hour >= 11 && $hour < 15) return 'Siang';
        if ($hour >= 15 && $hour < 18) return 'Sore';
        return 'Malam';
    }

    protected static function generateCategoryDetails(string $title, $tickets, bool $isClosed = false): string
    {
        if ($tickets->isEmpty()) {
            return "========================================================\n$title :\n> Tidak ada data\n\n";
        }

        $details = "========================================================\n\n$title :\n\n";
        foreach ($tickets as $ticket) {
            $siteName = $ticket->site ? $ticket->site->site_name : 'Unknown';
            $details .= "> {$ticket->site_id} $siteName " . ($isClosed ? '✅' : '❌') . "\n";
            if ($isClosed) {
                $actualOnline = Carbon::parse($ticket->actual_online)->format('d M Y');
                $details .= "Actual Online\t: $actualOnline\n";
            } else {
                $details .= "Durasi TT Open\t: {$ticket->aging} Hari\n";
                $targetOnline = Carbon::parse($ticket->target_online)->format('d M Y');
                $details .= "Target Online\t: $targetOnline\n";
                $details .= "Progress\t\t: {$ticket->update_progress}\n";
            }
            $details .= "\n";
        }

        return $details;
    }
}
