<?php

namespace App\Filament\Resources\TMODataResource\Pages;

use App\Filament\Resources\TMODataResource;
use App\Models\TmoData;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Forms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Webbingbrasil\FilamentCopyActions\Pages\Actions\CopyAction;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class ViewTMOData extends ViewRecord
{
    protected static string $resource = TMODataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CopyAction::make()
                ->copyable(function () {
                    $deviceChangeString = "-";
                    if ($this->record->tmo_id) {
                        // Ambil semua device yang terkait dengan tmo_id
                        $devices = \App\Models\TmoDeviceChange::where('tmo_id', $this->record->tmo_id)
                            ->get(['device_name', 'device_sn']); // Ambil dua kolom

                        // Format hasil ke dalam string
                        if ($devices->isNotEmpty()) {
                            $deviceChangeString = "> Pergantian Perangkat :\n" . $devices
                                ->map(fn($device) => "Perangkat Baru	: {$device->device_sn} / {$device->device_name}")
                                ->implode("\n");
                        } else {
                            $deviceChangeString = "> Pergantian Perangkat :\n-";
                        }

                        $problems = $this->record->problem_json ? "\n- " . implode("\n- ", $this->record->problem_json) : "-";
                        $actions = $this->record->action_json ? "\n- " . implode("\n- ", $this->record->action_json) : "-";
                    } else {
                        $deviceChangeString = "> Pergantian Perangkat :\n-";
                    }

                    $data = "Req TMO RTGS MHG
Jenis TMO		: {$this->record->tmo_type}
No. SPMK		: -
Tanggal TMO		: {$this->record->tmo_start_date}
				: {$this->record->tmo_end_date}

> Informasi Lokasi
Terminal ID		: {$this->record->site_id}
Site Name		: {$this->record->site_name}
Provinsi			: {$this->record->site->province}
Alamat			: {$this->record->site->address}
Koordinat		: Lat. {$this->record->site->latitude} Long. {$this->record->site->longitude}

> Informasi Kontak
Nama Teknisi		: {$this->record->engineer_name}
Telp.Teknisi		: {$this->record->engineer_number}
Nama PIC		: {$this->record->pic_name}
Telp. PIC			: {$this->record->pic_number}

> Informasi Perangkat (Existing)
SN Transceiver	: {$this->record->tmoDetail->transceiver_sn} / {$this->record->tmoDetail->transceiver_type}
SN Feedhorn		: {$this->record->tmoDetail->feedhorn_sn}
SN Dish Antena	: {$this->record->tmoDetail->antenna_sn}
SN Stabillizer		: {$this->record->tmoDetail->stabillizer_sn}
SN Modem		: {$this->record->tmoDetail->modem_sn} / {$this->record->tmoDetail->modem_type}
SN Router		: {$this->record->tmoDetail->router_sn} / {$this->record->tmoDetail->router_type}
SN AP 1			: {$this->record->tmoDetail->ap1_sn} / {$this->record->tmoDetail->ap1_type}
SN AP 2			: {$this->record->tmoDetail->ap2_sn} / {$this->record->tmoDetail->ap2_type}
SN Rack Indoor	: {$this->record->tmoDetail->rack_sn}

{$deviceChangeString}

> Informasi Pemeliharaan
Fan Rack 1		: {$this->record->fan_rack1}
Fan Rack 2		: {$this->record->fan_rack2}
Grounding		: {$this->record->grounding}
Kabel IFL			: {$this->record->ifl_length}

Modem SQF		: {$this->record->sqf}
Modem EsNo		: {$this->record->esno}

Cuaca			: {$this->record->weather}
Sinyal GSM		: {$this->record->signal}
Power Source		: {$this->record->power_source}
Backup Power	: {$this->record->power_source_backup}

Problem			: $problems
Action			: $actions
Note			: {$this->record->engineer_note}

TMO Status		: {$this->record->approval}
            ";

                    return $data;
                }),
            Actions\EditAction::make()
                ->label("Edit TMO")
                ->icon('phosphor-plus-circle-duotone')
                ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->contains(1)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Site Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('site_id')
                            ->label('Site ID')
                            ->color('primary')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('site_name')
                            ->label('Site Name')->color('primary')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('site_province')
                            ->label('Site Province')->color('primary'),

                        Infolists\Components\TextEntry::make('site_latitude')
                            ->label('Coordinate')->color('primary')
                            ->formatStateUsing(
                                fn() => "Lat : {$this->record->site_latitude}, Long : {$this->record->site_longitude}"
                            ),

                        Infolists\Components\TextEntry::make('site_address')
                            ->label('Site Address')->color('primary')->columnSpanFull(),

                    ])->collapsible()->persistCollapsed()->columns(4),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('engineer_name')
                            ->label('Engineer Name')
                            ->color('primary'),

                        PhoneEntry::make('engineer_number')
                            ->label('Engineer Number')
                            ->displayFormat(PhoneInputNumberType::NATIONAL)
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('pic_name')
                            ->label('PIC Name')
                            ->color('primary'),

                        PhoneEntry::make('pic_number')
                            ->label('PIC Number')
                            ->color('primary'),

                    ])->collapsible()->persistCollapsed()->columns(4),

                Infolists\Components\Section::make('Maintenance Information')
                    ->schema([

                        Infolists\Components\Grid::make('4')->schema([
                            Infolists\Components\TextEntry::make('spmk_number')
                                ->label('No. SPMK')
                                ->default("-")
                                ->color('primary'),

                            Infolists\Components\TextEntry::make('tmo_type')
                                ->label('Maintenance Type')
                                ->color('primary'),

                            Infolists\Components\TextEntry::make('tmo_start_date')
                                ->label('Start Date')
                                ->color('primary')->dateTime(),
                            Infolists\Components\TextEntry::make('tmo_end_date')
                                ->label('End Date')
                                ->color('primary')->dateTime(),
                        ]),


                        Infolists\Components\Grid::make('4')->schema([
                            Infolists\Components\TextEntry::make('sqf')
                                ->label('Modem SQF')
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('esno')
                                ->label('Modem EsNo')
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('signal')
                                ->label('GSM Signal')->color('gray'),

                            Infolists\Components\TextEntry::make('weather')
                                ->label('Weather Condition')->color('gray'),
                        ]),

                        Infolists\Components\Grid::make('4')->schema([
                            Infolists\Components\TextEntry::make('fan_rack1')
                                ->label('Fan Rack 1 Condition')->color('gray'),

                            Infolists\Components\TextEntry::make('fan_rack2')
                                ->label('Fan Rack 2 Condition')->color('gray'),


                            Infolists\Components\TextEntry::make('grounding')
                                ->label('Grounding Condition')->color('gray'),

                            Infolists\Components\TextEntry::make('ifl_length')
                                ->label('IFL Cable Length')
                                ->suffix('Meter')
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('power_source')
                                ->label('Power Source')
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('power_source_backup')
                                ->label('Backup Power Source')
                                ->color('gray'),
                        ]),

                        Infolists\Components\TextEntry::make('problem_json')
                            ->label('Problem')->default("-"),

                        Infolists\Components\TextEntry::make('action_json')
                            ->label('Action Taken')->default("-"),

                        Infolists\Components\TextEntry::make('engineer_note')
                            ->label('Note')->markdown()->default("-"),

                    ])->collapsible()->persistCollapsed(),

                Infolists\Components\Section::make('Device Information')
                    ->relationship('tmoDetail')
                    ->schema([
                        Infolists\Components\TextEntry::make('transceiver_type')
                            ->color('gray')
                            ->label('Transceiver Type'),

                        Infolists\Components\TextEntry::make('transceiver_sn')
                            ->color('gray')
                            ->label('Transceiver Serial Number'),

                        Infolists\Components\TextEntry::make('feedhorn_sn')
                            ->color('gray')
                            ->label('Feedhorn Serial Number'),

                        Infolists\Components\TextEntry::make('antenna_sn')
                            ->color('gray')
                            ->label('Antenna'),

                        Infolists\Components\TextEntry::make('stabillizer_sn')
                            ->color('gray')
                            ->label('Stabillizer Serial Number'),

                        Infolists\Components\TextEntry::make('modem_type')
                            ->color('gray')
                            ->label('Modem Type'),

                        Infolists\Components\TextEntry::make('modem_sn')
                            ->color('gray')
                            ->label('Modem Serial Number'),

                        Infolists\Components\TextEntry::make('router_type')
                            ->color('gray')
                            ->label('Router Type'),

                        Infolists\Components\TextEntry::make('router_sn')
                            ->color('gray')
                            ->label('Router Serial Number'),

                        Infolists\Components\TextEntry::make('ap1_type')
                            ->color('gray')
                            ->label('Access Point 1 Type'),

                        Infolists\Components\TextEntry::make('ap1_sn')
                            ->color('gray')
                            ->label('Access Point 1 Serial Number'),

                        Infolists\Components\TextEntry::make('ap2_type')
                            ->color('gray')
                            ->label('Access Point 2 Type'),

                        Infolists\Components\TextEntry::make('ap2_sn')
                            ->color('gray')
                            ->label('Access Point 2 Serial Number'),

                        Infolists\Components\TextEntry::make('rack_sn')
                            ->color('gray')
                            ->label('Rack Serial Number'),
                    ])
                    ->columns(4)->collapsible()->persistCollapsed(),

                Infolists\Components\Section::make('Images')
                    ->relationship('tmoImages')
                    ->schema([
                        Infolists\Components\ImageEntry::make('transceiver_img')
                            ->label('Transceiver'),

                        Infolists\Components\ImageEntry::make('feedhorn_img')
                            ->label('Feedhorn'),

                        Infolists\Components\ImageEntry::make('antenna_img')
                            ->label('Dish Antenna'),

                        Infolists\Components\ImageEntry::make('stabillizer_img')
                            ->label('Stabillizer'),

                        Infolists\Components\ImageEntry::make('rack_img')
                            ->label('Rack Indoor'),

                        Infolists\Components\ImageEntry::make('modem_img')
                            ->label('Modem'),

                        Infolists\Components\ImageEntry::make('router_img')
                            ->label('Router'),

                        Infolists\Components\ImageEntry::make('ap1_img')
                            ->label('Access Point 1'),

                        Infolists\Components\ImageEntry::make('ap2_img')
                            ->label('Access Point 2'),

                        Infolists\Components\ImageEntry::make('modem_summary_img')
                            ->label('Modem Summary'),

                        Infolists\Components\ImageEntry::make('pingtest_img')
                            ->label('Ping Test'),

                        Infolists\Components\ImageEntry::make('speedtest_img')
                            ->label('Speedtest'),

                        Infolists\Components\ImageEntry::make('cm_ba_img')
                            ->label('BA Corrective Maintenance'),

                        Infolists\Components\ImageEntry::make('pm_ba_img')
                            ->label('BA Preventive Maintenance'),

                        Infolists\Components\ImageEntry::make('signplace_img')
                            ->label('Sign'),

                        Infolists\Components\ImageEntry::make('stabillizer_voltage_img')
                            ->label('Stabillizer Voltage'),

                        Infolists\Components\ImageEntry::make('power_source_voltage_img')
                            ->label('Power Source Voltage'),
                    ])
                    ->columns(4)->collapsible()->persistCollapsed(),

                Infolists\Components\Section::make('Old Device')
                    ->schema([
                        Infolists\Components\TextEntry::make('is_device_change')
                            ->label('Device Change')
                            ->formatStateUsing(function ($state) {
                                return $state === 0 ? "No Device Replacement" : "";
                            })
                            ->color('gray'),

                        Infolists\Components\RepeatableEntry::make('deviceChanges')
                            ->schema([
                                Infolists\Components\TextEntry::make('device_name')
                                    ->label('Old Device Name')->color("gray"),

                                Infolists\Components\TextEntry::make('device_sn')
                                    ->label('Serial Number')->color("gray"),

                                Infolists\Components\TextEntry::make('homebase.location')
                                    ->label('To Homebase')->color("gray"),

                                Infolists\Components\ImageEntry::make('device_img')
                                    ->label('Image')

                            ])
                            ->label('')
                            ->grid(3) // Tampilkan dalam 2 kolom

                    ])->columns(1)->collapsible()->persistCollapsed(),

                Infolists\Components\Section::make('TMO Approval')
                    ->schema([
                        Infolists\Components\TextEntry::make('tmo_id')
                            ->label('TMO ID')->color("gray"),

                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By')
                            ->default("-")
                            ->color("gray"),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created Date')
                            ->formatStateUsing(function (TmoData $record) {
                                $state = Carbon::parse($record->created_at)->translatedFormat('d M Y H:i');

                                if ($record->approval === "Pending") {
                                    $state = "Waiting for Approval";
                                }

                                return $state;
                            })
                            ->color("gray")
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('cboss_tmo_code')
                            ->label('CBOSS TMO Code')
                            ->default("Waiting For Approval")
                            ->color("gray"),

                        Infolists\Components\TextEntry::make('approval')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Pending' => 'warning',
                                'Rejected' => 'danger',
                                'Approved' => 'success',
                            })
                            ->label('Status'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Approval Date')
                            ->formatStateUsing(function (TmoData $record) {
                                $state = Carbon::parse($record->updated_at)->translatedFormat('d M Y H:i');

                                if ($record->approval === "Pending") {
                                    $state = "Waiting for Approval";
                                }

                                return $state;
                            })
                            ->color("gray"),

                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Approval By')
                            ->default("-")
                            ->color("gray"),

                        Infolists\Components\TextEntry::make('approval_details')
                            ->label('Note')
                            ->default("-")
                            ->color("gray")
                            ->columnSpanFull(),
                    ])
                    ->footerActions([
                        Infolists\Components\Actions\Action::make('Rejected')
                            ->label('Reject')
                            ->form([
                                Forms\Components\Textarea::make('approval_details')
                                    ->label('Rejection Details')
                                    ->autofocus()
                                    ->required(),
                            ])
                            ->icon('phosphor-x-duotone') // Ganti dengan icon yang diinginkan
                            ->action(function (TmoData $record, array $data) {

                                $record->approval = 'Rejected';
                                $record->approval_details = $data['approval_details'];
                                $record->save();

                                Notification::make()
                                    ->title('TMO Updated')
                                    ->success()
                                    ->body("The TMO data has been successfully rejected")
                                    ->send();
                            })
                            ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                            ->color('danger')
                            ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->contains(1)),

                        Infolists\Components\Actions\Action::make('add_note')
                            ->label('Add Note')
                            ->form([
                                Forms\Components\Textarea::make('approval_details')
                                    ->label('Note')
                                    ->autofocus()
                                    ->required(),
                            ])
                            ->icon('phosphor-note-pencil-duotone') // Ganti dengan icon yang diinginkan
                            ->action(function (TmoData $record, array $data) {

                                $record->approval_details = $data['approval_details'];
                                $record->save();

                                Notification::make()
                                    ->title('TMO Updated')
                                    ->success()
                                    ->body("The TMO data has been successfully updated")
                                    ->send();
                            })
                            ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                            ->color('gray')
                            ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->contains(1)),

                        Infolists\Components\Actions\Action::make('Approve')
                            ->form([
                                Forms\Components\TextInput::make('cboss_tmo_code')
                                    ->label('CBOSS Code')
                                    ->autofocus()
                                    ->autocomplete(false)
                                    ->required(),

                                Forms\Components\Textarea::make('approval_details')
                                    ->label('Approval Details'),
                            ])
                            ->label('Approve')
                            ->icon('phosphor-check-circle-duotone') // Ganti dengan icon yang diinginkan
                            ->action(function (TmoData $record, array $data) {
                                // Update kolom approval menjadi 'Approved'
                                // $record->update(['approval' => 'Approved']);

                                $record->cboss_tmo_code = $data['cboss_tmo_code'];
                                $record->approval_details = $data['approval_details'];
                                $record->approval = "Approved";
                                $record->approval_by = auth()->id();
                                $record->update();

                                Notification::make()
                                    ->title('TMO Approved')
                                    ->success()
                                    ->body("The TMO data has been successfully approved")
                                    ->send();
                            })
                            ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                            ->color('primary')
                            ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->contains(1)),


                    ])->columns(4),
            ]);
    }
}
