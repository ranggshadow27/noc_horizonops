<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TmoTaskResource\Pages;
use App\Filament\Resources\TmoTaskResource\RelationManagers;
use App\Models\SiteDetail;
use App\Models\TmoTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TmoTaskResource extends Resource
{
    protected static ?string $model = TmoTask::class;

    protected static ?string $navigationLabel = 'TMO Task';
    protected static ?string $navigationGroup = 'TMO';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'TMO Task';
    protected static ?string $modelLabel = 'TMO Task';

    protected static ?string $navigationIcon = 'phosphor-clipboard-text-duotone';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Maintenance Information')
                    ->schema([
                        Forms\Components\TextInput::make('spmk_number')
                            ->required()
                            ->label('SPMK Number')
                            ->maxLength(255),

                        Forms\Components\Select::make('tmo_type')
                            ->options([
                                'Preventive Maintenance' => 'Preventive Maintenance',
                                'Corrective Maintenance' => 'Corrective Maintenance',
                            ])
                            ->default('Corrective Maintenance')
                            ->label('Maintenance Type')
                            ->searchable()
                            ->required(),
                    ])->collapsible()->persistCollapsed()->columns(2),

                Forms\Components\Section::make('Site Assign')
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
                                    $set('province', $site->province);
                                    $set('address', $site->address);
                                    $set('latitude', $site->latitude);
                                    $set('longitude', $site->longitude);
                                }
                            })
                            ->required()->columnSpan(2),

                        Forms\Components\Hidden::make('site_name')
                            ->label('Site Name'),

                        Forms\Components\TextInput::make('province')
                            ->label('Site Province')
                            ->required()
                            ->maxLength(255)
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('address')
                            ->label('Site Address')
                            ->required()
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->maxLength(25)
                            ->disabled()->dehydrated(true),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->maxLength(25)
                            ->disabled()->dehydrated(true),

                    ])->collapsible()->persistCollapsed()->columns(2),

                Forms\Components\Section::make('Technician Assign')
                    ->schema([
                        Forms\Components\Select::make('engineer')
                            ->label('Name')
                            ->options(User::whereHas(
                                'roles',
                                function ($query) {
                                    $query->where('name', 'panel_user');
                                }
                            )->pluck('name', 'name'))
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Auto-fill data when site_id is selected
                                $site = User::where('name', $state)->first();
                                if ($site) {
                                    $set('engineer', $site->name);
                                    $set('engineer_number', $site->number);
                                }
                            })
                            ->reactive()
                            ->searchable()
                            ->preload()
                            ->required(),

                        PhoneInput::make('engineer_number')
                            ->label('Phone Number')
                            ->onlyCountries(['id'])
                            ->required(),
                    ])->collapsible()->persistCollapsed()->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tmo_id')
                    ->label("TMO ID")
                    ->searchable(),

                Tables\Columns\TextColumn::make('spmk_number')
                    ->label("No. SPMK")
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_id')
                    ->label("Site ID")
                    ->searchable(),

                Tables\Columns\TextColumn::make('site_name')
                    ->label("Site Name")
                    ->searchable(),

                Tables\Columns\TextColumn::make('province')
                    ->label("Province")
                    ->searchable(),

                Tables\Columns\TextColumn::make('engineer')
                    ->label("Technician")
                    ->searchable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label("Assign By")
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->roles->pluck('id')->contains(2)) {
                    return $query->where('created_by', auth()->id());
                }
            })
            ->heading('TMO RTGS')
            ->description('List of TMO Maintenance Task.')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label("Add TMO Task")
                    ->icon('phosphor-plus-circle-duotone'),
                // ->visible(fn() => auth()->user()->roles->pluck('name')->contains('super_admin') ? true : false),
            ]);
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
            'index' => Pages\ListTmoTasks::route('/'),
            'create' => Pages\CreateTmoTask::route('/create'),
            'edit' => Pages\EditTmoTask::route('/{record}/edit'),
        ];
    }
}
