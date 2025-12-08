<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SweepingTicketResource\Pages;
use App\Filament\Resources\SweepingTicketResource\RelationManagers;
use App\Models\AreaList;
use App\Models\SweepingTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SweepingTicketResource extends Resource
{
    protected static ?string $model = SweepingTicket::class;

    protected static ?string $navigationIcon = 'phosphor-broom-duotone';

    protected static ?string $navigationLabel = 'Sweeping Ticket';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'Sweeping Ticket';
    protected static ?string $modelLabel = 'Sweeping Ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('classification')
                    ->required()
                    ->maxLength(30),
                Forms\Components\Textarea::make('problem_classification')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cboss_tt')
                    ->maxLength(30),
                Forms\Components\Textarea::make('cboss_problem')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sweeping_id')
                    ->searchable()
                    ->label("Sweeping ID")
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('site_id')
                    ->copyable()
                    ->label("Site ID")
                    ->searchable(),

                Tables\Columns\TextColumn::make('siteDetail.site_name')
                    ->copyable()
                    ->label("Site Name")
                    ->searchable(),

                Tables\Columns\TextColumn::make('siteDetail.province')
                    ->label("Province")
                    ->searchable(),

                Tables\Columns\TextColumn::make('siteDetail.administrative_area')
                    ->label("Adminstrative Area")
                    ->searchable(),

                Tables\Columns\TextColumn::make('classification')
                    ->badge()
                    ->label("Classification")
                    ->color(function ($state) {
                        if ($state === "MAJOR") {
                            return 'danger';
                        }

                        if ($state === "MINOR") {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->formatStateUsing(fn($state) => ucfirst(strtolower($state)))
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label("Status")
                    ->searchable(),

                Tables\Columns\TextColumn::make('cboss_tt')
                    ->default("-")
                    ->label("CBOSS TT")
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
                Tables\Filters\SelectFilter::make('problem_classification')
                    ->label("Classification")
                    ->native(false)
                    ->options(fn() => SweepingTicket::query()->pluck('classification', 'classification')),

                Tables\Filters\SelectFilter::make('status')
                    ->label("Status")
                    ->native(false)
                    ->options(fn() => SweepingTicket::query()->pluck('status', 'status')),

                Tables\Filters\SelectFilter::make('area')
                    ->label("Area")
                    ->options(fn() => AreaList::all()->pluck('area', 'area'))
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('siteDetail.area', function (Builder $query) use ($data) {
                                $query->where('area', $data['value']);
                            });
                        }
                    }),

                DateRangeFilter::make('created_at')
                    ->label('Date Created'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->deferLoading()
            ->poll(null)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSweepingTickets::route('/'),
            // 'create' => Pages\CreateSweepingTicket::route('/create'),
            // 'edit' => Pages\EditSweepingTicket::route('/{record}/edit'),
        ];
    }
}
