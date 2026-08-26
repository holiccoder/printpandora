<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShowcaseResource\Pages;
use App\Models\Showcase;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ShowcaseResource extends Resource
{
    protected static ?string $model = Showcase::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'Showcase';

    protected static ?string $pluralModelLabel = 'Showcases';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('image_name')
                    ->label('Image name')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('link')
                    ->label('Link')
                    ->nullable()
                    ->maxLength(255)
                    ->helperText('Optional link associated with this showcase.'),
                Forms\Components\TextInput::make('image_url')
                    ->label('Image URL')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Use a public URL or a path such as /images/showcases/example.webp.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->getStateUsing(fn (Showcase $record): string => str_starts_with($record->image_url, 'http://') || str_starts_with($record->image_url, 'https://')
                        ? $record->image_url
                        : url($record->image_url))
                    ->checkFileExistence(false)
                    ->square(),
                Tables\Columns\TextColumn::make('image_name')
                    ->label('Image name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('link')
                    ->label('Link')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('image_url')
                    ->label('Image URL')
                    ->searchable()
                    ->limit(45)
                    ->copyable(),
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
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShowcases::route('/'),
            'create' => Pages\CreateShowcase::route('/create'),
            'edit' => Pages\EditShowcase::route('/{record}/edit'),
        ];
    }
}
