<?php

namespace App\Filament\Pages;

use App\Models\SiteDetail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Split;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Arr;
use Webbingbrasil\FilamentCopyActions\Pages\Actions\CopyAction;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;



class GenerateFollowUpTickets extends Page
{
    use HasPageShield;

    protected static ?string $navigationGroup = 'Operational';
    protected static ?string $navigationLabel = 'Follow Up Tickets';
    protected static ?string $navigationIcon = 'phosphor-hand-deposit-duotone';

    protected static ?string $title = 'Follow Up Tickets';
    protected ?string $subheading = 'Auto Generate Follow Up Tickets to NSO Team';

    protected static string $view = 'filament.pages.generate-follow-up-tickets';

    public $siteId;
    public $problem_category;
    public $problem_type;
    public $generated_text;
    public $cboss_remark;

    public $showField = false;


    protected function getActions(): array
    {
        return [
            CopyAction::make()
                ->label("Copy Generated Message")
                ->disabled(fn() => empty($this->generated_text))
                ->copyable(fn() => $this->generated_text),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
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
                ->placeholder("Select a site ID or site name")
                ->columnSpanFull(),
            // ->reactive()
            // ->afterStateUpdated(fn($state, callable $set) => $set('generated_text', null)),

            Select::make('problem_type')
                ->label('Problem Type')
                ->native(false)
                ->placeholder("Select a problem type")
                ->options([
                    'Modem' => [
                        'Modem Fail' => 'Modem Fail',
                        'Modem Gagal Transmit' => 'Modem Gagal Transmit',
                    ],
                    'Router' => [
                        'Router Fail' => 'Router Fail',
                        'Router unConfig' => 'Router unConfig',
                    ],
                    'Access Point' => [
                        'AP 1 Fail' => 'AP 1 Fail',
                        'AP 2 Fail' => 'AP 2 Fail',
                        'POE Fail' => 'POE Fail',
                    ],
                ]),
            // ->reactive()
            // ->afterStateUpdated(fn($state, callable $set) => $set('generated_text', null)),

            Split::make([
                Textarea::make('generated_text')
                    ->label('Generated Message')
                    ->hidden(fn() => ! $this->showField)
                    ->autosize(),

                Textarea::make('cboss_remark')
                    ->label('Cboss Remark')
                    ->hidden(fn() => ! $this->showField)
                    ->autosize(),
            ]),

            Actions::make([
                Actions\Action::make('generate')
                    ->label('Generate')
                    ->action('generateMessage'),
            ])
                ->fullWidth()
                ->alignment(Alignment::Center),
        ];
    }

    public function generateMessage()
    {
        $this->validate([
            'siteId' => 'required',
            'problem_type' => 'required',
        ]);

        $site = SiteDetail::with('area')->findOrFail($this->siteId);
        $area = Str::of($site->area)->title()->trim(); // Ambil AreaList via relasi
        $head_po = $area->head_po ?? "Unknown";

        // Mapping PO ke kabupaten untuk NTT/NTB
        $po_kabupaten_mapping = [
            'Anjar' => ['sumba', 'lombok'],
            'Firman' => ['kupang', 'malaka', 'timur tengah', 'timor tengah', 'belu', 'rote', 'sabu', 'raijua', 'alor',],
            'Novan' => ['manggarai', 'nagekeo', 'ngada', 'ende', 'sikka', 'flores', 'lembata',],
        ];

        // Logika PO
        $po = 'Unknown'; // Default fallback

        if ($area && !empty($area->po)) {
            if (in_array($site->province, ['Nusa Tenggara Timur', 'Nusa Tenggara Barat'])) {
                // Cek administrative_area untuk NTT/NTB
                $admin_area = strtolower($site->administrative_area ?? '');
                foreach ($po_kabupaten_mapping as $po_name => $kabupatens) {
                    if (in_array($po_name, $area->po) && collect($kabupatens)->some(fn($kab) => str_contains($admin_area, $kab))) {
                        $po = $po_name;
                        break;
                    }
                }
                // Fallback ke PO pertama kalau ga cocok
                if ($po === 'Unknown') {
                    $po = Arr::first($area->po);
                }
            } else {
                // Untuk provinsi lain, ambil PO pertama
                $po = Arr::first($area->po);
            }
        }

        // Logika alamat: Lengkapi kalau kurang, hindari duplikasi
        $address = $site->address ?? 'Tidak diketahui';
        $province = $site->province ?? '';
        $admin_area = $site->administrative_area ?? '';
        $is_address_complete = str_contains(strtolower($address), strtolower($province)) ||
            ($admin_area && str_contains(strtolower($address), strtolower($admin_area)));

        if (!$is_address_complete) {
            if ($admin_area && !str_contains(strtolower($address), strtolower($admin_area))) {
                $address .= ", $admin_area";
            }
            if ($province && !str_contains(strtolower($address), strtolower($province))) {
                $address .= ($address === 'Tidak diketahui' ? '' : ', ') . $province;
            }
        }

        // Mapping problem_type ke grup (Modem, Router, POE)
        $problem_group = match ($this->problem_type) {
            'Modem Fail', 'Modem Gagal Transmit' => 'Modem',
            'Router Fail', 'Router unConfig' => 'Router',
            'POE Fail' => 'Access Point 1 & 2',
            'AP 1 Fail' => 'Access Point 1',
            'AP 2 Fail' => 'Access Point 2',
            default => 'Unknown',
        };

        // Template dinamis berdasarkan problem_type
        $dynamic_template = match ($this->problem_type) {
            'Modem Fail' => '- Modem dilokasi Down (Mati total)
- Sudah dibantu PIC Plug UnPlug power dan pindah power source (NOK)
- Indikasi Modem Fail',

            'Modem Gagal Transmit' => '- Indikator Transmit, Receive dan System di Modem Down
- Reboot & Shutdown Modem Sementara oleh PIC (NOK)
- Optim Modem dari HUB (NOK)
- Indikasi Miss Pointing/Transceiver Fail',

            'Router Fail' => '- Indikator LAN di Modem tidak menyala (Statecode 13.1.1)
- Router termonitor Down (Mati total)
- Sudah dibantu PIC pindahkan Power Source Router (NOK)
- Indikasi Mikrotik dilokasi Fail',

            'Router unConfig' => '- Ping Modem Normal, Router Timeout
- Tidak ada Statecode 13.1.1 di Modem (Indikator LAN ke eth1 menyala Normal)
- Tukar kabel LAN eth1 >< eth2 ke arah modem (Router masih RTO)
- Reboot Router & memastikan kabel LAN terpasang dengan baik (NOK)
- Indikasi kesalahan Konfigurasi pada Router/ter-Reset',

            'POE Fail' => '- Modem, Router Termonitor Normal
- LAN to Access Point Down (Interface Mikrotik Slave), indikator POE tidak menyala
- Plug UnPlug & Tukar Kabel Power POE (NOK)
- Indikasi POE Fail',

            'AP 1 Fail' => '- Modem & Router Termonitor Normal
- Indikator Access Point 1 Down
- LAN Access Point 1 UP (Interface Mikrotik RS), indikator POE Normal
- Plug UnPlug POE & Pastikan kabel LAN dari POE >< AP terpasang dengan baik (NOK)
- Indikasi AP Fail/Kabel LAN dari POE ke arah AP Rusak',

            'AP 2 Fail' => '- Modem & Router Termonitor Normal
- Indikator Access Point 2 Down
- LAN Access Point 2 UP (Interface Mikrotik RS), indikator POE Normal
- Plug UnPlug POE & Pastikan kabel LAN dari POE >< AP terpasang dengan baik (NOK)
- Indikasi AP Fail/Kabel LAN dari POE ke arah AP Rusak',

            default => 'Masalah tidak terdeteksi dengan jelas, perlu pengecekan lebih lanjut.',
        };

        // Generate template
        $this->generated_text = sprintf(
            "Dear All,\n\nSaat ini lokasi %s - %s - %s termonitor %s down, Hasil pengecekan:\n%s\n\nMohon segera dibantu terkait progress perbaikannya Pak @%s,\n- Nama PIC	: %s / %s\n- Alamat		: %s\n- Lat/Long	: %s / %s\n\nTerimakasih, CC : Pak @%s",
            $site->site_id,
            $site->site_name,
            $site->province,
            $problem_group,
            $dynamic_template,
            $po,
            $site->pic_name,
            $site->pic_number,
            $address,
            $site->latitude,
            $site->longitude,
            $head_po,
        );

        $this->cboss_remark = sprintf(
            "Dear rekan NSO,\n\nMohon segera dibantu terkait progress perbaikannya,
- Kendala\t: %s
- PIC\t\t\t: %s / %s
- Address\t: %s
- Koordinat\t: Lat: %s | Long: %s\n
Terimakasih.\nCC : Pak %s (PO Area) & Pak %s (Head PO)",
            $this->problem_type,
            $site->pic_name,
            $site->pic_number,
            $address,
            $site->latitude,
            $site->longitude,
            $po,
            $head_po,
        );

        $this->showField = true;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form($this->makeForm()->schema($this->getFormSchema())),
        ];
    }
}
