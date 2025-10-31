<?php

namespace App\Filament\Pages;

use App\Imports\BulkConfigImport;
use App\Models\SiteDetail;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GenerateMikrotikConfig extends Page
{
    use HasPageShield;

    protected static ?string $navigationGroup = 'Operational';
    protected static ?string $navigationLabel = 'Generate Router Config';
    protected static ?string $navigationIcon = 'phosphor-nut-duotone';
    protected static string $view = 'filament.pages.generate-mikrotik-config';
    protected static ?string $title = 'Configuration Generator';
    protected ?string $subheading = 'Auto Generate Router Configuration (Mikrotik/Grandstream)';

    public $siteId = '';
    public $timezone = '';
    public $configType = 'mikrotik';

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::ThreeExtraLarge;
    }

    public function getFormSchema(): array
    {
        return [
            Section::make('Configuration Generator')
                ->description('Router configuration made simple and reliable')
                ->schema([
                    Select::make('siteId')
                        ->label('Site ID')
                        ->options(function () {
                            return SiteDetail::pluck('site_name', 'site_id')->mapWithKeys(function ($siteName, $site_id) {
                                return [$site_id => "$site_id - $siteName"];
                            })->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search): array {
                            $sites = SiteDetail::where('site_id', 'like', "%{$search}%")
                                ->orWhere('site_name', 'like', "%{$search}%")
                                ->limit(10)
                                ->get();
                            return $sites->mapWithKeys(function ($site) {
                                return [$site->site_id => "{$site->site_id} - {$site->site_name}"];
                            })->toArray();
                        })
                        ->getOptionLabelFromRecordUsing(function (SiteDetail $record): string {
                            return "{$record->site_id} - {$record->site_name}";
                        })
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $site = SiteDetail::find($state);
                            $timezone = $site ? $this->getTimezoneByProvince($site->province) : 'Asia/Jakarta';
                            $set('timezone', $timezone);
                        })
                        ->placeholder("Select a site ID or site name")
                        ->columnSpanFull(),

                    Radio::make('configType')
                        ->label('Select Config Type :')
                        ->options([
                            'mikrotik' => 'Mikrotik Router Config',
                            'grandstream' => 'Grandstream Router Config',
                        ])
                        ->descriptions([
                            'mikrotik' => 'Output file .rsc',
                            'grandstream' => 'Output file .bin',
                        ])
                        ->default('mikrotik')
                        ->required()
                        ->inline()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('configType', $state);
                        })
                        ->disabled(function (callable $get): bool {
                            return empty($get('siteId'));
                        })
                        ->live()
                        // ->inlineLabel(false)
                        ->columnSpanFull(),

                    TextInput::make('timezone')
                        ->label('Timezone')
                        ->required()
                        ->placeholder("This input is auto generated")
                        ->disabled()
                        ->columnSpanFull(),

                    Forms\Components\Actions::make([
                        Action::make('generate')
                            ->label('Generate Config')
                            ->action('generateConfig')
                            ->disabled(function (callable $get): bool {
                                return empty($get('siteId')) || empty($get('timezone')) || empty($get('configType'));
                            }),
                    ])->fullWidth()->columnSpanFull(),
                ])
                ->headerActions([
                    Action::make('bulkConfig')
                        ->label('Bulk Configuration')
                        ->color('gray')
                        ->icon('phosphor-gear-duotone')
                        ->form([
                            Select::make('bulkConfigType')
                                ->label('Configuration Type')
                                ->native(false)
                                ->options([
                                    'mikrotik' => 'Mikrotik RSC',
                                    'grandstream' => 'Grandstream Bin',
                                ])
                                ->required()
                                ->placeholder("Select configuration type")
                                ->columnSpanFull(),

                            FileUpload::make('excel_file')
                                ->label('Excel File')
                                // ->acceptedFileTypes([
                                //     'application/vnd.ms-excel',
                                //     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                // ])
                                ->rules(['file', 'mimes:xls,xlsx'])
                                ->storeFiles(false) // Jangan simpan permanen
                                ->required()
                                ->hint('Excel file must be include ["Nama Lokasi", "Timezone", "IP Modem"] in the first row')
                                ->columnSpanFull(),
                        ])
                        ->action(function (array $data) {
                            try {
                                $file = $data['excel_file'];
                                $configType = $data['bulkConfigType'];

                                if ($file instanceof \Illuminate\Http\UploadedFile) {
                                    // Simpan file sementara
                                    $tempPath = $file->store('temp', 'local');
                                    $fullPath = storage_path('app/' . $tempPath);
                                    Log::info('Excel file stored at: ' . $fullPath);

                                    // Generate nama file ZIP
                                    $zipFileName = 'router_configs_export_' . Carbon::now()->format('dmy-Hi') . '.zip';
                                    $zipFilePath = storage_path('app/temp/' . $zipFileName);

                                    // Jalankan import, kirim zipFilePath ke importer
                                    Excel::import(new BulkConfigImport($configType, $zipFilePath), $fullPath);

                                    // Cek apakah file ZIP ada
                                    if (!file_exists($zipFilePath)) {
                                        Log::error('ZIP file not found at: ' . $zipFilePath);
                                        throw new \Exception('File ZIP tidak ditemukan setelah generate.');
                                    }

                                    // Hapus file Excel sementara
                                    Storage::disk('local')->delete($tempPath);

                                    Notification::make()
                                        ->title('Success')
                                        ->body('Bulk configurations generated successfully.')
                                        ->success()
                                        ->send();

                                    // Kirim file ZIP ke browser
                                    return response()->download(
                                        $zipFilePath,
                                        $zipFileName,
                                        ['Content-Type' => 'application/zip']
                                    )->deleteFileAfterSend(true);
                                } else {
                                    throw new \Exception('Invalid file upload.');
                                }
                            } catch (\Exception $e) {
                                Log::error('Bulk config generation failed: ' . $e->getMessage());
                                Notification::make()
                                    ->title('Error')
                                    ->body('Gagal generate bulk config: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                                throw $e;
                            }
                        })
                        ->modalHeading('Generate Configuration (Bulk)')
                        ->modalDescription('Bulk generate router configuration for multiple sites')
                        ->modalSubmitActionLabel('Generate Config')
                        ->modalWidth(MaxWidth::ThreeExtraLarge),
                ]),
        ];
    }

    public function generateConfig()
    {
        try {
            $site = SiteDetail::where('site_id', $this->siteId)->first();

            if (!$site) {
                Notification::make()->title('Error')->body('Site ID tidak ditemukan.')->danger()->send();
                throw new \Exception('Site ID tidak ditemukan.');
            }

            $deviceNetwork = $site->deviceNetworks;

            if (!$deviceNetwork->modem_ip || !filter_var($deviceNetwork->modem_ip, FILTER_VALIDATE_IP)) {
                Notification::make()->title('Error')->body('Modem IP tidak valid atau kosong.')->danger()->send();
                throw new \Exception('Modem IP tidak valid atau kosong.');
            }

            $fileName = str_replace(' ', ' ', trim($site->site_name));
            $cleanFileName = Str::replace(['-', '.', '/', '(', ')', "'"], '', $fileName);

            if ($this->configType === 'mikrotik') {
                $ipParts = explode('.', $deviceNetwork->modem_ip);
                if (count($ipParts) !== 4) {
                    Notification::make()->title('Error')->body('Format Modem IP tidak valid.')->danger()->send();
                    throw new \Exception('Format Modem IP tidak valid.');
                }

                $ipParts[3] = (int)$ipParts[3] - 1;
                if ($ipParts[3] < 0) {
                    Notification::make()->title('Error')->body('Digit terakhir Modem IP tidak bisa dikurangi (sudah 0).')->danger()->send();
                    throw new \Exception('Digit terakhir Modem IP tidak bisa dikurangi (sudah 0).');
                }
                $ipNetwork = implode('.', $ipParts);

                $template = Storage::disk('public')->get('templates/template_mikrotik.txt');

                $replacements = [
                    '${IP_NETWORK}' => $ipNetwork,
                    '${IP_MODEM}' => $deviceNetwork->modem_ip,
                    '${IP_MIKROTIK}' => $deviceNetwork->router_ip,
                    '${IP_AP1}' => $deviceNetwork->ap1_ip,
                    '${IP_AP2}' => $deviceNetwork->ap2_ip,
                    '${NAMA_LOKASI}' => $site->site_name,
                    '${SNMP_STRING}' => "MHGISPNet",
                    '${Password}' => "adminmhg123",
                    '${TIMEZONE}' => $this->timezone,
                ];

                $configContent = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $template
                );

                $outputFileName = $cleanFileName . '.rsc';
                $contentType = 'text/plain';
                Storage::put('temp/' . $outputFileName, $configContent);

                $successMessage = 'Mikrotik RSC configuration generated successfully.';
            } else {
                $ipParts = explode('.', $deviceNetwork->ap2_ip);
                if (count($ipParts) !== 4) {
                    Notification::make()->title('Error')->body('Format AP2 IP tidak valid.')->danger()->send();
                    throw new \Exception('Format AP2 IP tidak valid.');
                }

                $ipParts[3] = (int)$ipParts[3] + 1;
                if ($ipParts[3] < 0) {
                    Notification::make()->title('Error')->body('Digit terakhir AP2 IP tidak bisa dikurangi (sudah 0).')->danger()->send();
                    throw new \Exception('Digit terakhir AP2 IP tidak bisa dikurangi (sudah 0).');
                }
                $ipNetwork = implode('.', $ipParts);

                $template = Storage::disk('public')->get('templates/template_gs.txt');

                $replacements = [
                    '{$IP_MODEM}' => $deviceNetwork->modem_ip,
                    'IP BACKUP' => $ipNetwork,
                    '{$IP_ROUTER}' => $deviceNetwork->router_ip,
                    '{$IP_AP1}' => $deviceNetwork->ap1_ip,
                    '{$IP_AP2}' => $deviceNetwork->ap2_ip,
                    '{$NAMA_LOKASI}' => $site->site_name,
                    '{$TIMEZONE}' => $this->timezone,
                ];

                $configContent = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $template
                );

                $txtFileName = $cleanFileName . '_gscfg';
                $outputFileName = $cleanFileName . '_gscfg.bin';
                Storage::put('temp/' . $txtFileName, $configContent);

                $txtFilePath = storage_path('app/temp/' . $txtFileName);
                $binFilePath = base_path('bin/' . $outputFileName);
                $binFolderPath = base_path('bin');

                $command = sprintf('cd "%s" && ./gscfgtool -t GWN7003 -e "%s"', $binFolderPath, $txtFilePath);
                $output = shell_exec($command . ' 2>&1');

                if (!file_exists($binFilePath)) {
                    Notification::make()->title('Error')->body('Gagal mengenkripsi file ke .bin: ' . $output)->danger()->send();
                    throw new \Exception('Gagal mengenkripsi file ke .bin: ' . $output);
                }

                $moveBinCommand = sprintf('cd "%s" && mv "%s" ../storage/app/temp', $binFolderPath, $outputFileName);
                $moveBinCommandoutput = shell_exec($moveBinCommand . ' 2>&1');

                // dd($moveBinCommand);

                // Storage::delete('temp/' . $txtFileName);
                $contentType = 'application/octet-stream';
                $successMessage = 'Grandstream bin configuration generated successfully.';
            }

            Notification::make()->title('Success')->body($successMessage)->success()->send();

            return response()->download(
                storage_path('app/temp/' . $outputFileName),
                $outputFileName,
                ['Content-Type' => $contentType]
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body('Gagal generate config: ' . $e->getMessage())->danger()->send();
            throw $e;
        }
    }

    private function getTimezoneByProvince($province)
    {
        $mapping = [
            'aceh' => 'Asia/Jakarta',
            'sumatera utara' => 'Asia/Jakarta',
            'sumatera barat' => 'Asia/Jakarta',
            'riau' => 'Asia/Jakarta',
            'jambi' => 'Asia/Jakarta',
            'sumatera selatan' => 'Asia/Jakarta',
            'bengkulu' => 'Asia/Jakarta',
            'lampung' => 'Asia/Jakarta',
            'kepulauan bangka belitung' => 'Asia/Jakarta',
            'kepulauan riau' => 'Asia/Jakarta',
            'dki jakarta' => 'Asia/Jakarta',
            'jawa barat' => 'Asia/Jakarta',
            'jawa tengah' => 'Asia/Jakarta',
            'di yogyakarta' => 'Asia/Jakarta',
            'jawa timur' => 'Asia/Jakarta',
            'banten' => 'Asia/Jakarta',
            'kalimantan barat' => 'Asia/Jakarta',
            'kalimantan tengah' => 'Asia/Jakarta',
            'kalimantan selatan' => 'Asia/Makassar',
            'kalimantan timur' => 'Asia/Makassar',
            'kalimantan utara' => 'Asia/Makassar',
            'sulawesi utara' => 'Asia/Makassar',
            'sulawesi tengah' => 'Asia/Makassar',
            'sulawesi selatan' => 'Asia/Makassar',
            'sulawesi tenggara' => 'Asia/Makassar',
            'gorontalo' => 'Asia/Makassar',
            'sulawesi barat' => 'Asia/Makassar',
            'bali' => 'Asia/Makassar',
            'nusa tenggara barat' => 'Asia/Makassar',
            'nusa tenggara timur' => 'Asia/Makassar',
            'maluku' => 'Asia/Jayapura',
            'maluku utara' => 'Asia/Jayapura',
            'papua' => 'Asia/Jayapura',
            'papua barat' => 'Asia/Jayapura',
        ];

        $province = strtolower(trim($province));
        return $mapping[$province] ?? 'Asia/Jakarta';
    }
}
