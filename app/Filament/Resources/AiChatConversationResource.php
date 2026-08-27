<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiChatConversationResource\Pages;
use App\Models\AiChatConversation;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only viewer for AI support chat conversations. Use it to review
 * answer quality and spot knowledge-base gaps (questions the assistant
 * could not answer).
 */
class AiChatConversationResource extends Resource
{
    protected static ?string $model = AiChatConversation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'AI 会话';

    protected static ?string $pluralModelLabel = 'AI 会话';

    protected static ?string $navigationLabel = 'AI 客服会话';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('会话信息')
                    ->schema([
                        TextEntry::make('id')->label('会话 ID'),
                        TextEntry::make('user.email')
                            ->label('用户')
                            ->placeholder('访客'),
                        TextEntry::make('session_id')->label('Session'),
                        TextEntry::make('created_at')->dateTime()->label('开始时间'),
                    ])
                    ->columns(2),
                Section::make('对话内容')
                    ->schema([
                        RepeatableEntry::make('messages')
                            ->label('')
                            ->schema([
                                TextEntry::make('role')
                                    ->label('角色')
                                    ->badge()
                                    ->color(fn (string $state) => $state === 'user' ? 'primary' : 'gray')
                                    ->formatStateUsing(fn (string $state) => $state === 'user' ? '客户' : 'AI'),
                                TextEntry::make('admin_content')->label('内容'),
                                TextEntry::make('admin_translation_label')
                                    ->label('Translation')
                                    ->placeholder('—'),
                                TextEntry::make('created_at')->dateTime()->label('时间'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('用户')
                    ->placeholder('访客')
                    ->searchable(),
                Tables\Columns\TextColumn::make('session_id')
                    ->label('Session')
                    ->limit(12)
                    ->searchable(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('消息数'),
                Tables\Columns\TextColumn::make('latestMessage.admin_content')
                    ->label('最近消息')
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('最后活跃'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('用户类型')
                    ->placeholder('全部')
                    ->trueLabel('已登录')
                    ->falseLabel('访客')
                    ->nullable(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiChatConversations::route('/'),
            'view' => Pages\ViewAiChatConversation::route('/{record}'),
        ];
    }
}
