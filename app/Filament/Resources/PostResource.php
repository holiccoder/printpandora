<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = '文章';

    protected static ?string $pluralModelLabel = '文章';

    protected static string|\UnitEnum|null $navigationGroup = '博客管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('文章详情')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('标题')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label('别名')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('category_id')
                            ->label('文章分类')
                            ->relationship('category', 'name')
                            ->required(),
                        Forms\Components\Select::make('admin_id')
                            ->label('作者')
                            ->relationship('author', 'name')
                            ->required(),
                        FileUpload::make('featured_image')
                            ->label('封面图片')
                            ->image()
                            ->acceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
                            ->maxSize(10240)
                            ->disk('public')
                            ->directory('blog')
                            ->visibility('public')
                            ->fetchFileInformation(false)
                            ->imageEditor()
                            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                                return app(ProductImageUploadService::class)->store(
                                    $file,
                                    $component->getDirectory() ?? 'blog',
                                    $component->getDiskName(),
                                    $component->getVisibility(),
                                );
                            }),
                        RichEditor::make('body')
                            ->label('正文')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('blog')
                            ->fileAttachmentsVisibility('public')
                            ->fileAttachmentsAcceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
                            ->fileAttachmentsMaxSize(10240)
                            ->saveUploadedFileAttachmentUsing(
                                fn (TemporaryUploadedFile $file): string => app(ProductImageUploadService::class)->store(
                                    $file,
                                    'blog',
                                    'public',
                                    'public',
                                ),
                            )
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_published')
                            ->label('是否发布'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('发布时间'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('封面图片')
                    ->square(),
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('作者')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('发布状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('分类'),
                Tables\Filters\SelectFilter::make('author')
                    ->relationship('author', 'name')
                    ->label('作者'),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('发布状态'),
            ])
            ->actions([
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
