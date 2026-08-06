<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Services\ProductImageService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = '产品';

    protected static ?string $pluralModelLabel = '产品';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('产品名称')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label('产品别名')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('subtitle')
                            ->label('副标题')
                            ->maxLength(255),
                        Forms\Components\Select::make('product_category_id')
                            ->label('产品分类')
                            ->relationship('category', 'name')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('启用状态')
                            ->default(true),
                    ]),

                Section::make('定价')
                    ->schema([
                        Forms\Components\TextInput::make('price_line')
                            ->label('价格说明')
                            ->maxLength(255)
                            ->placeholder('例如: From $29.99'),
                    ]),

                Section::make('描述')
                    ->schema([
                        Forms\Components\TextInput::make('description_title')
                            ->label('描述标题')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->label('描述内容'),
                        Forms\Components\TagsInput::make('bullet_points')
                            ->label('特性列表')
                            ->placeholder('添加特性项')
                            ->reorderable(),
                    ]),

                Section::make('产品选项')
                    ->schema([
                        Forms\Components\CodeEditor::make('product_options')
                            ->label('定制选项配置')
                            ->language(Language::Json)
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                            ->columnSpanFull(),
                    ]),

                Section::make('媒体')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('主图')
                            ->image()
                            ->directory('products')
                            ->imageEditor(),
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('图集')
                            ->collection('gallery')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->maxFiles(8),
                    ]),

                Section::make('SEO')
                    ->schema([
                        Forms\Components\Textarea::make('meta_description')
                            ->label('网页描述 (Meta Description)')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('主图')
                    ->square(),
                SpatieMediaLibraryImageColumn::make('gallery')
                    ->label('图集')
                    ->collection('gallery')
                    ->circular()
                    ->stacked()
                    ->limit(3),
                Tables\Columns\TextColumn::make('name')
                    ->label('产品名称')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_line')
                    ->label('价格')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('启用状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('分类')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('启用状态'),
            ])
            ->actions([
                Actions\Action::make('images')
                    ->label('Images')
                    ->icon('heroicon-o-photo')
                    ->url(fn (Product $record): string => static::getUrl('images', ['record' => $record]))
                    ->visible(fn (Product $record): bool => app(ProductImageService::class)->supportsBusinessCard($record)),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' => Pages\ManageProductImages::route('/{record}/images'),
        ];
    }
}
