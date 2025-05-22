<?php

namespace App\Filament\Pages;

use App\Models\ChatTemplate;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use App\Models\SiteDetail;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Webbingbrasil\FilamentCopyActions\Pages\Actions\CopyAction;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class AutoChat extends Page implements HasForms
{
    use HasPageShield;

    use InteractsWithForms;

    protected static string $view = 'filament.pages.auto-chat';

    protected static ?string $navigationGroup = 'Operational';
    protected static ?string $navigationIcon = 'phosphor-chat-teardrop-dots-duotone';

    protected ?string $subheading = 'Auto Generate PIC Chat & Site Information';


    public $gender = '';
    public $siteId = '';
    public $templateId = '';
    public $generatedChat = '';

    public $additionalInfo = '';
    public $deviceDetail = '';

    public $showDetails = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getActions(): array
    {
        return [
            CopyAction::make()
                ->label("Copy Chat")
                ->disabled(fn() => empty($this->generatedChat))
                ->copyable(fn() => $this->generatedChat),

            CopyAction::make()
                ->label("Copy Site Information")
                ->disabled(fn() => empty($this->additionalInfo))
                ->copyable(fn() => $this->getCopyableContent()),
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

            Select::make('templateId')
                ->label('Chat Template')
                ->options(function () {
                    return ChatTemplate::pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->required()
                ->placeholder("Select a template chat"),

            Select::make('gender')
                ->label('Gender')
                ->default('male')
                ->options([
                    'male' => 'Male',
                    'female' => 'Female',
                ])
                ->required()
                ->placeholder('Pilih Gender'),

            Textarea::make('generatedChat')
                ->label('Generated Chat')
                ->autosize()
                ->hidden(fn() => ! $this->showDetails),

            Actions::make([
                Actions\Action::make('generate')
                    ->label('Generate Chat')
                    ->action('generateChat'),
            ])
                ->fullWidth()
                ->alignment(Alignment::Center),

            Split::make([
                Textarea::make('additionalInfo')
                    ->label('Additional Info')
                    ->readOnly()
                    ->autosize(),

                Textarea::make('deviceDetail')
                    ->label('Device Detail')
                    ->readOnly()
                    ->autosize(),
            ])->hidden(fn() => ! $this->showDetails)
        ];
    }

    public function generateChat(): void
    {
        $site = SiteDetail::findOrFail($this->siteId);
        $template = ChatTemplate::find($this->templateId);

        if (!$site) {
            $this->generatedChat = '';
            Notification::make()
                ->title('Error')
                ->body('Site ID tidak ditemukan!')
                ->danger()
                ->send();
        } elseif (!$template) {
            $this->generatedChat = '';
            Notification::make()
                ->title('Error')
                ->body('Template tidak ditemukan!')
                ->danger()
                ->send();
        } else {
            $timeOfDay = now()->hour < 5 ? 'Malam' : (now()->hour < 11 ? 'Pagi' : (now()->hour < 15 ? 'Siang' : (now()->hour < 18 ? 'Sore' : 'Malam')));
            $genderText = $this->gender === 'male' ? 'Bapak' : 'Ibu';

            $placeholders = [
                '{site_id}' => $site->site_id,
                '{nama_site}' => $site->site_name,
                '{provinsi}' => $site->province,
                '{time}' => $timeOfDay,
                '{gender}' => $genderText,
            ];

            $this->generatedChat = str_replace(
                array_keys($placeholders),
                array_values($placeholders),
                $template->template
            );

            $cbossTmo = $site->cbossTmo->last();

            // Additional Info
            $this->additionalInfo = "• Lokasi\t\t\t:\n{$site->site_id} - {$site->site_name}\n\n"
                . "• Data PIC\t\t:\n"
                . "PIC Lokasi\t\t\t" . ($site->pic_name ?? 'N/A') . " / " . ($site->pic_number ?? 'N/A') . "\n"
                . "PIC Penyedia\t\t" . ($site->installer_name ?? 'N/A') . " / " . ($site->installer_number ?? 'N/A') . "\n"
                . "PIC Last TMO\t\t" . ($cbossTmo->pic_name ?? '-') . " / " . ($cbossTmo->pic_number ?? '-') . "\n\n"
                . "• Provinsi\t:\n{$site->administrative_area}, {$site->province}\n\n"
                . "• Alamat\t\t:\n{$site->address}\n\n"
                . "• Koordinat\t:\nLatitude {$site->latitude} / Longitude {$site->longitude}\n\n"
                . "• Gateway\t\t\t: {$site->gateway}\n"
                . "• Spotbeam\t\t: {$site->spotbeam} / {$site->ip_hub}\n"
                . "• Power Source\t: {$site->power_source}\n"
                . "• Batch\t\t\t\t: {$site->batch}";

            // Device Detail
            $device = $site->devices;

            // dd($device);
            $this->deviceDetail = "• Transceiver\t:\n" . ($device->transceiver_type ?? 'N/A') . "  |  " . ($device->transceiver_sn ?? 'N/A') . "\n\n"
                . "• Antenna\t\t:\n" . ($device->antenna_type ?? 'N/A') . "  |  " . ($device->antenna_sn ?? 'N/A') . "\n\n"
                . "• Modem\t\t:\n" . ($device->modem_type ?? 'N/A') . "  |  " . ($device->modem_sn ?? 'N/A') . "\n\n"
                . "• Router\t\t\t:\n" . ($device->router_type ?? 'N/A') . "  |  " . ($device->router_sn ?? 'N/A') . "\n\n"
                . "• AP 1\t\t\t\t:\n" . ($device->ap1_type ?? 'N/A') . "  |  " . ($device->ap1_sn ?? 'N/A') . "\n\n"
                . "• AP 2\t\t\t\t:\n" . ($device->ap2_type ?? 'N/A') . "  |  " . ($device->ap2_sn ?? 'N/A') . "\n\n"
                . "• Rack\t\t\t\t: " . ($device->rack_sn ?? 'N/A') . "\n\n"
                . "• Stabillizer\t\t: " . ($device->stabilizer_type ?? 'N/A') . "  |  " . ($device->stabilizer_sn ?? 'N/A');

            $this->showDetails = true;
        }

        $this->form->fill([
            'siteId' => $this->siteId,
            'templateId' => $this->templateId,
            'gender' => $this->gender,
            'generatedChat' => $this->generatedChat,
            'additionalInfo' => $this->additionalInfo,
            'deviceDetail' => $this->deviceDetail,
        ]);
    }

    public function getCopyableContent(): string
    {
        // Pisahkan string berdasarkan 'Gaboleh terkopi'
        $parts = explode('• Gateway', $this->additionalInfo);

        // Ambil bagian pertama dari hasil explode
        $copyableContent = trim($parts[0]);

        // Ganti semua kemunculan 'Data' dengan 'Lokasi'
        $copyableContent = str_replace('• ', '> ', $copyableContent);

        return $copyableContent;
    }

    // public function generateChat(): void
    // {
    //     $site = SiteDetail::where('site_id', $this->siteId)->first();

    //     if ($site) {
    //         $this->generatedChat = "Selamat Malam,\n\nkami penyedia wifi dilokasi {$site->site_id} - {$site->site_name} - {$site->province}, apakah saat ini ada kendala dengan internetnya?\n\nTerimakasih";
    //     } else {
    //         $this->generatedChat = 'Site ID tidak ditemukan!';
    //     }

    //     $this->form->fill([
    //         'siteId' => $this->siteId,
    //         'generatedChat' => $this->generatedChat,
    //     ]);
    // }
}
