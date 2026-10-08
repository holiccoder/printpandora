@php
    $orderStatusOptions = \App\Models\Order::statusOptions();
    $statusSteps = [
        ['key' => \App\Models\Order::STATUS_PENDING, 'description' => '等待付款', 'icon' => 'heroicon-o-credit-card'],
        ['key' => \App\Models\Order::STATUS_PENDING_REVIEW, 'description' => '等待审核', 'icon' => 'heroicon-o-magnifying-glass'],
        ['key' => \App\Models\Order::STATUS_NEEDS_REUPLOAD, 'description' => '客户需要重新上传文件', 'icon' => 'heroicon-o-arrow-up-tray'],
        ['key' => \App\Models\Order::STATUS_PENDING_CONFIRMATION, 'description' => '等待确认', 'icon' => 'heroicon-o-question-mark-circle'],
        ['key' => \App\Models\Order::STATUS_CONFIRMED, 'description' => '订单已确认', 'icon' => 'heroicon-o-shield-check'],
        ['key' => \App\Models\Order::STATUS_PRODUCTION, 'description' => '正在生产', 'icon' => 'heroicon-o-cog-6-tooth'],
        ['key' => \App\Models\Order::STATUS_SHIPPED, 'description' => '运输中', 'icon' => 'heroicon-o-truck'],
    ];
    $statusSteps = array_map(
        static fn (array $step): array => [...$step, 'label' => $orderStatusOptions[$step['key']] ?? $step['key']],
        $statusSteps,
    );
    $statusIndex = array_search($order->status, array_column($statusSteps, 'key'), true);
    $statusIndex = $statusIndex === false ? 0 : $statusIndex;
    $statusLabel = $statusSteps[$statusIndex]['label'] ?? \Illuminate\Support\Str::headline((string) $order->status);
    $progressWidth = count($statusSteps) > 1
        ? ($statusIndex / (count($statusSteps) - 1)) * 87.5
        : 0;
    $statusMessages = [
        'pending_review' => '订单已提交，正在等待管理员审核。',
        'needs_reupload' => '管理员已要求客户重新上传文件。',
        'pending_confirmation' => '订单正在等待客户或管理员确认最终信息。',
        'confirmed' => '订单信息已确认，可以进入生产。',
        'production' => '订单正在生产中。',
        'shipped' => '订单已交给承运商配送。',
    ];
    $paymentStatusLabels = [
        'paid' => '已付款',
        'pending' => '待付款',
        'failed' => '支付失败',
        'refunded' => '已退款',
        'reversed' => '已撤销',
    ];
    $paymentStatusLabel = $paymentStatusLabels[(string) $order->payment_status]
        ?? ($order->payment_status ? (string) $order->payment_status : '待付款');
    $shippingMethodLabels = [
        'standard' => '标准运输',
        'dhl_express' => 'DHL 快速运输',
    ];
    $shippingMethodLabel = $shippingMethodLabels[(string) $order->shipping_method]
        ?? ((string) $order->shipping_method ?: '未指定');
    $optionLabels = [
        'sizes' => '尺寸',
        'size' => '尺寸',
        'shape' => '形状',
        'material' => '材质',
        'corners' => '圆角',
        'corner' => '圆角',
        'thickness' => '厚度',
        'texture' => '纹理',
        'paper' => '纸张',
        'paper_finish' => '纸张表面处理',
        'finish' => '表面处理',
        'folding' => '折叠方式',
        'uv_finish' => 'UV 工艺',
        'special_finish' => '特殊工艺',
        'special_finish_on_sides' => '特殊工艺单双面',
        'hot_foil' => '烫金',
        'hot_foil_on_sides' => '烫金单双面',
        'cold_foil' => '冷烫金',
        'print_code' => '印刷代码',
        'print_code_or_magnetic_stripe' => '印刷代码或磁条',
        'print_sides' => '印刷面',
        'drill' => '打孔',
        'quantity' => '数量',
        'custom_width' => '自定义宽度',
        'custom_height' => '自定义高度',
        'width' => '宽度',
        'height' => '高度',
    ];
    $formatOptionLabel = static function (mixed $key) use ($optionLabels): string {
        $normalizedKey = strtolower(str_replace([' ', '-'], '_', (string) $key));

        return $optionLabels[$normalizedKey] ?? \Illuminate\Support\Str::headline($normalizedKey);
    };
    $money = static fn (mixed $value): string => number_format((float) $value, 2);
    $formatOptionValue = static fn (mixed $value): string =>
        \App\Support\OrderOptionFormatter::value($value);
@endphp

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600 dark:text-primary-400">
                自定义订单详情
            </p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                订单 #{{ $order->id }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                下单时间：{{ $order->created_at?->format('Y-m-d H:i') ?? '—' }}
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full bg-primary-50 px-3 py-1.5 text-sm font-semibold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
            {{ $statusLabel }}
        </span>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">01</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">订单状态</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">跟踪订单从付款到配送的进度。</p>
            </div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">当前状态：{{ $statusLabel }}</span>
        </div>

        <div class="relative mt-8 overflow-x-auto pb-2">
            <div class="relative min-w-[900px]">
                <div class="absolute top-5 right-[6.25%] left-[6.25%] h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                <div
                    class="absolute top-5 left-[6.25%] h-0.5 bg-primary-600"
                    style="width: {{ $progressWidth }}%;"
                ></div>

                <div class="relative flex items-start">
                    @foreach ($statusSteps as $index => $step)
                        @php
                            $isComplete = $index < $statusIndex;
                            $isCurrent = $index === $statusIndex;
                            $stepIcon = $isComplete ? 'heroicon-o-check' : $step['icon'];
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                            <span @class([
                                'relative z-10 flex size-10 items-center justify-center rounded-full border-2',
                                'border-primary-600 bg-primary-600 text-white dark:border-primary-500 dark:bg-primary-500' => $isComplete || $isCurrent,
                                'ring-4 ring-primary-600/15' => $isCurrent,
                                'border-gray-300 bg-white text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-600' => ! $isComplete && ! $isCurrent,
                            ])>
                                <x-filament::icon :icon="$stepIcon" class="size-4" />
                            </span>
                            <span @class([
                                'mt-3 max-w-[115px] text-xs font-semibold leading-4',
                                'text-gray-900 dark:text-white' => $isComplete || $isCurrent,
                                'text-gray-400 dark:text-gray-600' => ! $isComplete && ! $isCurrent,
                            ])>
                                {{ $step['label'] }}
                            </span>
                            <span @class([
                                'mt-1 max-w-[125px] text-[11px] leading-4',
                                'text-gray-600 dark:text-gray-400' => $isCurrent,
                                'text-gray-400 dark:text-gray-600' => ! $isCurrent,
                            ])>
                                {{ $step['description'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if (isset($statusMessages[$order->status]))
            <div class="mt-5 flex items-start gap-3 rounded-lg border border-primary-100 bg-primary-50/60 px-4 py-3 text-sm text-primary-900 dark:border-primary-900 dark:bg-primary-950/40 dark:text-primary-100">
                <x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 size-4 shrink-0" />
                <p>{{ $statusMessages[$order->status] }}</p>
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">02</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">订单详情</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">商品、已选选项和客户的订单备注。</p>
        </div>

        <dl class="mt-6 grid gap-4 border-y border-gray-100 py-5 sm:grid-cols-3 dark:border-gray-800">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">订单编号</dt>
                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">#{{ $order->id }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">支付状态</dt>
                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $paymentStatusLabel }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">订单总计</dt>
                <dd class="mt-1 text-sm font-semibold text-primary-700 dark:text-primary-300">${{ $money($order->total) }}</dd>
            </div>
        </dl>

        <div class="mt-6 space-y-4">
            @forelse ($order->items as $item)
                @php
                    $options = collect($item->options ?? [])->reject(static fn (mixed $value, string|int $key): bool => $key === 'design_service_request_id' || $value === null || $value === '');
                @endphp
                <article class="rounded-lg border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                    <div class="flex items-start gap-4">
                        <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-800">
                            @if ($item->product?->featured_image)
                                <img src="{{ $item->product->featured_image }}" alt="{{ $item->product->name }}" class="size-full object-cover">
                            @else
                                <x-filament::icon icon="heroicon-o-cube" class="size-7 text-gray-400" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-semibold text-gray-950 dark:text-white">{{ $item->product?->name ?? '产品不可用' }}</h4>
                            <p class="mt-1 text-sm text-gray-500">数量：{{ $item->quantity }}</p>
                            <p class="mt-2 text-xs text-gray-500">单价：${{ $money($item->unit_price) }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">${{ $money($item->subtotal) }}</p>
                    </div>

                    @if ($options->isNotEmpty())
                        <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">已选选项</p>
                            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($options as $key => $value)
                                    <div>
                                        <dt class="text-xs text-gray-500">{{ $formatOptionLabel($key) }}</dt>
                                        <dd class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $formatOptionValue($value) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 dark:border-gray-700">暂无订单商品。</p>
            @endforelse
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-start dark:border-gray-800">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="size-4 text-primary-600" />
                订单备注
            </div>
            <p class="whitespace-pre-wrap text-sm leading-6 text-gray-500 sm:ml-auto sm:max-w-3xl sm:text-right">{{ $order->notes ?: '未添加订单备注。' }}</p>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">03</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">已上传文件</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">此订单附带的所有设计稿、标志和参考文件。</p>
        </div>

        @if (count($uploadedFiles) > 0)
            <div class="mt-6 grid gap-3 md:grid-cols-2">
                @foreach ($uploadedFiles as $file)
                    @php($isImage = preg_match('/\.(jpe?g|png|gif|webp|tiff?)$/i', $file['filename']) === 1)
                    @if ($file['available'] && $file['url'])
                        <a href="{{ $file['url'] }}" target="_blank" rel="noreferrer" download class="group flex min-w-0 items-center gap-3 rounded-lg border border-gray-200 p-3.5 transition hover:border-primary-400 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">
                    @else
                        <div class="flex min-w-0 items-center gap-3 rounded-lg border border-gray-200 p-3.5 dark:border-gray-800">
                    @endif
                            <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-primary-600 dark:bg-gray-800 dark:text-primary-300">
                                @if ($isImage && $file['url'])
                                    <img src="{{ $file['url'] }}" alt="" class="size-full object-cover">
                                @else
                                    <x-filament::icon :icon="$isImage ? 'heroicon-o-photo' : 'heroicon-o-document-text'" class="size-5" />
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $file['label'] }}</span>
                                    <span class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-950 dark:text-primary-300">{{ $file['source'] }}</span>
                                </span>
                                <span class="mt-1 block truncate text-xs text-gray-500">{{ $file['filename'] }}</span>
                            </span>
                            @if ($file['available'] && $file['url'])
                                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-4 shrink-0 text-gray-400 transition group-hover:text-primary-600" />
                            @else
                                <span class="shrink-0 text-[11px] text-gray-400">不可用</span>
                            @endif
                    @if ($file['available'] && $file['url'])
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="mt-6 flex items-center gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-950/50">
                <x-filament::icon icon="heroicon-o-document-text" class="size-5 text-gray-400" />
                此订单尚未上传文件。
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">04</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">客户信息</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">客户联系方式、地址和配送信息。</p>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-950/50">
                <div class="mb-5 flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-user" class="size-4" />
                    </span>
                    <h4 class="font-semibold text-gray-950 dark:text-white">客户</h4>
                </div>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs text-gray-500">姓名</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">电子邮箱</dt>
                        <dd class="break-words text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->customer_email }}</dd>
                    </div>
                    @if ($order->customer_phone)
                        <div>
                            <dt class="text-xs text-gray-500">电话</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->customer_phone }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-950/50">
                <div class="mb-5 flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-map-pin" class="size-4" />
                    </span>
                    <h4 class="font-semibold text-gray-950 dark:text-white">收件地址</h4>
                </div>
                <address class="text-sm leading-6 text-gray-600 not-italic dark:text-gray-300">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $order->shipping_address }}</p>
                    <p>{{ $order->shipping_city }}{{ $order->shipping_state ? ', '.$order->shipping_state : '' }} {{ $order->shipping_zip }}</p>
                    <p>{{ $order->shipping_country }}</p>
                </address>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-5 lg:col-span-2 dark:border-gray-800 dark:bg-gray-950/50">
                <div class="mb-5 flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-truck" class="size-4" />
                    </span>
                    <h4 class="font-semibold text-gray-950 dark:text-white">配送信息</h4>
                </div>
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">配送方式</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $shippingMethodLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">承运商</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->shipping_carrier ?: '未分配' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">运费</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">${{ $money($order->shipping_fee) }}</dd>
                    </div>
                </dl>

                @if ($order->tracking_number || $order->tracking_url)
                    <div class="mt-5 flex flex-col justify-between gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center dark:border-gray-800">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">快递单号</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->tracking_number ?: '暂无' }}</p>
                        </div>
                        @if ($order->tracking_url)
                            <a href="{{ $order->tracking_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                                查询物流
                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="size-4" />
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
