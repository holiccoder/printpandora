<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $modelLabel = '工单';

    protected static ?string $pluralModelLabel = '工单';

    protected static ?string $navigationLabel = '工单管理';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('工单详情')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->required()
                            ->options([
                                'open' => '开启',
                                'in_progress' => '处理中',
                                'closed' => '关闭',
                            ]),
                        Forms\Components\Select::make('priority')
                            ->label('优先级')
                            ->required()
                            ->options([
                                'low' => '低',
                                'medium' => '中',
                                'high' => '高',
                            ]),
                    ]),
                Section::make('详细信息')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('主题')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('user.name')
                            ->label('用户名')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.email')
                            ->label('电子邮箱')
                            ->disabled(),
                        Forms\Components\Textarea::make('reply_message')
                            ->label('追加回复内容')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('工单号')->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('主题')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('user.name')->label('用户名')->searchable()->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('状态')
                    ->options([
                        'open' => '开启',
                        'in_progress' => '处理中',
                        'closed' => '关闭',
                    ])
                    ->sortable(),
                Tables\Columns\SelectColumn::make('priority')
                    ->label('优先级')
                    ->options([
                        'low' => '低',
                        'medium' => '中',
                        'high' => '高',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('replies_count')->counts('replies')->label('回复数'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'open' => '开启',
                        'in_progress' => '处理中',
                        'closed' => '关闭',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('优先级')
                    ->options([
                        'low' => '低',
                        'medium' => '中',
                        'high' => '高',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
