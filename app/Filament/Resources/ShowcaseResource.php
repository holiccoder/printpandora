<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShowcaseResource\Pages;
use App\Models\Showcase;
use App\Services\ProductImageResolver;
use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                Forms\Components\Select::make('category_id')
                    ->label('分类')
                    ->relationship(
                        'category',
                        'name',
                        fn (Builder $query): Builder => $query
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('未分类'),
                Forms\Components\TextInput::make('image_name')
                    ->label('图片名称')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('link')
                    ->label('链接')
                    ->nullable()
                    ->maxLength(255)
                    ->helperText('此案例的可选链接。'),
                FileUpload::make('image_url')
                    ->label('Showcase image')
                    ->helperText('Upload a JPEG, PNG, or WebP image (maximum 10 MB).')
                    ->image()
                    ->acceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
                    ->maxSize(10240)
                    ->disk('public')
                    ->directory('showcases')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->required()
                    ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                        return app(ProductImageUploadService::class)->store(
                            $file,
                            $component->getDirectory() ?? 'showcases',
                            $component->getDiskName(),
                            $component->getVisibility(),
                        );
                    })
                    ->getUploadedFileUsing(function (
                        FileUpload $component,
                        string $file,
                        string|array|null $storedFileNames,
                    ): ?array {
                        if (Str::startsWith($file, ['http://', 'https://', '//', '/'])) {
                            return [
                                'name' => basename((string) parse_url($file, PHP_URL_PATH)),
                                'size' => 0,
                                'type' => 'image/*',
                                'url' => self::resolveImagePath($file),
                            ];
                        }

                        $uploadedFile = $component->getUploadedFile($file, $storedFileNames);

                        if ($uploadedFile === null) {
                            return null;
                        }

                        $uploadedFile['url'] = self::resolveImagePath($file);

                        return $uploadedFile;
                    })
                    ->deleteUploadedFileUsing(static function (string $file): void {
                        self::deleteUploadedImage($file);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('缩略图')
                    ->state(fn (Showcase $record): string => self::absoluteImageUrl(
                        (string) $record->getRawOriginal('image_url'),
                    ))
                    ->checkFileExistence(false)
                    ->imageSize(80)
                    ->square(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->badge()
                    ->placeholder('未分类')
                    ->sortable(),
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

    private static function resolveImagePath(string $image): string
    {
        $resolved = app(ProductImageResolver::class)->url($image);

        return is_string($resolved) ? $resolved : $image;
    }

    private static function absoluteImageUrl(string $image): string
    {
        $resolved = self::resolveImagePath($image);

        return Str::startsWith($resolved, ['http://', 'https://', '//'])
            ? $resolved
            : url($resolved);
    }

    private static function deleteUploadedImage(string $file): void
    {
        $file = ltrim(str_replace('\\', '/', $file), '/');
        $directory = ProductImagePolicy::ORIGINALS_DIRECTORY.'/showcases/';

        if (! Str::startsWith($file, $directory)) {
            return;
        }

        $resolver = app(ProductImageResolver::class);
        $webpPath = $resolver->derivativePath($file);
        $paths = [$file];

        if ($webpPath !== null) {
            $paths[] = $webpPath;
            $paths[] = $resolver->failureMarkerPath($webpPath);
        }

        Storage::disk('public')->delete($paths);
    }
}
