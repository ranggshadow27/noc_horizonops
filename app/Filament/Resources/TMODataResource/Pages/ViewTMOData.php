<?php

namespace App\Filament\Resources\TMODataResource\Pages;

use App\Filament\Resources\TMODataResource;
use App\Models\TmoData;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Forms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Notifications\Actions\Action as NotificationAction;
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
                ->label("Generate Report")
                ->color("gray")
                ->successNotificationTitle('TMO Report Successfully Generated!')
                ->copyable(function () {

                    $deviceChangeString = "-";
                    $transceiverSN = $this->record->tmoDetail->transceiver_sn ?? '';
                    $transceiverType = $this->record->tmoDetail->transceiver_type ?? '';
                    $feed = $this->record->tmoDetail->feedhorn_sn ?? '';
                    $dish = $this->record->tmoDetail->antenna_sn ?? '';
                    $modemSN = $this->record->tmoDetail->modem_sn ?? '';
                    $modemType = $this->record->tmoDetail->modem_type ?? '';
                    $routerSN = $this->record->tmoDetail->router_sn ?? '';
                    $routerType = $this->record->tmoDetail->router_type ?? '';
                    $rack = $this->record->tmoDetail->rack_sn ?? '';
                    $stabilizer = $this->record->tmoDetail->stabillizer_sn ?? '';
                    $ap1 = $this->record->tmoDetail->ap1_sn ?? '';
                    $ap1_type = $this->record->tmoDetail->ap1_type ?? '';
                    $ap2 = $this->record->tmoDetail->ap2_sn ?? '';
                    $ap2_type = $this->record->tmoDetail->ap2_type ?? '';

                    $startDate = $this->record->tmo_start_date ? Carbon::parse($this->record->tmo_start_date)->format('d M Y H:m') : '';
                    $endDate = $this->record->tmo_end_date ? Carbon::parse($this->record->tmo_end_date)->format('d M Y H:m') : '';

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
No. SPMK		: {$this->record->spmk_number}
TMO Status		: {$this->record->approval}
Tanggal TMO		: {$startDate}
				: {$endDate}

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
SN Transceiver	: {$transceiverType} {$transceiverSN}
SN Feedhorn		: {$feed}
SN Dish Antena	: {$dish}
SN Stabillizer		: {$stabilizer}
SN Modem		: {$modemType} {$modemSN}
SN Router		: {$routerType} {$routerSN}
SN AP 1			: {$ap1_type} {$ap1}
SN AP 2			: {$ap2_type} {$ap2}
SN Rack Indoor	: {$rack}

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
";

                    return $data;
                }),

            Actions\EditAction::make()
                ->label("Edit TMO")
                ->icon('phosphor-plus-circle-duotone')
                // ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->contains(1)),
                ->visible(fn(TmoData $record) => $record->approval === 'Pending'),
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
                                ->default("-")
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
                                ->default("-")
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('esno')
                                ->label('Modem EsNo')
                                ->default("-")
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('signal')
                                ->label('GSM Signal')->color('gray')
                                ->default("-"),

                            Infolists\Components\TextEntry::make('weather')
                                ->label('Weather Condition')->color('gray')
                                ->default("-"),
                        ]),

                        Infolists\Components\Grid::make('4')->schema([
                            Infolists\Components\TextEntry::make('fan_rack1')
                                ->label('Fan Rack 1 Condition')->color('gray')
                                ->default("-"),

                            Infolists\Components\TextEntry::make('fan_rack2')
                                ->label('Fan Rack 2 Condition')->color('gray')
                                ->default("-"),

                            Infolists\Components\TextEntry::make('grounding')
                                ->label('Grounding Condition')->color('gray')
                                ->default("-"),

                            Infolists\Components\TextEntry::make('ifl_length')
                                ->label('IFL Cable Length')
                                ->suffix('Meter')
                                ->default("- ")
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('power_source')
                                ->label('Power Source')
                                ->default("-")
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('power_source_backup')
                                ->label('Backup Power Source')
                                ->default("-")
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
                            ->default("-")
                            ->label('Transceiver Type'),

                        Infolists\Components\TextEntry::make('transceiver_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Transceiver Serial Number'),

                        Infolists\Components\TextEntry::make('feedhorn_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Feedhorn Serial Number'),

                        Infolists\Components\TextEntry::make('antenna_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Antenna'),

                        Infolists\Components\TextEntry::make('stabillizer_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Stabillizer Serial Number'),

                        Infolists\Components\TextEntry::make('modem_type')
                            ->color('gray')
                            ->default("-")
                            ->label('Modem Type'),

                        Infolists\Components\TextEntry::make('modem_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Modem Serial Number'),

                        Infolists\Components\TextEntry::make('router_type')
                            ->color('gray')
                            ->default("-")
                            ->label('Router Type'),

                        Infolists\Components\TextEntry::make('router_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Router Serial Number'),

                        Infolists\Components\TextEntry::make('ap1_type')
                            ->color('gray')
                            ->default("-")
                            ->label('Access Point 1 Type'),

                        Infolists\Components\TextEntry::make('ap1_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Access Point 1 Serial Number'),

                        Infolists\Components\TextEntry::make('ap2_type')
                            ->color('gray')
                            ->default("-")
                            ->label('Access Point 2 Type'),

                        Infolists\Components\TextEntry::make('ap2_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Access Point 2 Serial Number'),

                        Infolists\Components\TextEntry::make('rack_sn')
                            ->color('gray')
                            ->default("-")
                            ->label('Rack Serial Number'),
                    ])
                    ->columns(4)->collapsible()->persistCollapsed(),

                Infolists\Components\Section::make('Images')
                    ->relationship('tmoImages')
                    ->schema([
                        Infolists\Components\ImageEntry::make('transceiver_img')
                            ->label('Transceiver')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('feedhorn_img')
                            ->label('Feedhorn')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('antenna_img')
                            ->label('Dish Antenna')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('stabillizer_img')
                            ->label('Stabillizer')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('rack_img')
                            ->label('Rack Indoor')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('modem_img')
                            ->label('Modem')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('router_img')
                            ->label('Router')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('ap1_img')
                            ->label('Access Point 1')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('ap2_img')
                            ->label('Access Point 2')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('modem_summary_img')
                            ->label('Modem Summary')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('pingtest_img')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound')
                            ->label('Ping Test'),

                        Infolists\Components\ImageEntry::make('speedtest_img')
                            ->label('Speedtest')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('cm_ba_img')
                            ->label('BA Corrective Maintenance')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('pm_ba_img')
                            ->label('BA Preventive Maintenance')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('signplace_img')
                            ->label('Sign')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('stabillizer_voltage_img')
                            ->label('Stabillizer Voltage')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),

                        Infolists\Components\ImageEntry::make('power_source_voltage_img')
                            ->label('Power Source Voltage')
                            ->defaultImageUrl('https://placehold.co/100/transparent/gray?font=roboto&text=No Image\nFound'),
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
                            ->label('Creator')
                            ->default("-")
                            ->color("gray"),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created Date')
                            ->formatStateUsing(function (TmoData $record) {
                                $state = Carbon::parse($record->created_at)->translatedFormat('d M Y H:i');

                                // if ($record->approval === "Pending") {
                                //     $state = "Waiting for Approval";
                                // }

                                return $state;
                            })
                            ->color("gray")
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('approval')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Pending' => 'warning',
                                'Rejected' => 'danger',
                                'Approved' => 'success',
                            })
                            ->label('Status'),

                        Infolists\Components\TextEntry::make('cboss_tmo_code')
                            ->label('CBOSS TMO Code')
                            ->default("Waiting For Approval")
                            ->color("gray"),


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
                            ->label('Approver')
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

                                $engineer = User::where('name', $record->engineer_name)->first();
                                $creator = User::where('id', $record->created_by)->first();

                                $users = [$engineer, $creator];

                                $currentUser = auth()->user()->name;

                                foreach ($users as $user) {
                                    if ($user) {
                                        Notification::make()
                                            ->title($record->tmo_id . ' Rejected')
                                            ->danger()
                                            ->body(
                                                "{$record->site_id} - {$record->site_name}<br><br>
                                    <strong>Reason :</strong><br>
                                    {$data['approval_details']}<br>
                                    Rejected by: <strong>{$currentUser}</strong>"
                                            )
                                            ->actions([
                                                NotificationAction::make('view')

                                                    ->markAsRead()
                                                    ->label('View TMO')
                                                    ->icon('phosphor-hand-withdraw-duotone')
                                                    ->url(route('filament.mahaga.resources.t-m-o-datas.view', $record->tmo_id), true)
                                                    ->openUrlInNewTab(false) // Redirect ke halaman edit
                                            ])
                                            ->sendToDatabase($user);
                                    }
                                }

                                Notification::make()
                                    ->title('TMO Rejected')
                                    ->success()
                                    ->body("The TMO data has been successfully rejected")
                                    ->send();
                            })
                            ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                            ->modalHeading("TMO Rejection")
                            ->modalDescription("Are you sure want to reject this TMO?")
                            ->modalSubmitActionLabel("Reject")
                            ->color('danger')
                            ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)),

                        Infolists\Components\Actions\Action::make('Approve')
                            ->form([
                                Forms\Components\TextInput::make('cboss_tmo_code')
                                    ->label('CBOSS Code')
                                    ->autofocus()
                                    ->autocomplete(false)
                                    ->required(),

                                Forms\Components\Textarea::make('approval_details')
                                    ->label('Approval Note'),
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

                                $engineer = User::where('name', $record->engineer_name)->first();
                                $creator = User::where('id', $record->created_by)->first();

                                $users = [$engineer, $creator];

                                $currentUser = auth()->user()->name;

                                foreach ($users as $user) {
                                    if ($user) {
                                        Notification::make()
                                            ->title($record->tmo_id . ' Approved')
                                            ->success()
                                            ->body(
                                                "{$record->site_id} - {$record->site_name}<br><br>
                                    Kode Lapor : <strong>{$data['cboss_tmo_code']}</strong><br>
                                    Approved by: <strong>{$currentUser}</strong>"
                                            )
                                            ->actions([
                                                Action::make('view')
                                                    ->markAsRead()
                                                    ->label('View TMO')
                                                    ->icon('phosphor-hand-withdraw-duotone')
                                                    ->url(route('filament.mahaga.resources.t-m-o-datas.view', $record->tmo_id), true)
                                                    ->openUrlInNewTab(false) // Redirect ke halaman edit
                                            ])
                                            ->sendToDatabase($user);
                                    }
                                }

                                Notification::make()
                                    ->title('TMO Approved')
                                    ->success()
                                    ->body("The TMO data has been successfully approved")
                                    ->send();
                            })
                            ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                            ->modalHeading("TMO Approval")
                            ->modalDescription("Are you sure want to Approve this TMO?")
                            ->modalSubmitActionLabel("Approve TMO")
                            ->color('primary')
                            ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)),

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

                                $user = User::where('name', $record->engineer_name)->first();
                                $currentUser = auth()->user()->name;

                                Notification::make()
                                    ->title($record->tmo_id . ' Updated')
                                    ->info()
                                    ->body(
                                        "{$record->site_id} - {$record->site_name}<br><br>
                                    <strong>Update with Note :</strong><br>
                                    {$data['approval_details']}<br>
                                    Update by: <strong>{$currentUser}</strong>"
                                    )
                                    ->actions([
                                        NotificationAction::make('progress')
                                            ->markAsRead()
                                            ->label('Update TMO')
                                            ->icon('phosphor-hand-withdraw-duotone')
                                            ->url(route('filament.mahaga.resources.t-m-o-datas.edit', $record->tmo_id), true)
                                            ->openUrlInNewTab(false) // Redirect ke halaman edit
                                    ])
                                    ->sendToDatabase($user);

                                Notification::make()
                                    ->title('TMO Updated')
                                    ->success()
                                    ->body("The TMO data has been successfully updated")
                                    ->send();
                            })
                            ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                            ->modalHeading("Add TMO Note")
                            ->modalDescription("Please fill the column below first to sent your note")
                            ->modalSubmitActionLabel("Sent Note")
                            ->color('gray')
                            ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)),
                    ])->columns(4),


            ]);
    }
}
