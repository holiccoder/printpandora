<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\Media;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $modelLabel = '媒体文件';

    protected static ?string $pluralModelLabel = '媒体文件';

    protected static ?string $navigationLabel = '媒体库';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('媒体详情')
                    ->schema([
                        Placeholder::make('preview')
                            ->label('图片预览')
                            ->content(fn (?Media $record): HtmlString => $record
                                ? new HtmlString("<img src='{$record->getUrl()}' style='max-height: 200px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);' />")
                                : new HtmlString("<span class='text-gray-400'>新建时上传图片</span>")
                            ),

                        TextInput::make('name')
                            ->label('媒体名称')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('collection_name')
                            ->label('集合名称 (Collection)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('例如: product-galleries, product-gallery-overrides, product-featured-overrides')
                            ->live(),

                        Select::make('model_type')
                            ->label('关联模型类型')
                            ->options([
                                Product::class => '产品 (Product)',
                            ])
                            ->required()
                            ->live(),

                        Select::make('model_id')
                            ->label('关联实例')
                            ->options(function (Get $get) {
                                $modelClass = $get('model_type');
                                if (! $modelClass || ! class_exists($modelClass)) {
                                    return [];
                                }

                                return $modelClass::query()->pluck('name', 'id')->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        FileUpload::make('file')
                            ->label('上传图片')
                            ->image()
                            ->disk('public')
                            ->directory('temp-uploads')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->required(fn (string $operation): bool => $operation === 'create'),

                        KeyValue::make('custom_properties')
                            ->label('自定义属性')
                            ->keyLabel('属性名')
                            ->valueLabel('属性值'),

                        TextInput::make('order_column')
                            ->label('排序权重')
                            ->numeric()
                            ->default(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('预览')
                    ->state(fn (Media $record): string => $record->getUrl())
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label('媒体名称')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('文件名')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('collection_name')
                    ->label('集合名称')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model_type')
                    ->label('关联模型')
                    ->state(function (Media $record): string {
                        $model = $record->model;
                        if (! $model) {
                            return '无';
                        }
                        $className = class_basename($model);
                        $name = $model->name ?? $model->title ?? $record->model_id;

                        return "{$className} (#{$record->model_id}): {$name}";
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('size')
                    ->label('大小')
                    ->state(fn (Media $record): string => number_format($record->size / 1024, 2).' KB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('MIME类型')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collection_name')
                    ->label('集合名称')
                    ->options([
                        'product-galleries' => '默认图库',
                        'product-gallery-overrides' => '图库覆盖',
                        'product-featured-overrides' => '主图覆盖',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make()->label('编辑'),
                Actions\DeleteAction::make()->label('删除'),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->label('删除所选'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
