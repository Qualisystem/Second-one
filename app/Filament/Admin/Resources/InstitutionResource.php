<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InstitutionResource\Pages;
use App\Filament\Admin\Resources\InstitutionResource\RelationManagers;
use App\Models\Institution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('abbreviation')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->label("Institution name")
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('sector_id')
                    ->relationship('sector', 'label')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('abbreviation')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label("Institution name")
                    ->searchable(),
                Tables\Columns\TextColumn::make('sector.label') // Display the related sector's label
                    ->sortable(),
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
            'index' => Pages\ListInstitutions::route('/'),
            'create' => Pages\CreateInstitution::route('/create'),
            'edit' => Pages\EditInstitution::route('/{record}/edit'),
        ];
    }
}
