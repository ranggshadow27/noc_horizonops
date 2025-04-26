<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CbossTmoResource\Pages;
use App\Filament\Resources\CbossTmoResource\RelationManagers;
use App\Imports\CbossTmoImport;
use App\Models\CbossTmo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;

class CbossTmoResource extends Resource
{
    protected static ?string $model = CbossTmo::class;

    protected static ?string $navigationLabel = 'TMO CBOSS';
    protected static ?string $navigationGroup = 'TMO';

    protected static ?string $pluralModelLabel = 'TMO CBOSS';
    protected static ?string $modelLabel = 'TMO CBOSS';

    protected static ?string $navigationIcon = 'phosphor-hand-withdraw-duotone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('province')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('spmk_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('techinican_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('techinican_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pic_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pic_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tmo_by')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tmo_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('esno')
                    ->numeric(),
                Forms\Components\TextInput::make('sqf')
                    ->numeric(),
                Forms\Components\TextInput::make('ifl_cable')
                    ->maxLength(20),
                Forms\Components\Textarea::make('problem')
                    ->autosize()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('action')
                    ->required(),
                Forms\Components\TextInput::make('homebase')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('tmo_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->orderByDesc('tmo_date'))
            ->columns([
                Tables\Columns\TextColumn::make('tmo_id')
                    ->label("TMO ID")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        return explode("/", $state)[3];
                    })
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('spmk_number')
                    ->label("SPMK Number")
                    ->tooltip(fn($state) => $state)
                    ->formatStateUsing(function ($state) {
                        if (str_contains($state, "NA1-MHG/NOM/")) {
                            return explode("/", $state)[2] . "/" . explode("/", $state)[3];
                        }

                        return $state;
                    })
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('siteDetail.site_name')
                    ->label("Site Name")
                    ->description(fn($record): string => $record->site_id, 'above')
                    ->limit(35)
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

                Tables\Columns\TextColumn::make('province')
                    ->label("Province")
                    ->formatStateUsing(fn($state) => ucwords(strtolower($state)))
                    ->searchable(),

                Tables\Columns\TextColumn::make('techinican_name')
                    ->label("Technician")
                    ->default("-")
                    ->description(fn($record): string => $record->techinican_number ?? "-")
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    })
                    ->formatStateUsing(fn($state) => ucwords(strtolower($state)))
                    ->searchable(),

                Tables\Columns\TextColumn::make('pic_name')
                    ->label("PIC")
                    ->default("-")
                    ->description(fn($record): string => $record->pic_number ?? "-")
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    })
                    ->formatStateUsing(fn($state) => ucwords(strtolower($state)))
                    ->searchable(),

                Tables\Columns\TextColumn::make('tmo_code')
                    ->label("TMO Code")
                    ->copyable()
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('action')
                    ->label("Action")
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        $implodeState = implode(', ', $state);

                        if (strlen($implodeState) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $implodeState;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('tmo_by')
                    ->label("TMO By")
                    ->description(fn($record): string => "Date: " . Carbon::parse($record->tmo_date)->format("d M Y H:i"))
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('homebase')
                    ->label("Homebase")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                // Tables\Columns\TextColumn::make('tmo_date')
                //     ->dateTime()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->heading("Mahaga CBOSS TMO")
            ->description("BAKTI RTGS Maintenance Data - Network Operation Center. ")
            ->headerActions([
                CopyAction::make('generate_report')
                    ->label('Generate TMO Report')
                    ->copyable(function () {
                        return static::generateTmoReport();
                    })
                    ->successNotificationTitle('TMO Report copied to clipboard!')
                    ->color('gray')
                    ->icon('phosphor-file-txt-duotone'),

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
                                Excel::import(new CbossTmoImport, $fullConvertedPath);

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
            ]);
    }

    public static function generateTmoReport(): string
    {
        Carbon::setLocale('id');

        $now = Carbon::now();
        $formattedDate = $now->translatedFormat('d F Y'); // Contoh: Minggu, 20 April 2025

        // Fetch TMO records for the given date
        $tmos = CbossTmo::whereDate('tmo_date', $now->subDay()->startOfDay())
            ->with('siteDetail')
            ->get();

        // Initialize counters
        $preventiveCount = 0;
        $correctiveCount = 0;

        // Process each TMO to categorize based on action
        foreach ($tmos as $tmo) {
            $action = is_string($tmo->action) ? json_decode($tmo->action, true) : $tmo->action;
            $actionString = is_array($action) ? implode(', ', $action) : $action;

            if (is_array($action) && in_array('PM', $action)) {
                $preventiveCount++;
            } else {
                $correctiveCount++;
            }
        }

        $totalTmo = $preventiveCount + $correctiveCount;

        // Group TMO by Approver (tmo_by) and count occurrences
        $tmoByCounts = $tmos->groupBy('tmo_by')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'tmos' => $group // Sort TMO by tmo_date desc within each Approver
                ];
            })
            ->sortByDesc('count'); // Sort Approvers by TMO count desc

        // Start building the report
        $report = "Dear All,\n\n";
        $report .= "Berikut summary TMO Maintenance RTGS Mahaga, {$formattedDate} :\n\n";
        $report .= "> Maintenance Category\n";
        $report .= "- Preventive\t: {$preventiveCount}\n";
        $report .= "- Corrective\t: {$correctiveCount}\n";
        $report .= "\n- Total TMO\t\t: {$totalTmo}\n\n";

        // Detailed site information
        $index = 1;
        foreach ($tmoByCounts as $approver => $data) {
            foreach ($data['tmos'] as $tmo) {
                $action = is_string($tmo->action) ? json_decode($tmo->action, true) : $tmo->action;
                $actionString = is_array($action) ? implode(', ', $action) : $action;

                $splitSpmk = explode("/", $tmo->spmk_number);
                $spmkFormat = $splitSpmk[2] . "/" . $splitSpmk[3];
                $siteName = $tmo->siteDetail ? $tmo->siteDetail->site_name : 'N/A';
                $siteProvince = $tmo->siteDetail ? $tmo->siteDetail->province : 'N/A';

                $report .= "> {$index}. SPMK : *{$spmkFormat}* - {$tmo->site_id} - {$siteName} - {$siteProvince}\n";
                $report .= "EoS : *{$tmo->techinican_name}*\n";
                $report .= "Action : {$actionString}\n";
                $report .= "Approver : *{$tmo->tmo_by}*  |  Kode Lapor : *{$tmo->tmo_code}*\n\n";
                $index++;
            }
        }

        $report .= "Terimakasih";

        return $report;
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
            'index' => Pages\ListCbossTmos::route('/'),
            'create' => Pages\CreateCbossTmo::route('/create'),
            // 'edit' => Pages\EditCbossTmo::route('/{record}/edit'),
        ];
    }
}
