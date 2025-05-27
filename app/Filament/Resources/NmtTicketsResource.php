<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NmtTicketsResource\Pages;
use App\Filament\Resources\NmtTicketsResource\RelationManagers;
use App\Models\AreaList;
use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;

class NmtTicketsResource extends Resource
{
    protected static ?string $model = NmtTickets::class;

    protected static ?string $navigationLabel = 'NMT Ticket';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'NMT Ticket';
    protected static ?string $modelLabel = 'NMT Ticket';

    protected static ?string $navigationIcon = 'phosphor-tag-chevron-duotone';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return NmtTickets::where('status', "OPEN")
            ->whereHas('siteMonitor', function ($query) {
                $query->where('modem_last_up', '=', null)->orWhere('modem_last_up', '>=', now()->subDays(2));
            })
            ->count(); // Hitung total data
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('date_start')
                    ->required(),
                Forms\Components\TextInput::make('aging')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('problem_classification')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('problem_detail')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('problem_type')
                    ->required()
                    ->maxLength(20),
                Forms\Components\Textarea::make('update_progress')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->orderByDesc('status')->orderByDesc('aging'))
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label("Ticket ID")
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_id')
                    ->copyable()
                    ->label("Site ID")
                    ->searchable(),

                Tables\Columns\TextColumn::make('site.site_name')
                    ->label("Site Name")
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_province')
                    ->label("Province")
                    ->limit(15)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label("Status")
                    ->badge()
                    ->color(function ($state) {
                        if (str_contains($state, "OPEN")) {
                            return "warning";
                        } elseif (str_contains($state, "CLOSE")) {
                            return 'success';
                        } else {
                            return 'gray';
                        }
                    })
                    ->formatStateUsing(function ($state) {
                        $data = $state;

                        if (str_contains($data, "OPEN")) {
                            $data = "OPEN";
                        } else if (str_contains($data, "CLOSE")) {
                            $data = "CLOSED";
                        }

                        return Str::title($data);
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('aging')
                    ->label("Aging")
                    ->formatStateUsing(fn($state) => $state > 1 ? $state . " days" : $state . " day")
                    ->tooltip(
                        fn($record) => "Date Start : " . Carbon::parse($record->date_start)->translatedFormat('d M Y')
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
                        return $modemTime->diffInDays($now) <= 3 ? 'success' : 'gray';
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

                Tables\Columns\TextColumn::make('problem_classification')
                    ->label("Classification")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('problem_detail')
                    ->label("Detail")
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_start')
                    ->label("Date Start TT")
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format("d M Y"))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('problem_type')
                    ->label("Type")
                    ->badge()->color(fn(string $state): string => match ($state) {
                        'TEKNIS' => 'primary',
                        'NON TEKNIS' => 'secondary',
                        'Belum Ada Info' => 'danger',
                    })
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label("Status")
                    ->native(false)
                    ->options(fn() => NmtTickets::query()->pluck('status', 'status')),

                Tables\Filters\SelectFilter::make('problem_classification')
                    ->label("Problem Classification")
                    ->native(false)
                    ->options(fn() => NmtTickets::query()->pluck('problem_classification', 'problem_classification')),

                Tables\Filters\SelectFilter::make('aging')
                    ->label('Aging/Duration')
                    ->options([
                        'warning' => '≤ 3 days',
                        'minor' => '> 4-7 days',
                        'major' => '> 8-14 days',
                        'critical' => '> 15-30 days',
                        'sp1' => '> 30 days',
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $state) {
                        if (!isset($state['value']) || empty($state['value'])) {
                            return $query; // Jika tidak ada filter yang dipilih, kembalikan query tanpa filter
                        }

                        return $query->whereHas('siteMonitor', function ($query) use ($state) {
                            if ($state['value'] === 'warning') {
                                $query->where('aging', '<=', 3);
                            } elseif ($state['value'] === 'minor') {
                                $query->where('aging', '>=', 4)->where('aging', '<=', 7);
                            } elseif ($state['value'] === 'major') {
                                $query->where('aging', '>=', 8)->where('aging', '<=', 14);
                            } elseif ($state['value'] === 'critical') {
                                $query->where('aging', '>=', 15)->where('aging', '<=', 30);
                            } elseif ($state['value'] === 'sp1') {
                                $query->where('aging', '>', 30);
                            }
                        });
                    }),

                Tables\Filters\SelectFilter::make('area')
                    ->label("Area")
                    ->options(fn() => AreaList::all()->pluck('area', 'area'))
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }
                        return $query->whereHas('area', fn($query) => $query->where('area', $state['value']));
                    }),

                Tables\Filters\SelectFilter::make('modem_last_up')
                    ->label('Modem Last Up')
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

                DateRangeFilter::make('actual_online')
                    ->label('Actual Online Date')
                    ->linkedCalendars(),

                // Tables\Filters\Filter::make('actual_online')
                //     ->form([
                //         DatePicker::make('actual_online_date')
                //             ->label('Actual Online Date')
                //             // ->default(Carbon::today()) // Default ke hari ini
                //             ->displayFormat('d F Y') // Format Indo: 18 April 2025
                //             ->locale('id'), // Format tanggal dalam bahasa Indonesia
                //     ])
                //     ->query(function ($query, array $data) {
                //         if ($data['actual_online_date']) {
                //             $selectedDate = Carbon::parse($data['actual_online_date'])->startOfDay();
                //             $query->whereDate('closed_date', $selectedDate);
                //         }
                //     })
                //     ->indicateUsing(function (array $data): ?string {
                //         if ($data['actual_online_date']) {
                //             $formattedDate = Carbon::parse($data['actual_online_date'])->translatedFormat('d F Y');
                //             return "Actual Online: $formattedDate";
                //         }
                //         return null;
                //     }),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->label("Details")
                    ->modalHeading("Ticket Detail"),
            ])
            ->headerActions([
                CopyAction::make('generate_report')
                    // ->link()
                    ->color('gray')
                    ->label('Generate PMU Report')
                    ->successNotificationTitle('Report copied to clipboard')
                    ->copyable(function () {
                        return static::generateReportString();
                    })
                    ->icon('phosphor-file-txt-duotone'),

                Action::make('Import Data')
                    ->button()
                    ->label("Get Data from GSheet")
                    ->action(fn() => Artisan::call('fetch:nmt-tickets'))
                    ->requiresConfirmation()
                    ->successNotificationTitle('Data berhasil diimport')
                    ->icon('phosphor-plus-circle-duotone'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->heading("Mahaga NMT Tickets")
            ->description("Summary of BAKTI RTGS Trouble Tickets - Network Operation Center. ")
            ->emptyStateHeading('No NMT Ticket yet')
            ->emptyStateDescription('Once you have been import NMT Ticket, it will appear here.')
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
            'index' => Pages\ListNmtTickets::route('/'),
            'create' => Pages\CreateNmtTickets::route('/create'),
            // 'edit' => Pages\EditNmtTickets::route('/{record}/edit'),
        ];
    }

    protected static function generateReportString(): string
    {
        // Ambil waktu saat ini
        Carbon::setLocale('id');

        // Ambil waktu saat ini
        $now = Carbon::now();
        $date = $now->translatedFormat('d F Y'); // Contoh: Minggu, 20 April 2025
        $timeOfDay = static::getTimeOfDay($now);

        // Query untuk mengelompokkan data
        $closed = NmtTickets::where('status', 'CLOSED')
            ->whereDate('closed_date', $now->startOfDay())
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $renovasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%renovasi%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $relokasi = NmtTickets::where('status', 'OPEN')
            ->where('problem_classification', 'LIKE', '%relokasi%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $liburSekolah = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%libur%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $bencanaAlam = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'LIKE', '%bencana%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        $open = NmtTickets::where('status', 'OPEN')
            ->where('problem_detail', 'NOT LIKE', '%renovasi%')
            ->where('problem_classification', 'NOT LIKE', '%relokasi%')
            ->where('problem_detail', 'NOT LIKE', '%libur%')
            ->where('problem_detail', 'NOT LIKE', '%bencana%')
            ->with('site')
            ->orderBy('aging', 'desc')
            ->get();

        // Hitung total
        $totalClosed = $closed->count();
        $totalRenovasi = $renovasi->count();
        $totalRelokasi = $relokasi->count();
        $totalLiburSekolah = $liburSekolah->count();
        $totalBencanaAlam = $bencanaAlam->count();
        $totalOpen = $open->count();

        $totalTickets = $totalOpen + $totalClosed + $totalBencanaAlam + $totalLiburSekolah + $totalRelokasi + $totalRenovasi;

        // Buat string header report
        $report = "Selamat $timeOfDay,\n\n";
        $report .= "Berikut Update TT PT. MAHAGA PRATAMA $date:\n\n";
        $report .= "> CATEGORY SL\n";
        $report .= "* ✅ Closed\t\t\t: $totalClosed\t\n";

        // Tambahkan hanya kategori dengan jumlah > 0 ke summary
        if ($totalOpen > 0) {
            $report .= "* ❌ Open\t\t\t: $totalOpen\t\n";
        }
        if ($totalRenovasi > 0) {
            $report .= "* ⚠️ Renovasi\t\t: $totalRenovasi\t\n";
        }
        if ($totalRelokasi > 0) {
            $report .= "* 🚫 Relokasi\t\t: $totalRelokasi\t\n";
        }
        if ($totalLiburSekolah > 0) {
            $report .= "* ❕ Libur Sekolah\t: $totalLiburSekolah\t\n";
        }
        if ($totalBencanaAlam > 0) {
            $report .= "* ❗ Bencana Alam\t: $totalBencanaAlam\t\n";
        }

        $report .= "\n* Total Ticket\t\t: $totalTickets\n\n";

        // Detail per kategori
        if ($totalClosed > 0) {
            $report .= static::generateCategoryDetails('✅ TT CLOSED', $closed, true, false, '✅');
        }
        if ($totalOpen > 0) {
            $report .= static::generateCategoryDetails('❌ TT OPEN', $open, false, false, '❌');
        }
        if ($totalRenovasi > 0) {
            $report .= static::generateCategoryDetails('🚫 RELOKASI', $relokasi, false, true, '🚫');
        }
        if ($totalRelokasi > 0) {
            $report .= static::generateCategoryDetails('⚠️ RENOVASI', $renovasi, false, true, '⚠️');
        }
        if ($totalLiburSekolah > 0) {
            $report .= static::generateCategoryDetails('❕ LIBUR SEKOLAH', $liburSekolah, false, false, '❕');
        }
        if ($totalBencanaAlam > 0) {
            $report .= static::generateCategoryDetails('❗ BENCANA ALAM', $bencanaAlam, false, true, '❗');
        }

        $report .= "Terimakasih, CC: Pak @Dodo.";

        return $report;
    }

    protected static function getTimeOfDay(Carbon $time): string
    {
        $hour = $time->hour;
        if ($hour >= 4 && $hour < 11) return 'Pagi';
        if ($hour >= 11 && $hour < 15) return 'Siang';
        if ($hour >= 15 && $hour < 18) return 'Sore';
        return 'Malam';
    }

    protected static function generateCategoryDetails(string $title, $tickets, bool $isClosed, bool $isNoTargetOnline, string $emoji): string
    {
        $details = "========================================================\n\n$title :\n\n";

        foreach ($tickets as $ticket) {
            $siteName = $ticket->site ? $ticket->site->site_name : 'Unknown';
            $details .= "> {$ticket->site_id} - $siteName $emoji" . "\n";
            if ($isClosed) {
                $actualOnline = Carbon::parse($ticket->actual_online)->format('d M Y');
                $details .= "Actual Online\t: $actualOnline\n";
            } else if (!$isNoTargetOnline) {
                $details .= "Durasi Open\t: {$ticket->aging} Hari\n";
                $targetOnline = Carbon::parse($ticket->target_online)->format('d M Y');
                $details .= "Target Online\t: $targetOnline\n";
                $details .= "Progress\t\t: {$ticket->update_progress}\n";
            } else {
                $details .= "Durasi Open\t: {$ticket->aging} Hari\n";
                $details .= "Target Online\t: -\n";
                $details .= "Progress\t\t: {$ticket->update_progress}\n";
            }
            $details .= "\n";
        }

        return $details;
    }
}
