<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductDesignRequest;
use App\Services\OrderFileService;
use App\Support\OrderOptionFormatter;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class OrderResource extends Resource
{
    public static function fileAction(): Actions\Action
    {
        return Actions\Action::make('files')
            ->label(fn (Order $record): string => match ($record->status) {
                Order::STATUS_PENDING_REVIEW => '文件待审核',
                Order::STATUS_NEEDS_REUPLOAD => '文件需重传',
                Order::STATUS_PENDING_CONFIRMATION => '文件待确认',
                Order::STATUS_CONFIRMED => '文件已确认',
                default => '查看文件',
            })
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->modalHeading(fn (Order $record): string => "订单 #{$record->id} 的文件")
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('关闭')
            ->registerModalActions([
                Actions\Action::make('upload')
                    ->label('上传文件')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('上传文件')
                    ->modalSubmitActionLabel('上传')
                    ->form([
                        Forms\Components\FileUpload::make('files')
                            ->label('文件')
                            ->multiple()
                            ->required()
                            ->storeFiles(false)
                            ->maxFiles(20)
                            ->maxSize(76800)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/tiff',
                                'application/pdf',
                                'image/svg+xml',
                                'application/postscript',
                                'application/illustrator',
                                'image/vnd.adobe.photoshop',
                            ])
                            ->helperText('支持 JPG、PNG、WEBP、PDF、SVG、AI、EPS、PSD、TIFF；每个文件最大 75 MB。'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $admin = auth('admin')->user();
                        app(OrderFileService::class)->upload(
                            $record,
                            (array) ($data['files'] ?? []),
                            'admin',
                            $admin,
                        );

                        app(OrderFileService::class)->submitForConfirmation($record, $admin);

                        Notification::make()
                            ->success()
                            ->title('文件已上传，订单已进入待客户确认')
                            ->send();
                    }),
                Actions\Action::make('confirmForCustomer')
                    ->label('替客户确认')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('替客户确认')
                    ->modalDescription('确认后订单状态将变为已确认，并进入后续生产流程。')
                    ->modalSubmitActionLabel('确认')
                    ->modalCancelActionLabel('取消')
                    ->action(function (Order $record): void {
                        $admin = auth('admin')->user();
                        abort_unless($admin instanceof Admin, 403);

                        app(OrderFileService::class)->confirmForAdmin($record, $admin);

                        Notification::make()
                            ->success()
                            ->title('订单已替客户确认')
                            ->send();
                    }),
                Actions\Action::make('rejectReview')
                    ->label('审核不通过')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalHeading('审核不通过')
                    ->modalSubmitActionLabel('提交')
                    ->modalCancelActionLabel('取消')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('审核不通过的理由')
                            ->required()
                            ->maxLength(2000)
                            ->rows(4),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $admin = auth('admin')->user();
                        abort_unless($admin instanceof Admin, 403);

                        app(OrderFileService::class)->rejectForReupload(
                            $record,
                            $admin,
                            (string) ($data['reason'] ?? ''),
                        );

                        Notification::make()
                            ->success()
                            ->title('已要求客户重新上传文件')
                            ->send();
                    }),
                Actions\Action::make('deleteFile')
                    ->label('删除文件')
                    ->requiresConfirmation()
                    ->modalHeading('删除文件')
                    ->modalDescription('该文件将被永久删除，且无法恢复。')
                    ->modalSubmitActionLabel('确认删除')
                    ->modalCancelActionLabel('取消')
                    ->color('danger')
                    ->action(function (Order $record, array $arguments): void {
                        app(OrderFileService::class)->delete(
                            $record,
                            (string) ($arguments['file'] ?? ''),
                            auth('admin')->user(),
                        );

                        Notification::make()
                            ->success()
                            ->title('文件已删除')
                            ->send();
                    }),
            ])
            ->modalContent(fn (Actions\Action $action, Order $record): View => view(
                'filament.pages.order-files-modal',
                [
                    'action' => $action,
                    'order' => $record,
                    'files' => app(OrderFileService::class)->forOrder($record, 'admin'),
                ],
            ));
    }

    /**
     * Return design requests explicitly attached to the order, plus older
     * product-page submissions that predate the order association.
     *
     * @return Collection<int, ProductDesignRequest>
     */
    public static function designRequestsFor(Order $order): Collection
    {
        $order->loadMissing('items');

        $requests = $order->productDesignRequests()
            ->latest()
            ->get();
        $productIds = $order->items
            ->pluck('product_id')
            ->map(static fn (mixed $productId): int => (int) $productId)
            ->filter(static fn (int $productId): bool => $productId > 0)
            ->unique()
            ->values();
        $email = trim((string) $order->customer_email);

        if ($productIds->isEmpty() || $email === '') {
            return $requests;
        }

        $legacyRequests = ProductDesignRequest::query()
            ->whereNull('order_id')
            ->where('desgin->email', $email)
            ->whereIn('desgin->product_id', $productIds->all())
            ->get();

        return $requests
            ->merge($legacyRequests)
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Return every file submitted with an order, including all example files.
     * Product-page submissions are included even when they predate the order
     * association, matching the existing design-request lookup.
     *
     * @return array<int, array{
     *     id: string,
     *     label: string,
     *     filename: string,
     *     url: string|null,
     *     source: string,
     *     available: bool
     * }>
     */
    public static function uploadedFilesFor(Order $order): array
    {
        $files = [];

        foreach (static::designRequestsFor($order) as $designRequest) {
            $payload = $designRequest->getAttribute('desgin');

            if (! is_array($payload)) {
                continue;
            }

            $requestId = (int) $designRequest->getKey();
            $mode = (string) data_get($payload, 'mode', 'upload');

            self::addUploadedFiles(
                $files,
                "product-design-{$requestId}-design",
                data_get($payload, 'design_path'),
                $mode === 'canva' ? 'Canva 设计稿' : '设计文件',
                '产品设计文件',
            );
            self::addUploadedFiles(
                $files,
                "product-design-{$requestId}-logo",
                data_get($payload, 'logo_path'),
                '公司标志',
                '产品设计文件',
            );

            foreach ((array) data_get($payload, 'example_paths', []) as $index => $path) {
                self::addUploadedFile(
                    $files,
                    "product-design-{$requestId}-example-{$index}",
                    $path,
                    '示例 '.((int) $index + 1),
                    '产品设计文件',
                );
            }
        }

        $order->loadMissing('designServiceRequests');

        foreach ($order->designServiceRequests as $designRequest) {
            $requestId = (int) $designRequest->getKey();

            self::addUploadedFile(
                $files,
                "design-service-{$requestId}-logo",
                $designRequest->getAttribute('logo_path'),
                '公司标志',
                '设计服务',
            );

            foreach ((array) $designRequest->getAttribute('example_paths') as $index => $path) {
                self::addUploadedFile(
                    $files,
                    "design-service-{$requestId}-example-{$index}",
                    $path,
                    '示例 '.((int) $index + 1),
                    '设计服务',
                );
            }
        }

        return $files;
    }

    /**
     * @param  array<int, array{
     *     id: string,
     *     label: string,
     *     filename: string,
     *     url: string|null,
     *     source: string,
     *     available: bool
     * }>  $files
     */
    private static function addUploadedFiles(
        array &$files,
        string $idPrefix,
        mixed $paths,
        string $label,
        string $source,
    ): void {
        $paths = array_values((array) $paths);

        foreach ($paths as $index => $path) {
            self::addUploadedFile(
                $files,
                "{$idPrefix}-{$index}",
                $path,
                count($paths) > 1 ? $label.' '.($index + 1) : $label,
                $source,
            );
        }
    }

    /**
     * @param  array<int, array{
     *     id: string,
     *     label: string,
     *     filename: string,
     *     url: string|null,
     *     source: string,
     *     available: bool
     * }>  $files
     */
    private static function addUploadedFile(
        array &$files,
        string $id,
        mixed $path,
        string $label,
        string $source,
    ): void {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $path = trim($path);
        $available = Storage::disk('public')->exists($path);

        $files[] = [
            'id' => $id,
            'label' => $label,
            'filename' => basename($path),
            'url' => $available ? Storage::disk('public')->url($path) : null,
            'source' => $source,
            'available' => $available,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                ViewEntry::make('order_detail')
                    ->view(
                        'filament.pages.order-detail',
                        fn (Order $record): array => [
                            'order' => $record,
                            'uploadedFiles' => static::uploadedFilesFor($record),
                        ],
                    )
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]);
    }

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
                            ->options(Order::statusOptions())
                            ->disableOptionWhen(
                                fn (string $value, Order $record): bool => match ($value) {
                                    Order::STATUS_CONFIRMED => $record->status !== Order::STATUS_CONFIRMED,
                                    Order::STATUS_PRODUCTION => ! in_array($record->status, [
                                        Order::STATUS_CONFIRMED,
                                        Order::STATUS_PRODUCTION,
                                        Order::STATUS_SHIPPED,
                                    ], true),
                                    default => false,
                                },
                            ),
                        Forms\Components\Select::make('shipping_method')
                            ->label('运输方式')
                            ->options([
                                'standard' => '标准运输',
                                'dhl_express' => 'DHL 快速运输',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('shipping_carrier')
                            ->label('承运商')
                            ->disabled(),
                        Forms\Components\TextInput::make('shipping_fee')
                            ->label('运费')
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('shipped_at')
                            ->label('发货时间')
                            ->seconds(false)
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
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with('items.product'),
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('订单号')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->label('客户姓名')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_email')->label('电子邮箱')->searchable(),
                Tables\Columns\TextColumn::make('items_summary')
                    ->label('商品及选项')
                    ->state(
                        fn (Order $record): array => $record->items
                            ->map(function (OrderItem $item): string {
                                $productName = $item->product?->name ?? '产品不可用';
                                $options = OrderOptionFormatter::options($item->options);

                                return $options === ''
                                    ? $productName
                                    : $productName.' — '.$options;
                            })
                            ->all(),
                    )
                    ->listWithLineBreaks()
                    ->wrap(),
                Tables\Columns\TextColumn::make('total')->label('订单总计')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('shipping_weight_grams')
                    ->label('订单重量（克）')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('shipping_carrier')->label('承运商')->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')->label('快递单号')->searchable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('状态')
                    ->options(Order::statusOptions())
                    ->selectablePlaceholder(false)
                    ->disableOptionWhen(
                        fn (string $value, Order $record): bool => match ($value) {
                            Order::STATUS_CONFIRMED => $record->status !== Order::STATUS_CONFIRMED,
                            Order::STATUS_PRODUCTION => ! in_array($record->status, [
                                Order::STATUS_CONFIRMED,
                                Order::STATUS_PRODUCTION,
                                Order::STATUS_SHIPPED,
                            ], true),
                            default => false,
                        },
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('商品件数'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('下单时间'),
                Tables\Columns\TextColumn::make('shipped_at')->dateTime()->sortable()->label('发货时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('订单状态')
                    ->options(Order::statusOptions())
                    ->multiple(),
                Tables\Filters\Filter::make('order_date')
                    ->label('下单时间')
                    ->form([
                        Forms\Components\DateTimePicker::make('from')
                            ->label('开始时间')
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('until')
                            ->label('结束时间')
                            ->seconds(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): void {
                        if (filled($data['from'] ?? null)) {
                            $query->where('created_at', '>=', $data['from']);
                        }

                        if (filled($data['until'] ?? null)) {
                            $query->where('created_at', '<=', $data['until']);
                        }
                    })
                    ->indicateUsing(static function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = '下单开始：'.$data['from'];
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = '下单结束：'.$data['until'];
                        }

                        return $indicators;
                    }),
                Tables\Filters\Filter::make('shipped_date')
                    ->label('发货时间')
                    ->form([
                        Forms\Components\DateTimePicker::make('from')
                            ->label('开始时间')
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('until')
                            ->label('结束时间')
                            ->seconds(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): void {
                        if (filled($data['from'] ?? null)) {
                            $query->where('shipped_at', '>=', $data['from']);
                        }

                        if (filled($data['until'] ?? null)) {
                            $query->where('shipped_at', '<=', $data['until']);
                        }
                    })
                    ->indicateUsing(static function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = '发货开始：'.$data['from'];
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = '发货结束：'.$data['until'];
                        }

                        return $indicators;
                    }),
                Tables\Filters\Filter::make('recipient')
                    ->label('收件人姓名或邮箱')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('姓名或邮箱')
                            ->placeholder('输入收件人姓名或邮箱')
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = trim((string) ($data['value'] ?? ''));

                        if ($value === '') {
                            return;
                        }

                        $like = '%'.addcslashes($value, '%_\\').'%';

                        $query->where(function (Builder $query) use ($like): void {
                            $query
                                ->where('customer_name', 'like', $like)
                                ->orWhere('customer_email', 'like', $like);
                        });
                    })
                    ->indicateUsing(static function (array $data): array {
                        return filled($data['value'] ?? null)
                            ? ['收件人：'.$data['value']]
                            : [];
                    }),
                Tables\Filters\Filter::make('keyword')
                    ->label('关键词')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('订单号或订单名称')
                            ->placeholder('输入订单号或商品名称')
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = trim((string) ($data['value'] ?? ''));

                        if ($value === '') {
                            return;
                        }

                        $like = '%'.addcslashes(ltrim($value, '#'), '%_\\').'%';

                        $query->where(function (Builder $query) use ($like): void {
                            $query
                                ->where('orders.id', 'like', $like)
                                ->orWhereHas('items.product', function (Builder $query) use ($like): void {
                                    $query->where('products.name', 'like', $like);
                                });
                        });
                    })
                    ->indicateUsing(static function (array $data): array {
                        return filled($data['value'] ?? null)
                            ? ['关键词：'.$data['value']]
                            : [];
                    }),
                Tables\Filters\Filter::make('shipping_tracking')
                    ->label('物流 / 快递单号')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('物流或快递单号')
                            ->placeholder('输入物流公司或快递单号')
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = trim((string) ($data['value'] ?? ''));

                        if ($value === '') {
                            return;
                        }

                        $like = '%'.addcslashes($value, '%_\\').'%';

                        $query->where(function (Builder $query) use ($like): void {
                            foreach ([
                                'shipping_carrier',
                                'shipping_method',
                                'tracking_number',
                                'fourpx_consignment_no',
                                'fourpx_tracking_number',
                                'fourpx_logistics_channel_no',
                            ] as $column) {
                                $method = $column === 'shipping_carrier' ? 'where' : 'orWhere';
                                $query->{$method}($column, 'like', $like);
                            }
                        });
                    })
                    ->indicateUsing(static function (array $data): array {
                        return filled($data['value'] ?? null)
                            ? ['物流 / 快递单号：'.$data['value']]
                            : [];
                    }),
                Tables\Filters\Filter::make('order_number')
                    ->label('订单号')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('订单号')
                            ->numeric()
                            ->placeholder('输入订单号'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = ltrim(trim((string) ($data['value'] ?? '')), '#');

                        if ($value !== '') {
                            $query->whereKey($value);
                        }
                    })
                    ->indicateUsing(static function (array $data): array {
                        return filled($data['value'] ?? null)
                            ? ['订单号：'.$data['value']]
                            : [];
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->filtersTriggerAction(function (Actions\Action $action): Actions\Action {
                return $action
                    ->button()
                    ->label('筛选订单')
                    ->icon('heroicon-o-funnel');
            })
            ->actions([
                Actions\ViewAction::make()->label('查看'),
                static::fileAction(),
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
                            'status' => Order::STATUS_SHIPPED,
                        ]);
                    })
                    ->successNotificationTitle('物流信息已保存，订单已标记为已发货'),
                Actions\EditAction::make()->label('编辑'),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->label('批量删除'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
