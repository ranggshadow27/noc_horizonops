<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TMODataResource\Pages;
use App\Models\AreaList;
use App\Models\SiteDetail;
use App\Models\TmoData;
use App\Models\TmoHomebase;
use App\Models\TmoProblem;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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

    public static function getNavigationBadge(): ?string
    {

        $data = static::getModel()::where('approval', 'Pending')->count();

        if (auth()->user()->roles->pluck('id')->contains(4)) {
            $data = TmoData::where('created_by', auth()->id())
                ->where('approval', 'Pending')
                ->count();
        }

        if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
            $data = TmoData::where('engineer_name', auth()->user()->name)
                ->where('approval', 'Pending')
                ->count();
        }

        return $data;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'TMO Pending';
    }

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
                                    $set('coordinate', "{$site->latitude} / " . "{$site->longitude}");
                                }
                            })
                            // ->disabled()->dehydrated(true)
                            ->required()->columnSpanFull(),

                        Forms\Components\Hidden::make('site_name')
                            ->label('Site Name'),

                        Forms\Components\Hidden::make('site_latitude')
                            ->label('Longitude'),

                        Forms\Components\Hidden::make('site_longitude')
                            ->label('Latitude'),

                        Forms\Components\TextInput::make('site_province')
                            ->label('Site Province')
                            ->required()
                            ->maxLength(255)
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('coordinate')
                            ->label('Coordinate (Lat/Long)')
                            ->formatStateUsing(fn($record) => "{$record->site_latitude} / " . "{$record->site_longitude}")
                            ->maxLength(50)
                            ->disabled(),

                        Forms\Components\Textarea::make('site_address')
                            ->label('Site Address')
                            ->autosize()
                            ->required()
                            ->disabled()->dehydrated(true)->columnSpanFull(),

                    ])->collapsible()->persistCollapsed()->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('engineer_name')
                            ->label('Technician Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->required(),

                        PhoneInput::make('engineer_number')
                            ->label('Technician Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->onlyCountries(['id'])
                            ->required(),

                        Forms\Components\TextInput::make('pic_name')
                            ->label('PIC Name')
                            ->required(),

                        PhoneInput::make('pic_number')
                            ->label('PIC Number')
                            ->required()
                            ->onlyCountries(['id']),

                    ])->collapsible()->persistCollapsed()->columns(2),

                Forms\Components\Section::make('Maintenance Information')
                    ->schema([
                        Forms\Components\Grid::make('2')->schema([
                            Forms\Components\TextInput::make('spmk_number')
                                ->label('No. SPMK')
                                ->disabled()->dehydrated(true)
                                ->required(),

                            Forms\Components\Select::make('tmo_type')
                                ->options([
                                    'Preventive Maintenance' => 'Preventive Maintenance',
                                    'Corrective Maintenance' => 'Corrective Maintenance',
                                ])
                                ->label('Maintenance Type')
                                ->searchable()
                                ->required(),
                        ]),

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
                            // ->default(fn($record) => Device::where('site_id', $record->site_id))
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
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_transceiver.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->openable()->downloadable()
                            ->nullable(),

                        Forms\Components\FileUpload::make('feedhorn_img')
                            ->label('Feedhorn')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_feedhorn.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('antenna_img')
                            ->label('Dish Antenna')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_antenna.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),
                        // ->panelLayout('integrated')

                        Forms\Components\FileUpload::make('stabillizer_img')
                            ->label('Stabillizer')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_stabillizer.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('rack_img')
                            ->label('Rack Indoor')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_rack.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('modem_img')
                            ->label('Modem')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_modem.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('router_img')
                            ->label('Router')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_router.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('ap1_img')
                            ->label('Access Point 1')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_ap1.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('ap2_img')
                            ->label('Access Point 2')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_ap2.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('modem_summary_img')
                            ->label('Modem Summary')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_modem_summary.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('pingtest_img')
                            ->label('Ping Test')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_pingtest.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('speedtest_img')
                            ->label('Speedtest')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_speedtest.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('cm_ba_img')
                            ->label('BA Corrective Maintenance')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_cm_ba.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('pm_ba_img')
                            ->label('BA Preventive Maintenance')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_pm_ba.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('signplace_img')
                            ->label('Sign')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_signplace.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('stabillizer_voltage_img')
                            ->label('Stabillizer Voltage')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_stabillizer_voltage.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),

                        Forms\Components\FileUpload::make('power_source_voltage_img')
                            ->label('Power Source Voltage')
                            ->directory(fn($record) => "tmo-images/{$record->tmo_id}")
                            ->imagePreviewHeight('250')
                            ->removeUploadedFileButtonPosition('right')->loadingIndicatorPosition('right')
                            ->uploadButtonPosition('right')->uploadProgressIndicatorPosition('right')
                            ->image()->optimize('jpg')
                            ->openable()->downloadable()
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str("_power_source_voltage.{$file->extension()}")->prepend(now()->timestamp);
                            })
                            ->nullable(),
                    ])
                    ->columns(4),


                Forms\Components\Section::make('Device Replacement')
                    ->schema([
                        // Forms\Components\TextInput::make('tmo_id'),

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
                                    ->directory(fn($get) => 'device-change/' . ($get('../../tmo_id') ?? 'default'))
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

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['tmo_id'])) {
            $data['device_img'] = 'device-change/' . $data['tmo_id'];
        }

        return $data;
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
            ->query(static::getEloquentQuery()->orderByDesc('tmo_start_date'))
            ->columns([
                Tables\Columns\TextColumn::make('tmo_id')->label('TMO ID')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('spmk_number')->label('No. SPMK')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(true)
                    ->placeholder('No SPMK Found')
                    ->copyable(),

                Tables\Columns\TextColumn::make('site_id')->label('Site ID')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('site_name')->label('Site Name')
                    ->limit(35)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_province')->label('Province')
                    ->searchable()
                    ->sortable()
                    ->limit(15)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\TextColumn::make('tmo_type')->label('TMO Type')
                    ->sortable()
                    ->badge()->color(fn(string $state): string => match ($state) {
                        'Corrective Maintenance' => 'primary',
                        'Preventive Maintenance' => 'secondary',
                    })
                    ->formatStateUsing(function ($state) {
                        return $state === "Preventive Maintenance" ? "Preventive" : "Corrective";
                    }),

                Tables\Columns\TextColumn::make('engineer_name')
                    ->label('Technician')
                    ->limit(15)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Rejected' => 'danger',
                        'Approved' => 'success',
                    })
                    ->tooltip(
                        fn(TmoData $record) => $record->approval === "Pending" ?
                            null :
                            "At " . Carbon::parse($record->updated_at)->translatedFormat('d M Y H:i') .
                            ($record->approver?->name ?  " by " .  $record->approver?->name : "")
                    ),

                Tables\Columns\TextColumn::make('cboss_tmo_code')
                    ->label('TMO Code')
                    ->searchable()
                    ->copyable()
                    ->placeholder(
                        fn(TmoData $record) =>
                        $record->tmo_end_date || $record->pic_name ||
                            $record->action_json || $record->problem_json ?
                            "Waiting Approval" :
                            "Data Unfinished"
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('TMO Created Date')
                    ->date('d M Y H:i')
                    ->toggleable()
                    ->toggledHiddenByDefault(true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval_by')
                    ->label('Approver')
                    ->limit(15)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->placeholder("Unidentified")
                    ->sortable(),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('approval')
                //     ->label("TMO Approval")
                //     ->options(fn() => TmoData::query()->pluck('approval', 'approval')),

                Tables\Filters\SelectFilter::make('site_province')
                    ->label("Province")
                    ->options(
                        function () {
                            if (auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)) {
                                return TmoData::query()
                                    // ->where('created_by', auth()->id())
                                    ->pluck('site_province', 'site_province');
                            }

                            return TmoData::query()->pluck('site_province', 'site_province');
                        }
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('area')
                    ->label("Area")
                    ->options(fn() => AreaList::all()->pluck('area', 'area')) //you probably want to limit this in some way?
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }
                        return $query->whereHas('area', fn($query) => $query->where('area', $state['value']));
                    }),

                Tables\Filters\Filter::make('tmo_created')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label("Created Date"),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            );
                    })->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'TMO Created Date : ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\EditAction::make('edit'),

                    Tables\Actions\Action::make('add_note')
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
                                    <strong>Note from {$currentUser} :</strong><br>
                                    {$data['approval_details']}<br>
                                    "
                                )
                                ->actions([
                                    Action::make('progress')
                                        ->link()
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
                        ->color('gray'),

                    Tables\Actions\Action::make('approve')
                        ->form([
                            Forms\Components\TextInput::make('cboss_tmo_code')
                                ->label('CBOSS Code')
                                ->autofocus()
                                ->autocomplete(false)
                                ->required(),

                            Forms\Components\Textarea::make('approval_details')
                                ->label('Approval Note'),
                        ])
                        ->label('Approvez')
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
                                                ->link()
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
                        ->modalHeading("TMO Approval")
                        ->modalDescription("Are you sure want to Approve this TMO?")
                        ->modalSubmitActionLabel("Approve TMO")
                        ->requiresConfirmation() // Menambahkan konfirmasi sebelum eksekusi
                        ->color('primary'),

                    Tables\Actions\Action::make('reject')
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
                                            Action::make('view')
                                                ->link()
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
                ])
                    ->icon('phosphor-dots-three-vertical-duotone')->dropdown()
                    ->visible(
                        fn(TmoData $record) =>
                        $record->approval === 'Pending' && auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)
                    )
                // ->visible(fn(TmoData $record) => $record->approval === 'Pending' && auth()->user()->hasRole('super_admin'))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->heading('TMO RTGS')
            ->description('Mahaga TMO RTGS Maintenance - Network Operation Center.')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label("Add New TMO")
                    ->icon('phosphor-plus-circle-duotone')
                    ->visible(fn() => auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4) ? true : false),
            ])
            ->recordUrl(
                fn(TmoData $record): string =>
                $record->update_at || $record->pic_name || $record->pic_name || auth()->user()->roles->pluck('id')->some(fn($id) => $id < 5) ?
                    Pages\ViewTMOData::getUrl([$record->tmo_id]) :
                    Pages\EditTMOData::getUrl([$record->tmo_id]),
            )
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->roles->pluck('id')->contains(4)) {
                    return $query->where('created_by', auth()->id());
                }

                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                    return $query->where('engineer_name', auth()->user()->name);
                }
            })
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No Assigned TMO yet')
            ->emptyStateDescription('Once you have been assign your first TMO, it will appear here.')
            ->emptyStateIcon('phosphor-hand-withdraw-duotone')
        ;
    }

    protected function getTablePollingInterval(): ?string
    {
        return '60s';
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

    public static function canCreate(): bool
    {
        return auth()->user()->roles->pluck('id')->some(fn($id) => $id > 3) ? false : true;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->approval === 'Pending' ? true : false;
    }
}
