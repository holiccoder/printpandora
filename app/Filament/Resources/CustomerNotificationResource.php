<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerNotificationResource\Pages;
use App\Models\User;
use App\Notifications\CustomerNotification;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class CustomerNotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = '通知管理';

    protected static ?string $modelLabel = '通知';

    protected static ?string $pluralModelLabel = '通知管理';

    protected static ?string $slug = 'notifications';

    protected static string|\UnitEnum|null $navigationGroup = '用户管理';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('notifiable_type', User::class)
            ->where('type', CustomerNotification::class)
            ->with('notifiable');
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = static::getEloquentQuery()->whereNull('read_at')->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('通知内容')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('标题')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('body')
                            ->label('内容')
                            ->required()
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('action_url')
                            ->label('跳转链接（可选）')
                            ->placeholder('/dashboard/orders')
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('发送对象')
                    ->schema([
                        Forms\Components\Toggle::make('send_to_all')
                            ->label('发送给所有客户')
                            ->helperText('打开后会发送给当前所有客户。')
                            ->live(),
                        Forms\Components\Select::make('recipient_ids')
                            ->label('选择客户')
                            ->options(static::recipientOptions())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => ! (bool) $get('send_to_all'))
                            ->hidden(fn (Get $get): bool => (bool) $get('send_to_all'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('通知内容')
                    ->schema([
                        TextEntry::make('data.title')
                            ->label('标题'),
                        TextEntry::make('data.category')
                            ->label('类型')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state === CustomerNotification::CATEGORY_ADMIN ? '管理员通知' : '系统通知')
                            ->color(fn (?string $state): string => $state === CustomerNotification::CATEGORY_ADMIN ? 'warning' : 'primary'),
                        TextEntry::make('data.body')
                            ->label('内容')
                            ->columnSpanFull(),
                        TextEntry::make('data.action_url')
                            ->label('跳转链接')
                            ->placeholder('无')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('接收信息')
                    ->schema([
                        TextEntry::make('notifiable.name')
                            ->label('客户'),
                        TextEntry::make('notifiable.email')
                            ->label('邮箱'),
                        TextEntry::make('read_at')
                            ->label('读取时间')
                            ->dateTime()
                            ->placeholder('未读'),
                        TextEntry::make('created_at')
                            ->label('发送时间')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data.title')
                    ->label('标题')
                    ->searchable(query: static function (Builder $query, string $search): Builder {
                        $like = '%'.addcslashes($search, '%_\\').'%';

                        return $query->where('data->title', 'like', $like);
                    })
                    ->limit(45),
                Tables\Columns\TextColumn::make('data.category')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === CustomerNotification::CATEGORY_ADMIN ? '管理员通知' : '系统通知')
                    ->color(fn (?string $state): array => $state === CustomerNotification::CATEGORY_ADMIN ? Color::Orange : Color::Blue),
                Tables\Columns\TextColumn::make('notifiable.name')
                    ->label('客户')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notifiable.email')
                    ->label('邮箱')
                    ->searchable(),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('已读')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('发送时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('通知类型')
                    ->options([
                        CustomerNotification::CATEGORY_SYSTEM => '系统通知',
                        CustomerNotification::CATEGORY_ADMIN => '管理员通知',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('data->category', $data['value'])
                        : $query),
                Tables\Filters\TernaryFilter::make('read_at')
                    ->label('读取状态')
                    ->nullable(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->label('删除所选'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    protected static function recipientOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user): array => [
                (string) $user->getKey() => "{$user->name} ({$user->email})",
            ])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerNotifications::route('/'),
            'create' => Pages\CreateCustomerNotification::route('/create'),
            'view' => Pages\ViewCustomerNotification::route('/{record}'),
        ];
    }
}
