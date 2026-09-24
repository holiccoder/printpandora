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

    protected static ?string $modelLabel = '案例分类';

    protected static ?string $pluralModelLabel = '案例分类';

    protected static ?string $navigationLabel = '案例分类';

    protected static string|\UnitEnum|null $navigationGroup = '博客管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->label('别名')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('排序')
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
                    ->label('名称')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('别名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('showcases_count')
                    ->counts('showcases')
                    ->label('案例数'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Actions\EditAction::make()->label('编辑'),
                Actions\DeleteAction::make()->label('删除'),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->label('删除所选'),
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
