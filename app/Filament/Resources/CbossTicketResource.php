<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CbossTicketResource\Pages;
use App\Filament\Resources\CbossTicketResource\RelationManagers;
use App\Imports\CbossTicketImport;
use App\Models\AreaList;
use App\Models\CbossTicket;
use App\Models\SiteMonitor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Enums\OpenDirection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;

class CbossTicketResource extends Resource
{
    protected static ?string $model = CbossTicket::class;

    protected static ?string $navigationLabel = 'CBOSS Ticket';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected static ?string $pluralModelLabel = 'CBOSS Tickets';
    protected static ?string $modelLabel = 'CBOSS Ticket';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('spmk')
                    ->maxLength(255),
                Forms\Components\TextInput::make('problem_map')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('trouble_category')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('detail_action')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('ticket_start'),
                Forms\Components\DateTimePicker::make('ticket_end'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->orderByDesc('ticket_start'))
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label("Ticket ID")
                    ->description(function($record): string {
                        $spmkState = $record->spmk;

                        if (str_contains($spmkState, "NA1-MHG/NOM/")) {
                            return explode("-", $spmkState)[0] . "/" . explode("/", $spmkState)[2] . "/" . explode("/", $spmkState)[3];
                        }

                        return $spmkState ?? "No SPMK";
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_id')
                    ->label("Site ID")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('siteDetail.site_name')
                    ->label("Site Name")
                    ->description(fn($record): string => $record->site_id, 'above')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    })
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('spmk')
                    ->label("SPMK Number")
                    ->default("No SPMK")
                    ->tooltip(fn($state) => $state)
                    ->formatStateUsing(function ($state) {
                        if (str_contains($state, "NA1-MHG/NOM/")) {
                            return explode("-", $state)[0] . "/" . explode("/", $state)[2] . "/" . explode("/", $state)[3];
                        }

                        return $state;
                    })
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('province')
                    ->label("Province")
                    ->description(fn($record): string => $record->siteDetail->area->area)
                    ->formatStateUsing(fn($state) => ucwords(strtolower($state)))
                    ->searchable(),

                // Tables\Columns\TextColumn::make('siteDetail.area.head_po')
                //     ->label("Head PO")
                //     ->searchable(),

                Tables\Columns\TextColumn::make('problem_map')
                    ->label("Problem Map")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('trouble_category')
                    ->label("Trouble Category")
                    ->description(fn($record): string => $record->problem_map, 'above')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label("Status")
                    ->badge()
                    ->color(fn($state) => $state === "Closed" ? "primary" : "warning")
                    ->tooltip(function ($record, $state): ?string {
                        if ($state === "Closed") {
                            return "Closed at : " . Carbon::parse($record->ticket_end)->format("d M Y H:i");
                        }

                        return "";
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('ticket_start')
                    ->label("Open Date")
                    ->since()
                    ->tooltip(
                        fn($record) => "Date Start : " . Carbon::parse($record->ticket_start)->translatedFormat('d M Y')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('siteMonitor.modem_last_up')
                    ->label('Modem Last Up')
                    ->default("Online")
                    ->badge()
                    ->color(function ($state) {
                        if ($state === "Online") {
                            return 'success'; // Jika "Up", warna hijau (success)
                        }

                        $modemTime = Carbon::parse($state);
                        $now = Carbon::now();

                        // Jika selisih kurang dari atau sama dengan 3 hari → success (hijau)
                        // Jika lebih dari 3 hari → danger (merah)
                        return $modemTime->diffInHours($now) <= 6 ? 'success' : 'gray';
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state === "Online") {
                            return "Online";
                        }

                        Carbon::setLocale('en');

                        return Carbon::parse($state)
                            ->diffForHumans();
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sensorDetails')
                    ->label('Sensor Status')
                    ->default("All Sensor Online")
                    // ->badge()
                    ->formatStateUsing(function ($record) {
                        // Ambil data dari relasi siteMonitor
                        $siteMonitor = $record->siteMonitor;

                        // Jika tidak ada siteMonitor atau semua null, kembalikan "Online"
                        if (!$siteMonitor || (
                            is_null($siteMonitor->modem_last_up) &&
                            is_null($siteMonitor->mikrotik_last_up) &&
                            is_null($siteMonitor->ap1_last_up) &&
                            is_null($siteMonitor->ap2_last_up)
                        )) {
                            return 'Online';
                        }

                        // Ambil semua waktu yang tidak null
                        $times = [];
                        if ($siteMonitor->modem_last_up) {
                            $times['modem'] = Carbon::parse($siteMonitor->modem_last_up);
                        }
                        if ($siteMonitor->mikrotik_last_up) {
                            $times['router'] = Carbon::parse($siteMonitor->mikrotik_last_up);
                        }
                        if ($siteMonitor->ap1_last_up) {
                            $times['ap1'] = Carbon::parse($siteMonitor->ap1_last_up);
                        }
                        if ($siteMonitor->ap2_last_up) {
                            $times['ap2'] = Carbon::parse($siteMonitor->ap2_last_up);
                        }

                        // Jika ada waktu, cek apakah semua sama
                        if (!empty($times)) {
                            $uniqueTimes = array_unique(array_map(fn($time) => $time->toDateTimeString(), $times));
                            if (count($uniqueTimes) === 1 && isset($times['modem'])) {
                                // Semua waktu sama dan modem down, kembalikan "All Sensor Down"
                                Carbon::setLocale('en');
                                return 'All Sensor Down';
                            }

                            // Ambil waktu paling lama (datetime terkecil)
                            $earliest = null;
                            $earliestKey = null;
                            foreach ($times as $key => $time) {
                                if (is_null($earliest) || $time->lt($earliest)) {
                                    $earliest = $time;
                                    $earliestKey = $key;
                                }
                            }

                            // Tentukan status berdasarkan prioritas
                            if ($earliestKey === 'modem') {
                                Carbon::setLocale('en');
                                return 'All Sensor Down';
                            } elseif ($earliestKey === 'router') {
                                return 'Router Down';
                            } elseif ($earliestKey === 'ap1' && isset($times['ap2']) && $times['ap1']->equalTo($times['ap2'])) {
                                return 'AP1&2 Down';
                            } elseif ($earliestKey === 'ap1') {
                                return 'AP1 Down';
                            } elseif ($earliestKey === 'ap2') {
                                return 'AP2 Down';
                            }
                        }

                        // Fallback (seharusnya tidak sampai sini)
                        return 'Online';
                    }),

                Tables\Columns\TextColumn::make('ticket_end')
                    ->label("Closed Date")
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format("d M Y H:i") : "-")
                    ->placeholder("Ticket on Progress")
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->label("Status")
                        ->multiple()
                        ->options(fn() => CbossTicket::all()->pluck('status', 'status'))
                        ->native(false)
                        ->searchable(),

                    Tables\Filters\SelectFilter::make('area')
                        ->label("Area")
                        ->options(fn() => AreaList::all()->pluck('area', 'area'))
                        ->native(false)
                        ->modifyQueryUsing(function (Builder $query, $state) {
                            if (! $state['value']) {
                                return $query;
                            }
                            return $query->whereHas('area', fn($query) => $query->where('area', $state['value']));
                        }),

                    Tables\Filters\SelectFilter::make('problem_map')
                        ->label("Problem Map")
                        ->options(fn() => CbossTicket::all()->pluck('problem_map', 'problem_map'))
                        ->native(false)
                        ->searchable(),

                    Tables\Filters\SelectFilter::make('trouble_category')
                        ->label("Category")
                        ->multiple()
                        ->options(fn() => CbossTicket::all()->pluck('trouble_category', 'trouble_category'))
                        ->native(false)
                        ->searchable(),

                    Tables\Filters\SelectFilter::make('modem_last_up')
                        ->label('Modem Last Up')
                        ->native(false)
                        ->options([
                            'now' => 'Up (Online)',
                            'recent' => '≤ 1 days ago',
                            'old' => '> 2 days ago',
                        ])
                        ->modifyQueryUsing(function (Builder $query, array $state) {
                            if (!isset($state['value']) || empty($state['value'])) {
                                return $query; // Jika tidak ada filter yang dipilih, kembalikan query tanpa filter
                            }

                            return $query->whereHas('siteMonitor', function ($query) use ($state) {
                                if ($state['value'] === 'now') {
                                    $query->whereNull('modem_last_up');
                                } elseif ($state['value'] === 'recent') {
                                    $query->where('modem_last_up', '>=', now()->subDays(1))->orWhereNull('modem_last_up');
                                } elseif ($state['value'] === 'old') {
                                    $query->where('modem_last_up', '<', now()->subDays(2));
                                }
                            });
                        }),

                    Tables\Filters\SelectFilter::make('sensorStatus')
                        ->label("Sensor Status")
                        ->native(false)
                        ->options(fn() => SiteMonitor::all()->pluck('status', 'status'))
                        ->modifyQueryUsing(function (Builder $query, $state) {
                            if (! $state['value']) {
                                return $query;
                            }
                            return $query->whereHas('siteMonitor', fn($query) => $query->where('status', $state['value']));
                        }),

                    DateRangeFilter::make('ticket_start')
                        ->label("Date Open")
                        ->opens(OpenDirection::CENTER)
                        ->drops(DropDirection::AUTO),

                    DateRangeFilter::make('ticket_end')
                        ->label("Date Closed")
                        ->opens(OpenDirection::CENTER)
                        ->drops(DropDirection::AUTO),
                ],
                layout: FiltersLayout::Modal
            )
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // CopyAction::make('generate_report')
                //     ->label('Generate TMO Report')
                //     ->copyable(function () {
                //         return static::generateTmoReport();
                //     })
                //     ->successNotificationTitle('TMO Report copied to clipboard!')
                //     ->color('gray')
                //     ->icon('phosphor-file-txt-duotone'),

                Tables\Actions\Action::make('import_excel')
                    ->label('Import from Excel')
                    ->icon('phosphor-file-xls-duotone')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('excel_file')
                            ->label('Excel File')
                            // ->acceptedFileTypes([
                            //     'application/vnd.ms-excel', // .xls
                            //     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            // ])
                            ->storeFiles(false) // Jangan simpan file permanen
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $file = $data['excel_file'];

                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                // Simpan file sementara ke disk
                                $tempPath = $file->store('temp', 'local');
                                $fullPath = storage_path('app/' . $tempPath);

                                // Log path file asli
                                Log::info('Original file stored at: ' . $fullPath);

                                // Baca file menggunakan PhpSpreadsheet
                                $reader = IOFactory::createReaderForFile($fullPath);
                                $reader->setReadDataOnly(true); // Optimasi untuk membaca data saja
                                $spreadsheet = $reader->load($fullPath);

                                // Simpan sebagai .xlsx
                                $convertedPath = 'temp/converted_' . uniqid() . '.xlsx';
                                $fullConvertedPath = storage_path('app/' . $convertedPath);
                                $writer = new Xlsx($spreadsheet);
                                $writer->save($fullConvertedPath);

                                // Log path file yang dikonversi
                                Log::info('Converted file stored at: ' . $fullConvertedPath);

                                // Impor file yang dikonversi
                                Excel::import(new CbossTicketImport, $fullConvertedPath);

                                // Hapus file sementara
                                Storage::disk('local')->delete($tempPath);
                                Storage::disk('local')->delete($convertedPath);

                                \Filament\Notifications\Notification::make()
                                    ->title('Import Successful')
                                    ->body('Data has been imported successfully.')
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception('Invalid file upload.');
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            Log::error('Excel Import Error: ' . $e->getMessage());
                        }
                    }),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->heading("Mahaga CBOSS Tickets")
            ->description("Summary of RTGS Internal Trouble Tickets (CBOSS) - Network Operation Center. ")
            ->emptyStateHeading('No CBOSS Ticket..')
            ->emptyStateDescription('Once your CBOSS Ticket available, it will appear here.')
            ->emptyStateIcon('phosphor-ticket-duotone');
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
            'index' => Pages\ListCbossTickets::route('/'),
            // 'create' => Pages\CreateCbossTicket::route('/create'),
            // 'edit' => Pages\EditCbossTicket::route('/{record}/edit'),
        ];
    }
}
