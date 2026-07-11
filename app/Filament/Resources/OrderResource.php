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

    protected static string|\UnitEnum|null $navigationGroup = 'Shop';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
                Section::make('Customer Info')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')->required(),
                        Forms\Components\TextInput::make('customer_email')->required()->email(),
                        Forms\Components\TextInput::make('customer_phone'),
                        Forms\Components\TextInput::make('shipping_address')->required()->columnSpanFull(),
                        Forms\Components\TextInput::make('shipping_city')->required(),
                        Forms\Components\TextInput::make('shipping_state'),
                        Forms\Components\TextInput::make('shipping_zip')->required(),
                        Forms\Components\TextInput::make('shipping_country')->default('US'),
                    ])->columns(2),
                Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->disabled()
                                    ->label('Product'),
                                Forms\Components\TextInput::make('quantity')
                                    ->disabled(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->disabled()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('subtotal')
                                    ->disabled()
                                    ->prefix('$'),
                                Forms\Components\KeyValue::make('options')
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
                Tables\Columns\TextColumn::make('id')->label('Order #')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_email')->searchable(),
                Tables\Columns\TextColumn::make('total')->money('USD')->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Items'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
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
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
