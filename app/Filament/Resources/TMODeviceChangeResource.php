<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TMODeviceChangeResource\Pages;
use App\Filament\Resources\TMODeviceChangeResource\RelationManagers;
use App\Models\TmoData;
use App\Models\TmoDeviceChange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class TMODeviceChangeResource extends Resource
{
    protected static ?string $model = TMODeviceChange::class;

    protected static ?string $navigationIcon = 'phosphor-arrows-left-right-duotone';

    protected static ?string $navigationLabel = 'Device Change';
    protected static ?string $navigationGroup = 'TMO';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'TMO Device Change';
    protected static ?string $modelLabel = 'TMO Device Change';
    protected static ?int $navigationSort = 3;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tmo_device_change_id')
                    ->label("Device Change ID")
                    ->hiddenOn('edit')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('device_name')
                    ->label("Device Name")
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('device_sn')
                    ->label("Serial Number")
                    ->required()
                    ->maxLength(25),

                Forms\Components\FileUpload::make('device_img')
                    ->label("Image")
                    ->openable()->downloadable()
                    ->directory(fn($record) => "device-changes-img/{$record->tmo_id}"),

                Forms\Components\TextInput::make('homebase')
                    ->label("Homebase")
                    ->maxLength(50),

                Forms\Components\TextInput::make('tmo_id')
                    ->label("TMO ID")
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('tmo_device_change_id')
                    ->label("Device Change ID")
                    ->searchable(),

                Tables\Columns\TextColumn::make('tmoData.site_id')
                    ->label("Site ID")
                    ->description(fn(TmoDeviceChange $record): string => $record->tmoData->site_name)
                    ->searchable(['site_id', 'site_name']),

                Tables\Columns\TextColumn::make('tmoData.site_name')
                    ->label("Site Name")
                    ->hidden(),

                Tables\Columns\TextColumn::make('device_name')
                    ->label("Device Name")
                    ->searchable(),

                Tables\Columns\TextColumn::make('device_sn')
                    ->label("Serial Number")
                    ->searchable(),


                Tables\Columns\TextColumn::make('homebase.location')
                    ->label("Homebase")
                    ->searchable(),

                Tables\Columns\ImageColumn::make('device_img')
                    ->label("Image")
                    ->height(60)
                    ->width(60),

                Tables\Columns\TextColumn::make('tmo_id')
                    ->label("TMO ID")
                    ->description(fn(TmoDeviceChange $record): string => $record->tmoData->approval)
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
                Tables\Filters\SelectFilter::make('approval')
                    ->label("TMO Approval")
                    ->options(fn() => TmoData::all()->pluck('approval', 'approval')) //you probably want to limit this in some way?
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }
                        return $query->whereHas('tmoData', fn($query) => $query->where('approval', $state['value']));
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
                            $indicators['created_from'] = 'Created Date : ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getTablePollingInterval(): ?string
    {
        return '60s';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTMODeviceChanges::route('/'),
        ];
    }
}
