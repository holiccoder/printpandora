<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = '订单';

    protected static ?string $pluralModelLabel = '订单';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('订单状态')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->required()
                            ->options([
                                'pending' => '待付款',
                                'confirmed' => '已确认',
                                'processing' => '处理中',
                                'shipped' => '已发货',
                                'delivered' => '已送达',
                                'cancelled' => '已取消',
                            ]),
                        Forms\Components\Select::make('shipping_method')
                            ->label('运输方式')
                            ->options([
                                'standard' => '标准运输',
                                'dhl_express' => '快速运输（DHL Express）',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('shipping_carrier')
                            ->label('承运商')
                            ->disabled(),
                        Forms\Components\TextInput::make('shipping_fee')
                            ->label('运费')
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('shipping_weight_grams')
                            ->label('包裹重量（克）')
                            ->numeric()
                            ->integer()
                            ->minValue(1),
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('快递追踪号')
                            ->helperText('可在此填写或同步 4PX、DHL 快递追踪号。'),
                        Forms\Components\TextInput::make('tracking_url')
                            ->label('快递查询 URL')
                            ->url(),
                        Forms\Components\TextInput::make('fourpx_status')
                            ->label('4PX 状态')
                            ->disabled(),
                        Forms\Components\Textarea::make('notes')
                            ->label('备注')
                            ->columnSpanFull(),
                    ]),
                Section::make('客户信息')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('客户姓名')
                            ->required(),
                        Forms\Components\TextInput::make('customer_email')
                            ->label('电子邮箱')
                            ->required()
                            ->email(),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('联系电话'),
                        Forms\Components\TextInput::make('shipping_address')
                            ->label('收件地址')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('shipping_city')
                            ->label('城市')
                            ->required(),
                        Forms\Components\TextInput::make('shipping_state')
                            ->label('州/省'),
                        Forms\Components\TextInput::make('shipping_zip')
                            ->label('邮政编码')
                            ->required(),
                        Forms\Components\TextInput::make('shipping_country')
                            ->label('国家')
                            ->default('US'),
                    ])->columns(2),
                Section::make('订单商品')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('商品明细')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->disabled()
                                    ->label('产品'),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('数量')
                                    ->disabled(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('单价')
                                    ->disabled()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('小计')
                                    ->disabled()
                                    ->prefix('$'),
                                Forms\Components\KeyValue::make('options')
                                    ->label('选项')
                                    ->disabled()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('订单号')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->label('客户姓名')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_email')->label('电子邮箱')->searchable(),
                Tables\Columns\TextColumn::make('total')->label('订单总计')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('shipping_carrier')->label('承运商')->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待付款',
                        'confirmed' => '已确认',
                        'processing' => '处理中',
                        'shipped' => '已发货',
                        'delivered' => '已送达',
                        'cancelled' => '已取消',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('商品件数'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('下单时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('订单状态')
                    ->options([
                        'pending' => '待付款',
                        'confirmed' => '已确认',
                        'processing' => '处理中',
                        'shipped' => '已发货',
                        'delivered' => '已送达',
                        'cancelled' => '已取消',
                    ]),
            ])
            ->actions([
                Actions\Action::make('addShippingTracking')
                    ->label('填写物流信息')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->modalHeading('填写物流信息')
                    ->modalSubmitActionLabel('保存并标记为已发货')
                    ->fillForm(fn (Order $record): array => [
                        'tracking_number' => $record->tracking_number,
                        'tracking_url' => $record->tracking_url,
                    ])
                    ->form([
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('快递追踪号')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tracking_url')
                            ->label('快递查询 URL')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->helperText('客户将通过此链接查询物流状态。'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'tracking_number' => $data['tracking_number'],
                            'tracking_url' => $data['tracking_url'],
                            'status' => 'shipped',
                        ]);
                    })
                    ->successNotificationTitle('物流信息已保存，订单已标记为已发货'),
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
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
