<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignerPartnerApplicationResource\Pages;
use App\Models\DesignerPartnerApplication;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DesignerPartnerApplicationResource extends Resource
{
    protected static ?string $model = DesignerPartnerApplication::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 42;

    protected static ?string $modelLabel = '设计师合作申请';

    protected static ?string $pluralModelLabel = '设计师合作申请';

    protected static ?string $navigationLabel = '设计师合作申请';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('申请详情')
                    ->schema([
                        Forms\Components\TextInput::make('name_or_company')
                            ->label('姓名或公司')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('country_or_region')
                            ->label('国家/地区')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email')
                            ->label('电子邮箱')
                            ->email()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('website')
                            ->label('网站')
                            ->url()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('portfolio_links')
                            ->label('作品集链接')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('design_field')
                            ->label('主要设计领域')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('expected_products_finishes')
                            ->label('预计产品与工艺')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('审核信息')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options([
                                'new' => '新申请',
                                'reviewing' => '审核中',
                                'approved' => '已通过',
                                'rejected' => '已拒绝',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('handled_at')
                            ->label('处理时间')
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('内部备注')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('元数据')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP 地址')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('浏览器标识')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('提交时间')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_or_company')
                    ->label('姓名或公司')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('电子邮箱')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country_or_region')
                    ->label('国家/地区')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('design_field')
                    ->label('设计领域')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => '新申请',
                        'reviewing' => '审核中',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'reviewing' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('提交时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('handled_at')
                    ->label('处理时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'new' => '新申请',
                        'reviewing' => '审核中',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                    ]),
                Tables\Filters\Filter::make('handled_at')
                    ->query(fn ($query) => $query->whereNull('handled_at'))
                    ->label('待处理')
                    ->toggle(),
            ])
            ->actions([
                Actions\EditAction::make()->label('编辑'),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->label('删除所选'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesignerPartnerApplications::route('/'),
            'edit' => Pages\EditDesignerPartnerApplication::route('/{record}/edit'),
        ];
    }
}
