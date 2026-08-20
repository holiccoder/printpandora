<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountCodeResource\Pages;
use App\Models\DiscountCode;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountCodeResource extends Resource
{
    protected static ?string $model = DiscountCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = '折扣码';

    protected static ?string $pluralModelLabel = '折扣码';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('折扣码信息')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('代码')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim($state ?? ''))),
                        Forms\Components\Select::make('type')
                            ->label('类型')
                            ->required()
                            ->options([
                                'percent' => '百分比',
                                'fixed' => '固定金额',
                            ])
                            ->default('percent'),
                        Forms\Components\TextInput::make('value')
                            ->label('面值')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->helperText('百分比类型的值必须在 0 到 100 之间。'),
                        Forms\Components\TextInput::make('minimum_subtotal')
                            ->label('最低起用金额')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('$'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('启用状态')
                            ->default(true),
                    ])->columns(2),
                Section::make('可用时间及次数限制')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('开始时间'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('结束时间'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('最大使用总次数')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('留空表示无限制。'),
                        Forms\Components\TextInput::make('max_uses_per_customer')
                            ->label('每人最大使用次数')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('根据结账时填写的邮箱统计。'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('代码')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'percent' => '百分比',
                        'fixed' => '固定金额',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('面值')
                    ->formatStateUsing(fn ($state, DiscountCode $record): string => $record->type === 'percent'
                        ? $state.'%'
                        : '$'.$state),
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('已用次数')
                    ->formatStateUsing(fn ($state, DiscountCode $record): string => $record->max_uses
                        ? "{$state}/{$record->max_uses}"
                        : (string) $state),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('启用状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('开始时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('结束时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('启用状态'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscountCodes::route('/'),
            'create' => Pages\CreateDiscountCode::route('/create'),
            'edit' => Pages\EditDiscountCode::route('/{record}/edit'),
        ];
    }
}
