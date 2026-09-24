<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShowcaseCategoryResource\Pages;
use App\Models\ShowcaseCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ShowcaseCategoryResource extends Resource
{
    protected static ?string $model = ShowcaseCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 46;

    protected static ?string $modelLabel = 'Showcase category';

    protected static ?string $pluralModelLabel = 'Showcase categories';

    protected static ?string $navigationLabel = 'Showcase categories';

    protected static string|\UnitEnum|null $navigationGroup = '博客管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort order')
                    ->numeric()
                    ->integer()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('showcases_count')
                    ->counts('showcases')
                    ->label('Showcases'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShowcaseCategories::route('/'),
            'create' => Pages\CreateShowcaseCategory::route('/create'),
            'edit' => Pages\EditShowcaseCategory::route('/{record}/edit'),
        ];
    }
}
