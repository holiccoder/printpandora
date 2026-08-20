<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages;
use App\Models\ProductCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = '产品分类';

    protected static ?string $pluralModelLabel = '产品分类';

    protected static ?string $navigationLabel = '产品分类';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('分类名称')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->label('网址别名')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('parent_id')
                    ->label('父级分类')
                    ->options(fn (?ProductCategory $record): array => static::categoryOptions($record))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('顶级分类')
                    ->helperText('选择父级分类以创建嵌套分类。为避免循环，不能选择当前分类及其子分类。'),
            ]);
    }

    /**
     * Build hierarchical labels for category selectors.
     *
     * @return array<int, string>
     */
    public static function categoryOptions(?ProductCategory $record = null): array
    {
        $categories = ProductCategory::query()
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $excludedIds = $record?->descendantIds() ?? [];

        if ($record?->exists) {
            $excludedIds[] = (int) $record->getKey();
        }

        $paths = [];
        $pathFor = function (int $id, array $visiting = []) use (&$pathFor, &$paths, $categories): string {
            if (isset($paths[$id])) {
                return $paths[$id];
            }

            $category = $categories->get($id);

            if (! $category) {
                return '';
            }

            if (isset($visiting[$id])) {
                return $category->name;
            }

            $visiting[$id] = true;
            $parentPath = $category->parent_id
                ? $pathFor((int) $category->parent_id, $visiting)
                : '';

            return $paths[$id] = $parentPath !== ''
                ? "{$parentPath} / {$category->name}"
                : $category->name;
        };

        return $categories
            ->reject(fn (ProductCategory $category): bool => in_array((int) $category->getKey(), $excludedIds, true))
            ->mapWithKeys(fn (ProductCategory $category): array => [
                (int) $category->getKey() => $pathFor((int) $category->getKey()),
            ])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('分类')
                    ->state(fn (ProductCategory $record): string => $record->hierarchyPath())
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('网址别名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('子分类数'),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('产品数'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make()->label('编辑'),
                DeleteAction::make()->label('删除'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('删除所选'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
