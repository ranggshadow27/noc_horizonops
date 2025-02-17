<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TMODataResource\Pages;
use App\Filament\Resources\TMODataResource\RelationManagers;
use App\Models\SiteDetail;
use App\Models\TMOData;
use App\Models\TmoDeviceChange;
use App\Models\TmoHomebase;
use App\Models\TmoProblem;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class TMODataResource extends Resource
{
    protected static ?string $model = TmoData::class;

    protected static ?string $navigationLabel = 'TMO Data';
    protected static ?string $navigationGroup = 'TMO';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'TMO Data';
    protected static ?string $modelLabel = 'TMO Data';

    protected static ?string $navigationIcon = 'phosphor-hand-withdraw-duotone';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Information')
                    ->schema([
                        Forms\Components\Select::make('site_id')
                            ->label('Site ID')
                            ->relationship(name: 'site', titleAttribute: 'site_id')
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn(SiteDetail $record) => "{$record->site_id} - {$record->site_name}")
                            ->reactive()
                            ->searchable(['site_id', 'site_name'])
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Auto-fill data when site_id is selected
                                $site = SiteDetail::where('site_id', $state)->first();
                                if ($site) {
                                    $set('site_name', $site->site_name);
                                    $set('site_province', $site->province);
                                    $set('site_address', $site->address);
                                    $set('site_latitude', $site->latitude);
                                    $set('site_longitude', $site->longitude);
                                }
                            })
                            ->required()->columnSpan(2),

                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->hidden(),

                        Forms\Components\TextInput::make('site_province')
                            ->label('Site Province')
                            ->required()
                            ->maxLength(255)
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('site_address')
                            ->label('Site Address')
                            ->required()
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('site_latitude')
                            ->label('Latitude')
                            ->maxLength(25)
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('site_longitude')
                            ->label('Longitude')
                            ->maxLength(25)
                            ->disabled()->dehydrated(true),

                    ])->collapsible()->persistCollapsed()->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('engineer_name')
                            ->label('Technician Name')
                            ->required(),

                        Forms\Components\TextInput::make('pic_name')
                            ->label('PIC Name')
                            ->required(),

                        PhoneInput::make('engineer_number')
                            ->label('Technician Number')
                            ->onlyCountries(['id'])
                            ->required(),

                        PhoneInput::make('pic_number')
                            ->label('PIC Number')
                            ->required()
                            ->onlyCountries(['id']),

                    ])->collapsible()->persistCollapsed()->columns(2),

                Forms\Components\Section::make('Maintenance Information')
                    ->schema([
                        Forms\Components\Select::make('tmo_type')
                            ->options([
                                'Preventive Maintenance' => 'Preventive Maintenance',
                                'Corrective Maintenance' => 'Corrective Maintenance',
                            ])
                            ->label('Maintenance Type')
                            ->searchable()
                            ->required(),

                        Forms\Components\Grid::make('4')->schema([
                            Forms\Components\TextInput::make('sqf')
                                ->label('Modem SQF')
                                ->numeric()
                                ->required(),

                            Forms\Components\TextInput::make('esno')
                                ->label('Modem EsNo')
                                ->numeric()
                                ->required(),

                            Forms\Components\TextInput::make('signal')
                                ->label('GSM Signal')
                                ->required(),

                            Forms\Components\Select::make('weather')
                                ->options([
                                    'Hujan' => 'Hujan',
                                    'Mendung' => 'Mendung',
                                    'Berawan' => 'Berawan',
                                    'Cerah' => 'Cerah',
                                ])
                                ->searchable()
                                ->label('Weather')
                                ->required(),
                        ]),

                        Forms\Components\Grid::make('4')->schema([
                            Forms\Components\Select::make('fan_rack1')
                                ->options([
                                    'Not Attached' => 'Not Attached',
                                    'Problem' => 'Problem',
                                    'Normal' => 'Normal',
                                ])
                                ->searchable()
                                ->label('Fan Rack 1 Condition')
                                ->required(),

                            Forms\Components\Select::make('fan_rack2')
                                ->options([
                                    'Not Attached' => 'Not Attached',
                                    'Problem' => 'Problem',
                                    'Normal' => 'Normal',
                                ])
                                ->searchable()
                                ->label('Fan Rack 2 Condition')
                                ->required(),


                            Forms\Components\Select::make('grounding')
                                ->options([
                                    'Not Attached' => 'Not Attached',
                                    'Problem' => 'Problem',
                                    'Normal' => 'Normal',
                                ])
                                ->searchable()
                                ->label('Grounding Condition')
                                ->required(),

                            Forms\Components\TextInput::make('ifl_length')
                                ->label('IFL Cable Length')
                                ->suffix('Meter')
                                ->numeric()
                                ->required(),
                        ]),

                        Forms\Components\Grid::make('2')->schema([
                            Forms\Components\Select::make('power_source')
                                ->options([
                                    'None' => 'None',
                                    'PLTA' => 'PLTA',
                                    'Genset' => 'Genset',
                                    'Solar Panel' => 'Solar Panel',
                                    'PLN' => 'PLN',
                                    'Others' => 'Others',
                                ])
                                ->searchable()
                                ->label('Power Source')
                                ->required(),

                            Forms\Components\Select::make('power_source_backup')
                                ->options([
                                    'None' => 'None',
                                    'PLTA' => 'PLTA',
                                    'Genset' => 'Genset',
                                    'Solar Panel' => 'Solar Panel',
                                    'PLN' => 'PLN',
                                    'Others' => 'Others',
                                ])
                                ->searchable()
                                ->label('Backup Power Source')
                                ->required(),
                        ]),

                        Forms\Components\Select::make('problem_json')
                            ->options(
                                TmoProblem::query()
                                    ->orderBy('problem_type') // Urutkan berdasarkan kategori
                                    ->get()
                                    ->groupBy('problem_category')
                                    ->mapWithKeys(fn($items, $category) => [
                                        $category => $items->pluck('problem_type', 'problem_type')->toArray()
                                    ])
                                    ->toArray()
                            )
                            ->afterStateUpdated(function (callable $set, $state) {
                                if (empty($state)) {
                                    $set('action_json', []);
                                    return;
                                }

                                // Ambil semua action berdasarkan problem yang dipilih
                                $actions = TmoProblem::whereIn('problem_type', $state)
                                    ->pluck('action')
                                    ->toArray();

                                $set('action_json', array_unique($actions)); // Set multiple actions ke select
                            })
                            ->label("Problem")
                            ->searchable()
                            ->multiple()
                            ->reactive()
                            ->required(),

                        Forms\Components\Select::make('action_json')
                            ->disabled()->dehydrated(true)
                            ->label('Action Taken')
                            ->unique()
                            ->multiple()
                            ->reactive()
                            ->required(),

                        Forms\Components\Textarea::make('engineer_note')
                            ->label('Note')->autosize(),

                        Forms\Components\Grid::make('2')->schema([
                            Forms\Components\DateTimePicker::make('tmo_start_date')
                                ->label('Start Date')
                                ->required(),
                            Forms\Components\DateTimePicker::make('tmo_end_date')
                                ->label('End Date')
                                ->required(),

                        ]),

                    ])->collapsible()->persistCollapsed(),

                Forms\Components\Section::make('Device Information')
                    ->relationship('tmoDetail')
                    ->schema([

                        Forms\Components\Select::make('transceiver_type')
                            ->label('Transceiver Type')
                            ->options([
                                'Hughes HB220' => 'Hughes HB220',
                                'RevGo' => 'RevGo',
                                'SkyWare' => 'SkyWare',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('transceiver_sn')
                            ->label('Transceiver Serial Number')
                            ->required(),

                        Forms\Components\TextInput::make('feedhorn_sn')
                            ->label('Feedhorn Serial Number')
                            ->required(),

                        Forms\Components\TextInput::make('antenna_sn')
                            ->label('Antenna')
                            ->required(),

                        Forms\Components\TextInput::make('stabillizer_sn')
                            ->label('Stabillizer Serial Number')
                            ->required(),

                        Forms\Components\Select::make('modem_type')
                            ->label('Modem Type')
                            ->options([
                                'Hughes HT2010' => 'Hughes HT2010',
                                'Hughes HT2300' => 'Hughes HT2300',

                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('modem_sn')
                            ->label('Modem Serial Number')
                            ->required(),

                        Forms\Components\Select::make('router_type')
                            ->label('Router Type')
                            ->options([
                                'Mikrotik RB450' => 'Mikrotik RB450',
                                'Grandstream GWN' => 'Grandstream GWN7003',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('router_sn')
                            ->label('Router Serial Number')
                            ->required(),


                        Forms\Components\Select::make('ap1_type')
                            ->label('Access Point 1 Type')
                            ->options([
                                'Grandstream GWN7630LR' => 'Grandstream GWN7630LR',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('ap1_sn')
                            ->label('Access Point 1 Serial Number')
                            ->required(),

                        Forms\Components\Select::make('ap2_type')
                            ->label('Access Point 2 Type')
                            ->options([
                                'Grandstream GWN7630LR' => 'Grandstream GWN7630LR',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('ap2_sn')
                            ->label('Access Point 2 Serial Number')
                            ->required(),

                        Forms\Components\TextInput::make('rack_sn')
                            ->label('Rack Serial Number')
                            ->required(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Images Upload')
                    ->relationship('tmoImages')
                    ->schema([
                        Forms\Components\FileUpload::make('transceiver_img')
                            ->label('Transceiver')
                            ->directory('tmo-images/transceiver')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('feedhorn_img')
                            ->label('Feedhorn')
                            ->directory('tmo-images/feedhorn')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('antenna_img')
                            ->label('Dish Antenna')
                            ->directory('tmo-images/antenna')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),
                        // ->panelAspectRatio('1:1')
                        // ->panelLayout('integrated')

                        Forms\Components\FileUpload::make('stabillizer_img')
                            ->label('Stabillizer')
                            ->directory('tmo-images/stabillizer')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('rack_img')
                            ->label('Rack Indoor')
                            ->directory('tmo-images/rack')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('modem_img')
                            ->label('Modem')
                            ->directory('tmo-images/modem')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('router_img')
                            ->label('Router')
                            ->directory('tmo-images/router')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('ap1_img')
                            ->label('Access Point 1')
                            ->directory('tmo-images/ap1')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('ap2_img')
                            ->label('Access Point 2')
                            ->directory('tmo-images/ap2')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('modem_summary_img')
                            ->label('Modem Summary')
                            ->directory('tmo-images/modem_summary')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('pingtest_img')
                            ->label('Ping Test')
                            ->directory('tmo-images/pingtest')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('speedtest_img')
                            ->label('Speedtest')
                            ->directory('tmo-images/speedtest')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('cm_ba_img')
                            ->label('BA Corrective Maintenance')
                            ->directory('tmo-images/cm_ba')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('pm_ba_img')
                            ->label('BA Preventive Maintenance')
                            ->directory('tmo-images/pm_ba')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('signplace_img')
                            ->label('Sign')
                            ->directory('tmo-images/signplace')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('stabillizer_voltage_img')
                            ->label('Stabillizer Voltage')
                            ->directory('tmo-images/stabillizer_voltage')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('power_source_voltage_img')
                            ->label('Power Source Voltage')
                            ->directory('tmo-images/power_source_voltage')
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->nullable(),
                    ])
                    ->columns(4),


                Forms\Components\Section::make('Device Replacement')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_device_change')
                            ->boolean()
                            ->label("Change Device")
                            ->grouped()
                            ->required()
                            ->reactive(),

                        Forms\Components\Repeater::make('deviceChanges')
                            ->relationship() // Otomatis detect relasi 'deviceChanges' dari model
                            ->schema([
                                Forms\Components\Select::make('device_name')
                                    ->options([
                                        'Access Point' => 'Access Point',
                                        'Adaptor Router' => 'Adaptor Router',
                                        'Adaptor Modem' => 'Adaptor Modem',
                                        'Modem Hughes HT2010' => '(Modem) Hughes HT2010',
                                        'Modem Hughes HT2300' => '(Modem) Hughes HT2300',
                                        'Mikrotik RB450' => '(Router) Mikrotik RB450',
                                        'Grandstream GWN7003' => '(Router) Grandstream GWN7003',
                                        'POE' => 'POE',
                                        'Stabillizer' => 'Stabillizer',
                                        'Transceiver Hughes HB220' => '(Transceiver) Hughes HB220',
                                        'Transceiver RevGo' => '(Transceiver) RevGo',
                                    ])
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('homebase_id')
                                    ->options(TmoHomebase::all()->pluck('location', 'homebase_id'))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('device_sn')
                                    ->label('Serial Number')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\FileUpload::make('device_img')
                                    ->label('Gambar Device')
                                    ->imagePreviewHeight('250')
                                    ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                                    ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                                    ->image()->optimize('jpg')
                                    ->openable()->downloadable()
                                    ->directory('device-images') // Folder penyimpanan
                                    ->nullable()
                            ])
                            // ->columns(2) // Tampilkan dalam 2 kolom
                            ->label('')
                            ->maxItems(5)
                            ->reorderable(true)
                            ->collapsible()
                            ->grid(3)
                            ->hidden(fn(callable $get) => !$get('is_device_change'))
                            ->itemLabel(fn(array $state): string => $state['device_name'] ?? 'Device')
                            ->defaultItems(0) // Jumlah item awal (kosong)
                            ->addActionLabel('Add Device') // Jika ingin bisa diurutkan (drag & drop)
                            ->deletable(false)
                    ])->columns(1)
            ]);
    }

    protected function saved($record): void
    {
        // Simpan data TMODetails saat data TMOData disimpan
        $record->tmoDetails()->updateOrCreate(
            ['tmo_id' => $record->tmo_id], // Cari berdasarkan tmo_id
            request()->input('tmo_details', []) // Ambil data nested form TMODetails
        );

        $record->tmoImages()->updateOrCreate(
            ['tmo_id' => $record->tmo_id], // Cari berdasarkan tmo_id
            request()->input('tmo_images', []) // Ambil data nested form TMODetails
        );

        $record->tmoDeviceChanges()->updateOrCreate(
            ['tmo_id' => $record->tmo_id], // Cari berdasarkan tmo_id
            request()->input('tmo_device_changes', []) // Ambil data nested form TMODetails
        );
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tmo_id')->label('TMO ID')
                    ->sortable()->searchable()->copyable(),
                Tables\Columns\TextColumn::make('site_id')->label('Site ID')
                    ->sortable()->searchable()->copyable(),
                Tables\Columns\TextColumn::make('site_name')->label('Site Name')
                    ->sortable()->searchable(),
                Tables\Columns\TextColumn::make('tmo_type')->label('TMO Type')
                    ->sortable()
                    ->badge()->color(fn(string $state): string => match ($state) {
                        'Corrective Maintenance' => 'primary',
                        'Preventive Maintenance' => 'secondary',
                    })
                    ->formatStateUsing(function ($state) {
                        return $state === "Preventive Maintenance" ? "Preventive" : "Corrective";
                    }),
                Tables\Columns\TextColumn::make('engineer_name')->label('Technician Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval')->label('Status')
                    ->sortable()->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Rejected' => 'danger',
                        'Approved' => 'success',
                    })
                    ->tooltip(fn(TMOData $record) => $record->approval === "Pending" ? null : "At " . Carbon::parse($record->updated_at)->translatedFormat('d M Y H:i')),
                Tables\Columns\TextColumn::make('cboss_tmo_code')->label('CBOSS TMO Code')
                    ->sortable()->placeholder('Waiting for Approval'),
                Tables\Columns\TextColumn::make('tmo_start_date')->label('TMO Date')
                    ->date('d M Y H:i')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make('edit'),
                    Tables\Actions\Action::make('approve')
                        ->form([
                            Forms\Components\Textarea::make('approval_details')
                                ->label('Approval Details')
                                ->required(),
                            Forms\Components\TextInput::make('cboss_tmo_code')
                                ->label('CBOSS Code')
                                ->required(),
                        ])
                        ->label('Approve')
                        ->icon('phosphor-check-circle-duotone') // Ganti dengan icon yang diinginkan
                        ->action(function (TmoData $record, array $data) {
                            // Update kolom approval menjadi 'Approved'
                            // $record->update(['approval' => 'Approved']);

                            $record->cboss_tmo_code = $data['cboss_tmo_code'];
                            $record->approval_details = $data['approval_details'];
                            $record->save();

                            Notification::make()
                                ->title('TMO Approved')
                                ->success()
                                ->body("The TMO data has been successfully approved")
                                ->send();
                        })
                        ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                        ->color('primary'),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->form([
                            Forms\Components\Textarea::make('approval_details')
                                ->label('Rejection Details')
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
                ])
                    ->icon('phosphor-dots-three-vertical-duotone')->dropdown()
                    ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->roles->pluck('name')->contains('super_admin'))
                // ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->hasRole('super_admin'))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->heading('TMO RTGS')
            ->description('Manage all Mahaga TMO RTGS Maintenance - Network Operation Center.')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label("Add New TMO")
                    ->icon('phosphor-plus-circle-duotone'),
            ])
            ->recordUrl(
                fn(TMOData $record): string =>
                $record->tmo_start_date || $record->pic_name ?
                    Pages\ViewTMOData::getUrl([$record->tmo_id]) :
                    Pages\EditTMOData::getUrl([$record->tmo_id]),
            );;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTMOData::route('/'),
            'create' => Pages\CreateTMOData::route('/create'),
            'view' => Pages\ViewTMOData::route('/{record}'),
            'edit' => Pages\EditTMOData::route('/{record}/edit'),
        ];
    }
}
