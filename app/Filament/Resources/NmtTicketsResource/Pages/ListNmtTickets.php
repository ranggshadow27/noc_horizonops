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

            ])
                ->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),

            Action::make('Import Data')
                ->label("Get Data")
                ->action(fn() => Artisan::call('fetch:nmt-tickets'))
                ->requiresConfirmation()
                ->successNotificationTitle('Data berhasil diimport'),

            CopyAction::make()
                ->label('Copy Data to Clipboard')
                ->hidden()
                ->copyable(function () {
                    // Cek waktu saat ini
                    $hour = date('H');
                    $salam = ($hour < 12) ? 'Selamat Pagi' : (($hour < 18) ? 'Selamat Siang' : 'Selamat Malam');

                    // Format tanggal
                    $tanggal = date('l, j F Y'); // Contoh: Selasa, 2 April 2024

                    // Ambil data tiket dengan status OPEN dan aging di atas 20
                    $data = NmtTickets::with('site')
                        ->where('status', 'OPEN')
                        ->where('aging', '>', 20)
                        ->get();

                    // Kelompokkan berdasarkan problem_category
                    $groupedData = $data->groupBy('problem_detail');

                    // Siapkan string untuk hasil
                    $result = "$salam,\n\nBerikut Report Site $tanggal:\n\n";

                    // Daftar kategori yang ingin dipisahkan
                    $categoriesToSeparate = ['BENCANA ALAM', 'LIBUR SEKOLAH', 'RENOVASI'];

                    // Loop untuk kategori yang dipisahkan
                    foreach ($categoriesToSeparate as $category) {
                        // Filter data yang mengandung kategori
                        $filteredItems = $groupedData->filter(function ($items, $key) use ($category) {
                            return Str::contains($key, $category);
                        });

                        if ($filteredItems->isNotEmpty()) {
                            $result .= "$category:\n";
                            $result .= $filteredItems->flatten()->map(function ($item) {
                                return "Site Name: " . $item->site->site_name . "\nProvinsi: " . $item->site_province . "\nKeterangan: " . $item->update_progress;
                            })->implode("\n\n") . "\n\n";
                        }
                    }

                    // Ambil tiket OPEN yang tidak termasuk dalam kategori di atas
                    $openTickets = $data->filter(function ($item) use ($categoriesToSeparate) {
                        foreach ($categoriesToSeparate as $category) {
                            if (Str::contains($item->problem_detail, $category)) {
                                return false; // Jika mengandung kategori, jangan masukkan
                            }
                        }
                        return true; // Masukkan jika tidak mengandung kategori
                    });

                    if ($openTickets->isNotEmpty()) {
                        $result .= "OPEN:\n";
                        $result .= $openTickets->map(function ($item) {
                            return "Site Name: " . $item->site->site_name . "\nProvinsi: " . $item->site_province . "\nKeterangan: " . $item->update_progress;
                        })->implode("\n\n") . "\n\n";
                    }

                    // Tambahkan penutup
                    $result .= "Terimakasih.";

                    return $result;
                }),
        ];
    }
}
