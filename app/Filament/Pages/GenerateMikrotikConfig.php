<?php

namespace App\Filament\Pages;

use App\Models\SiteDetail;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Str;


class GenerateMikrotikConfig extends Page
{
    use HasPageShield;

    protected static ?string $navigationGroup = 'Operational';
    protected static ?string $navigationLabel = 'Generate Router Config';
    protected static ?string $navigationIcon = 'phosphor-nut-duotone';

    // protected static ?string $navigationIcon = 'heroicon-o-document-download';
    protected static string $view = 'filament.pages.generate-mikrotik-config';

    protected static ?string $title = 'Generate Configuration';
    protected ?string $subheading = 'Auto Generate Router Configuration (Mikrotik/Grandstream)';


    public $siteId = '';
    public $timezone = '';
    public $actionDisabled = false;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Prose;
    }

    public function getFormSchema(): array
    {
        return [
            Tabs::make()
                ->tabs([
                    Tabs\Tab::make("Mikrotik RSC")
                        ->schema([
                            Select::make('siteId')
                                ->label('Site ID')
                                // ->options(SiteDetail::pluck('site_name', 'site_id'))
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
                                    // Ambil province dari SiteDetail
                                    $site = SiteDetail::find($state);

                                    $timezone = $site ? $this->getTimezoneByProvince($site->province) : 'Asia/Jakarta';
                                    $set('actionDisabled', true);
                                    $set('timezone', $timezone);

                                    // dd($timezone);
                                })
                                ->placeholder("Select a site ID or site name")
                                ->columnSpanFull(),

                            TextInput::make('timezone')
                                ->label('Timezone')
                                ->required()
                                ->placeholder("This input is auto generated")
                                ->disabled()
                                ->columnSpanFull(),
                            // ->default('Asia/Jakarta'),

                            Forms\Components\Actions::make([
                                Action::make('generatersc')
                                    ->label('Generate .rsc')
                                    ->action('generateRsc')
                                    ->disabled($this->actionDisabled)
                            ])->fullWidth()->columnSpanFull(),
                        ]),
                    Tabs\Tab::make("Grandstream")
                        ->schema([
                            Select::make('siteId')
                                ->label('Site ID')
                                // ->options(SiteDetail::pluck('site_name', 'site_id'))
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
                                    // Ambil province dari SiteDetail
                                    $site = SiteDetail::find($state);
                                    $timezone = $site ? $this->getTimezoneByProvince($site->province) : 'Asia/Jakarta';

                                    $set('actionDisabled', true);
                                    $set('timezone', $timezone);

                                    // dd($timezone);
                                })
                                ->placeholder("Select a site ID or site name")
                                ->columnSpanFull(),

                            TextInput::make('timezone')
                                ->label('Timezone')
                                ->required()
                                ->placeholder("This input is auto generated")
                                ->disabled()
                                ->columnSpanFull(),
                            // ->default('Asia/Jakarta'), // Fallback kalau ga ke-set

                            Forms\Components\Actions::make([
                                Action::make('generategscfg')
                                    ->label('Generate Config')
                                    ->action('generateGsConfig')
                                    ->disabled($this->actionDisabled)
                            ])->fullWidth()->columnSpanFull(),
                        ]),

                ])
        ];
    }

    public function generateRsc()
    {
        // Ambil data dari SiteDetail berdasarkan site_id
        $site = SiteDetail::where('site_id', $this->siteId)->first();

        if (!$site) {
            throw new \Exception('Site ID tidak ditemukan.');
        }

        // Ambil data dari relasi DeviceNetwork
        $deviceNetwork = $site->deviceNetworks;

        // Validasi modem_ip ada dan valid
        if (!$deviceNetwork->modem_ip || !filter_var($deviceNetwork->modem_ip, FILTER_VALIDATE_IP)) {
            throw new \Exception('Modem IP tidak valid atau kosong.');
        }

        // Hitung ip_network dari modem_ip (digit terakhir -1)
        $ipParts = explode('.', $deviceNetwork->modem_ip);
        if (count($ipParts) !== 4) {
            throw new \Exception('Format Modem IP tidak valid.');
        }

        $ipParts[3] = (int)$ipParts[3] - 1; // Kurangi digit terakhir
        if ($ipParts[3] < 0) {
            throw new \Exception('Digit terakhir Modem IP tidak bisa dikurangi (sudah 0).');
        }
        $ipNetwork = implode('.', $ipParts);

        // Baca template mikrotik
        $template = Storage::disk('public')->get('templates/template_mikrotik.txt');

        // Mapping placeholder ke data
        $replacements = [
            '${IP_NETWORK}' => $ipNetwork,
            '${IP_MODEM}' => $deviceNetwork->modem_ip,
            '${IP_MIKROTIK}' => $deviceNetwork->router_ip,
            '${IP_AP1}' => $deviceNetwork->ap1_ip,
            '${IP_AP2}' => $deviceNetwork->ap2_ip,
            '${NAMA_LOKASI}' => $site->site_name,
            '${TIMEZONE}' => $this->timezone,
        ];

        // Ganti placeholder dengan data
        $rscContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        // Nama file berdasarkan site_name
        $fileName = str_replace(' ', ' ', trim($site->site_name));
        $cleanFileName = Str::replace(['-', '.', '(', ')', "'"], '', $fileName) . '.rsc';

        // Simpan file sementara di storage
        Storage::put('temp/' . $cleanFileName, $rscContent);

        // Kirim file untuk di-download
        return response()->download(
            storage_path('app/temp/' . $cleanFileName),
            $cleanFileName,
            ['Content-Type' => 'text/plain']
        )->deleteFileAfterSend(true);
    }

    public function generateGsConfig()
    {
        $site = SiteDetail::where('site_id', $this->siteId)->first();

        if (!$site) {
            throw new \Exception('Site ID tidak ditemukan.');
        }

        $deviceNetwork = $site->deviceNetworks;

        if (!$deviceNetwork->modem_ip || !filter_var($deviceNetwork->modem_ip, FILTER_VALIDATE_IP)) {
            throw new \Exception('Modem IP tidak valid atau kosong.');
        }

        // Hitung ip_network dari modem_ip (digit terakhir -1)
        $ipParts = explode('.', $deviceNetwork->ap2_ip);
        if (count($ipParts) !== 4) {
            throw new \Exception('Format Modem IP tidak valid.');
        }

        $ipParts[3] = (int)$ipParts[3] + 1; // Kurangi digit terakhir
        if ($ipParts[3] < 0) {
            throw new \Exception('Digit terakhir Modem IP tidak bisa dikurangi (sudah 0).');
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

        $txtFileName = str_replace(' ', ' ', trim($site->site_name));
        $cleantxtFileName = Str::replace(['-', '.', '(', ')', "'"], '', $txtFileName) . '_gscfg';

        $binFileName = str_replace(' ', ' ', trim($site->site_name));
        $cleanbinFileName = Str::replace(['-', '.', '(', ')', "'"], '', $binFileName) . '_gscfg.bin';

        Storage::put('temp/' . $cleantxtFileName, $configContent);

        $txtFilePath = storage_path('app/temp/' . $cleantxtFileName);
        $binFilePath = base_path('bin/' . $cleanbinFileName);
        $binFolderPath = base_path('bin');

        $command = sprintf('cd "%s" && ./gscfgtool -t GWN7003 -e "%s"', $binFolderPath, $txtFilePath);
        $output = shell_exec($command . ' 2>&1');

        if (!file_exists($binFilePath)) {
            throw new \Exception('Gagal mengenkripsi file ke .bin: ' . $binFilePath . $output);
        }

        Storage::delete('temp/' . $cleantxtFileName);

        return response()->download(
            $binFilePath,
            $cleanbinFileName,
            ['Content-Type' => 'application/octet-stream']
        )->deleteFileAfterSend(true);
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

        // Normalisasi province (case-insensitive, trim spasi)
        $province = strtolower(trim($province));

        // Return timezone kalau ada, kalau enggak fallback ke Asia/Jakarta
        return $mapping[$province] ?? 'Asia/Jakarta';
    }
}
