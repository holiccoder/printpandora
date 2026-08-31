<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignServiceRequestResource\Pages;
use App\Models\DesignServiceRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DesignServiceRequestResource extends Resource
{
    protected static ?string $model = DesignServiceRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = '设计服务申请';

    protected static ?string $pluralModelLabel = '设计服务申请';

    protected static ?string $navigationLabel = '设计服务申请';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('上传文件')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('公司 Logo')
                            ->disk('public')
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\FileUpload::make('example_paths')
                            ->label('案例文件')
                            ->multiple()
                            ->disk('public')
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Section::make('申请详情')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('电子邮箱')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('business_name')
                            ->label('公司/商家名称')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('business_card_type')
                            ->label('名片类型')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('design_service_code')
                            ->label('设计服务')
                            ->options([
                                'card_layout' => '名片版面排版 ($29)',
                                'card_design' => '名片深度设计 ($79)',
                            ])
                            ->nullable(),
                        Forms\Components\TextInput::make('design_service_fee')
                            ->hidden()
                            ->label('设计服务费 (USD)')
                            ->numeric()
                            ->step('0.01')
                            ->nullable(),
                        Forms\Components\Toggle::make('terms_accepted')
                            ->hidden()
                            ->label('同意服务条款')
                            ->required(),
                        Forms\Components\DateTimePicker::make('handled_at')
                            ->hidden()
                            ->label('处理时间')
                            ->nullable(),
                    ]),
                Section::make('名片排版信息')
                    ->schema([
                        Forms\Components\Textarea::make('card_info')
                            ->label('排版内容与要求')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('管理员备注')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('备注')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('元数据')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP 地址')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('浏览器 User Agent')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('提交时间')
                            ->disabled(),
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
                Tables\Columns\TextColumn::make('email')
                    ->label('电子邮箱')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->label('公司/商家名称')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_card_type')
                    ->label('名片类型')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('design_service_code')
                    ->label('设计服务')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'card_layout' => '名片版面排版 ($29)',
                        'card_design' => '名片深度设计 ($79)',
                        default => '—',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('design_service_fee')
                    ->label('费用')
                    ->money('USD')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('terms_accepted')
                    ->label('同意条款')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('提交时间'),
                Tables\Columns\TextColumn::make('handled_at')
                    ->dateTime()
                    ->sortable()
                    ->label('处理时间')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('terms_accepted')
                    ->label('同意服务条款'),
                Tables\Filters\Filter::make('handled_at')
                    ->query(fn ($query) => $query->whereNull('handled_at'))
                    ->label('待处理')
                    ->toggle(),
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
            'index' => Pages\ListDesignServiceRequests::route('/'),
            'edit' => Pages\EditDesignServiceRequest::route('/{record}/edit'),
        ];
    }
}
