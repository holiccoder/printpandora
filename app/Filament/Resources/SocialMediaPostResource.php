<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaPostResource\Pages;
use App\Models\SocialMediaPost;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('撰写社交媒体内容')
                    ->description('起草并规划您的社交平台（Facebook、Pinterest、Instagram、X、YouTube、LinkedIn）贴文。')
                    ->schema([
                        TextInput::make('title')
                            ->label('内部标题')
                            ->placeholder('仅供内部记录或部分平台（如 YouTube 标题）使用')
                            ->maxLength(255),

                        Textarea::make('content')
                            ->label('正文/配文')
                            ->required()
                            ->rows(5)
                            ->maxLength(5000),

                        CheckboxList::make('platforms')
                            ->label('发布平台')
                            ->options([
                                'facebook' => 'Facebook',
                                'pinterest' => 'Pinterest',
                                'instagram' => 'Instagram',
                                'x' => 'X (Twitter)',
                                'youtube' => 'YouTube',
                                'linkedin' => 'LinkedIn',
                            ])
                            ->required()
                            ->columns(3),

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
                            ->label('定时发送时间')
                            ->placeholder('选择计划发布的时间')
                            ->native(false),

                        FileUpload::make('media_urls')
                            ->label('媒体附件')
                            ->multiple()
                            ->disk('public')
                            ->directory('social-media')
                            ->visibility('public')
                            ->fetchFileInformation(false),

                        DateTimePicker::make('published_at')
                            ->label('实际发布时间')
                            ->disabled()
                            ->placeholder('系统自动填充实际发送时间'),
                    ])
                    ->columns(2),
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
                    ->label('配文')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
