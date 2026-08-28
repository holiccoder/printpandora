<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaPostResource\Pages;
use App\Models\SocialMediaPost;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SocialMediaPostResource extends Resource
{
    protected static ?string $model = SocialMediaPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static ?string $modelLabel = '社交媒体发布';

    protected static ?string $pluralModelLabel = '社交媒体发布';

    protected static string|\UnitEnum|null $navigationGroup = '营销管理';

    /**
     * @return array<string, array{label: string, description: string}>
     */
    private static function platformDefinitions(): array
    {
        return [
            'facebook' => [
                'label' => 'Facebook',
                'description' => '为 Facebook 设置独立文案；留空时使用基础设置中的通用内容。',
            ],
            'pinterest' => [
                'label' => 'Pinterest',
                'description' => '为 Pinterest 设置独立文案；图片和链接可在基础设置中统一管理。',
            ],
            'instagram' => [
                'label' => 'Instagram',
                'description' => '为 Instagram 设置独立文案，可在文本中保留话题标签和换行。',
            ],
            'x' => [
                'label' => 'X (Twitter)',
                'description' => 'X 的文案建议控制在 280 个字符以内；留空时使用通用内容。',
            ],
            'youtube' => [
                'label' => 'YouTube',
                'description' => '为 YouTube 设置视频说明文案；视频媒体可在基础设置中上传。',
            ],
            'linkedin' => [
                'label' => 'LinkedIn',
                'description' => '为 LinkedIn 设置独立文案，可保留段落和链接。',
            ],
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $platformTabs = [];

        foreach (self::platformDefinitions() as $platform => $definition) {
            $platformTabs[] = Tab::make($platform)
                ->label($definition['label'])
                ->schema([
                    Section::make($definition['label'].' 发布面板')
                        ->description($definition['description'])
                        ->schema([
                            Textarea::make("platform_contents.{$platform}")
                                ->label('平台专属文案')
                                ->helperText('可选。填写后仅此平台使用该文案；留空则回退到“基础设置”的通用内容。')
                                ->rows(12)
                                ->maxLength(5000)
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                ]);
        }

        return $schema
            ->schema([
                Tabs::make('社交媒体发布面板')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('general')
                            ->label('基础设置')
                            ->schema([
                                Section::make('通用发布设置')
                                    ->description('选择要发布的平台，并设置所有平台都可以使用的默认内容。')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('内部标题')
                                            ->placeholder('仅供内部记录使用')
                                            ->maxLength(255),

                                        Textarea::make('content')
                                            ->label('通用内容（默认文案）')
                                            ->helperText('建议使用纯文本；换行会保留。各平台标签页留空时会使用此内容。')
                                            ->required()
                                            ->rows(8)
                                            ->maxLength(5000)
                                            ->columnSpanFull(),

                                        CheckboxList::make('platforms')
                                            ->label('发布平台')
                                            ->options(array_combine(
                                                array_keys(self::platformDefinitions()),
                                                array_column(self::platformDefinitions(), 'label'),
                                            ))
                                            ->helperText('平台专属文案请切换到对应标签页填写。')
                                            ->required()
                                            ->columns(3)
                                            ->columnSpanFull(),

                                        Select::make('status')
                                            ->label('状态')
                                            ->options([
                                                'draft' => '草稿 (Draft)',
                                                'scheduled' => '已定时 (Scheduled)',
                                                'published' => '已发布 (Published)',
                                                'failed' => '发送失败 (Failed)',
                                            ])
                                            ->required()
                                            ->default('draft'),

                                        DateTimePicker::make('scheduled_at')
                                            ->label('定时发布时间')
                                            ->placeholder('选择计划发布的时间')
                                            ->native(false),

                                        FileUpload::make('media_urls')
                                            ->label('媒体附件')
                                            ->multiple()
                                            ->disk('public')
                                            ->directory('social-media')
                                            ->visibility('public')
                                            ->fetchFileInformation(false)
                                            ->columnSpanFull(),

                                        DateTimePicker::make('published_at')
                                            ->label('实际发布时间')
                                            ->disabled()
                                            ->placeholder('系统自动填充实际发布时间'),
                                    ])
                                    ->columns(2),
                            ]),
                        ...$platformTabs,
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->placeholder('无标题')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('通用文案')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('platforms')
                    ->label('平台')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'published' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '草稿',
                        'scheduled' => '已定时',
                        'published' => '已发布',
                        'failed' => '失败',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('计划发布时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('实际发布时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'draft' => '草稿',
                        'scheduled' => '已定时',
                        'published' => '已发布',
                        'failed' => '失败',
                    ]),
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
            'index' => Pages\ListSocialMediaPosts::route('/'),
            'create' => Pages\CreateSocialMediaPost::route('/create'),
            'edit' => Pages\EditSocialMediaPost::route('/{record}/edit'),
        ];
    }
}
