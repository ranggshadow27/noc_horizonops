<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NmtTicketsResource\Pages;
use App\Filament\Resources\NmtTicketsResource\RelationManagers;
use App\Models\AreaList;
use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                $query->where('modem_last_up', '=', null)->orWhere('modem_last_up', '>=', now()->subDays(5));
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_id')
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
                        if ($state === "OPEN") {
                            return "warning";
                        } elseif ($state === "CLOSED") {
                            return 'success';
                        } else {
                            return 'gray';
                        }
                    })
                    ->formatStateUsing(fn($state) => Str::title($state))
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
                        'recent' => '≤ 3 days ago',
                        'old' => '> 3 days ago',
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $state) {
                        if (!isset($state['value']) || empty($state['value'])) {
                            return $query; // Jika tidak ada filter yang dipilih, kembalikan query tanpa filter
                        }

                        return $query->whereHas('siteMonitor', function ($query) use ($state) {
                            if ($state['value'] === 'now') {
                                $query->whereNull('modem_last_up');
                            } elseif ($state['value'] === 'recent') {
                                $query->where('modem_last_up', '>=', now()->subDays(5))->orWhere('modem_last_up', '=', null);
                            } elseif ($state['value'] === 'old') {
                                $query->where('modem_last_up', '<', now()->subDays(3));
                            }
                        });
                    })
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->label("Details")
                    ->modalHeading("Ticket Detail"),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
}
