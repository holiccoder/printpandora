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

    protected static ?string $modelLabel = '案例';

    protected static ?string $pluralModelLabel = '案例';

    protected static ?string $navigationLabel = '案例';

    protected static string|\UnitEnum|null $navigationGroup = '博客管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('image_name')
                    ->label('图片名称')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('link')
                    ->label('链接')
                    ->nullable()
                    ->maxLength(255)
                    ->helperText('此案例的可选链接。'),
                Forms\Components\TextInput::make('image_url')
                    ->label('图片地址')
                    ->required()
                    ->maxLength(255)
                    ->helperText('请输入公开图片 URL，或类似 /images/showcases/example.webp 的路径。'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('图片')
                    ->getStateUsing(fn (Showcase $record): string => str_starts_with($record->image_url, 'http://') || str_starts_with($record->image_url, 'https://')
                        ? $record->image_url
                        : url($record->image_url))
                    ->checkFileExistence(false)
                    ->square(),
                Tables\Columns\TextColumn::make('image_name')
                    ->label('图片名称')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('link')
                    ->label('链接')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('image_url')
                    ->label('图片地址')
                    ->searchable()
                    ->limit(45)
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
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
