<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatTemplateResource\Pages;
use App\Filament\Resources\ChatTemplateResource\RelationManagers;
use App\Models\ChatTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatTemplateResource extends Resource
{
    protected static ?string $model = ChatTemplate::class;

    protected static ?string $navigationGroup = 'Operational';
    protected static ?string $navigationIcon = 'phosphor-chat-teardrop-text-duotone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->native(false)
                    ->options([
                        'follow-up' => 'follow-up',
                        'general' => 'general',
                        'others' => 'others',
                    ]),
                Forms\Components\Textarea::make('template')
                    ->required()
                    ->autosize()
                    ->hint('Gunakan placeholder seperti {gender}, {nama_site}, {provinsi}, {time}')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
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
            'index' => Pages\ManageChatTemplates::route('/'),
        ];
    }
}
