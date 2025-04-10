<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SopResource\Pages;
use App\Filament\Resources\SopResource\RelationManagers;
use App\Models\Sop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SopResource extends Resource
{
    protected static ?string $model = Sop::class;


    protected static ?string $navigationLabel = 'SOP List';
    protected static ?string $navigationGroup = 'NOC Team';

    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $pluralModelLabel = 'SOP List';
    protected static ?string $modelLabel = 'SOP List';

    protected static ?string $navigationIcon = 'phosphor-archive-duotone';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SOP Information')
                    ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->autosize()
                    ->nullable(),
                Forms\Components\FileUpload::make('file_path')
                    ->disk('public')
                    ->preserveFilenames()
                    ->directory('sops')
                    ->required(),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label("ID")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label("Title")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label("Description")
                    ->limit(50),
                Tables\Columns\TextColumn::make('file_path')
                    ->label("File"),
                Tables\Columns\TextColumn::make('created_at')
                    ->label("Created At")
                    ->dateTime()
                    ->sortable(),
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
            ->heading('NOC Mahaga - SOP List')
            ->description('Standard Operating Procedure (SOP) - Network Operation Center.')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label("Add New SOP")
                    ->icon('phosphor-plus-circle-duotone')
                    ->visible(fn() => auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4) ? true : false),
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
            'index' => Pages\ListSops::route('/'),
            'create' => Pages\CreateSop::route('/create'),
            // 'edit' => Pages\EditSop::route('/{record}/edit'),
            'view' => Pages\ViewSop::route('/{record}'),
        ];
    }
}
