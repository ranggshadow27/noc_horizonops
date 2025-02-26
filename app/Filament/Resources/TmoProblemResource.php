<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TmoProblemResource\Pages;
use App\Filament\Resources\TmoProblemResource\RelationManagers;
use App\Models\TmoProblem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TmoProblemResource extends Resource
{
    protected static ?string $model = TmoProblem::class;

    protected static ?string $navigationIcon = 'phosphor-flag-banner-fold-duotone';

    protected static ?string $navigationLabel = 'Problem List';
    protected static ?string $navigationGroup = 'TMO';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'Problem List';
    protected static ?string $modelLabel = 'Problem List';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('problem_classification')
                    ->options([
                        'MASALAH PERANGKAT IDU' => 'Masalah Perangkat IDU',
                        'MASALAH PERANGKAT ODU' => 'Masalah Perangkat ODU',
                        'MASALAH SUMBER DAYA LISTRIK' => 'Masalah Sumber Daya Listrik',
                        'LAYANAN SEMENTARA DIMATIKAN' => 'Layanan Sementara Dimatikan',
                        'MAINTENANCE' => 'Maintenance',
                        'OTHERS' => 'Others',
                    ])
                    ->native(false)
                    ->required()
                    ->searchable()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('problem_type')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('problem_classification')
                    ->options([
                        'TEKNIS' => 'TEKNIS',
                        'NON-TEKNIS' => 'NON-TEKNIS',
                    ])
                    ->native(false)
                    ->required()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('problem_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('problem_classification')
                    ->searchable(),
                Tables\Columns\TextColumn::make('problem_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('problem_category'),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTmoProblems::route('/'),
        ];
    }
}
