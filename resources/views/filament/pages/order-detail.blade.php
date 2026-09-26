@php
    $statusSteps = [
        ['key' => 'pending', 'label' => 'Pending', 'description' => 'Payment pending', 'icon' => 'heroicon-o-credit-card'],
        ['key' => 'confirmed', 'label' => 'Confirmed', 'description' => 'Payment confirmed', 'icon' => 'heroicon-o-shield-check'],
        ['key' => 'pending_modification', 'label' => 'Pending modification', 'description' => 'Changes can be requested', 'icon' => 'heroicon-o-pencil-square'],
        ['key' => 'pending_production', 'label' => 'Pending production', 'description' => 'Design approved', 'icon' => 'heroicon-o-lock-closed'],
        ['key' => 'production', 'label' => 'Production', 'description' => 'Being made', 'icon' => 'heroicon-o-cog-6-tooth'],
        ['key' => 'pending_shipment', 'label' => 'Pending shipment', 'description' => 'Preparing to ship', 'icon' => 'heroicon-o-archive-box'],
        ['key' => 'shipped', 'label' => 'Shipped', 'description' => 'On its way', 'icon' => 'heroicon-o-truck'],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'description' => 'Order cancelled', 'icon' => 'heroicon-o-x-circle'],
    ];
    $statusIndex = array_search($order->status, array_column($statusSteps, 'key'), true);
    $statusIndex = $statusIndex === false ? 0 : $statusIndex;
    $statusLabel = $statusSteps[$statusIndex]['label'] ?? \Illuminate\Support\Str::headline((string) $order->status);
    $progressWidth = count($statusSteps) > 1
        ? ($statusIndex / (count($statusSteps) - 1)) * 87.5
        : 0;
    $statusMessages = [
        'pending_modification' => 'This order can still be revised. Contact support if the customer needs to request a change.',
        'pending_production' => 'The design is confirmed and the order details are now locked for production.',
        'production' => 'The order is currently being produced.',
        'pending_shipment' => 'Production is complete and the order is waiting to be handed to the carrier.',
    ];
    $money = static fn (mixed $value): string => number_format((float) $value, 2);
    $formatOptionValue = static function (mixed $value) use (&$formatOptionValue): string {
        if (is_array($value)) {
            return collect($value)
                ->map(static fn (mixed $entry): string => $formatOptionValue($entry))
                ->implode(', ');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', (string) $value));
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600 dark:text-primary-400">
                Custom order detail
            </p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Order #{{ $order->id }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Placed {{ $order->created_at?->format('M j, Y, g:i A') ?? '—' }}
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full px-3 py-1.5 text-sm font-semibold {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300' }}">
            {{ $statusLabel }}
        </span>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">01</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Order status</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Follow the order from payment through delivery.</p>
            </div>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Current: {{ $statusLabel }}</span>
        </div>

        <div class="relative mt-8 overflow-x-auto pb-2">
            <div class="relative min-w-[900px]">
                <div class="absolute top-5 right-[6.25%] left-[6.25%] h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                <div
                    class="absolute top-5 left-[6.25%] h-0.5 {{ $order->status === 'cancelled' ? 'bg-red-500' : 'bg-primary-600' }}"
                    style="width: {{ $progressWidth }}%;"
                ></div>

                <div class="relative flex items-start">
                    @foreach ($statusSteps as $index => $step)
                        @php
                            $isComplete = $index < $statusIndex;
                            $isCurrent = $index === $statusIndex;
                            $isCancelled = $isCurrent && $order->status === 'cancelled';
                            $stepIcon = $isComplete ? 'heroicon-o-check' : $step['icon'];
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                            <span @class([
                                'relative z-10 flex size-10 items-center justify-center rounded-full border-2 bg-white dark:bg-gray-900',
                                'border-primary-600 bg-primary-600 text-white dark:border-primary-500 dark:bg-primary-500' => $isComplete,
                                'border-primary-600 bg-primary-600 text-white ring-4 ring-primary-600/15 dark:border-primary-500 dark:bg-primary-500' => $isCurrent && ! $isCancelled,
                                'border-red-500 bg-red-500 text-white ring-4 ring-red-500/15' => $isCancelled,
                                'border-gray-300 text-gray-400 dark:border-gray-700 dark:text-gray-600' => ! $isComplete && ! $isCurrent,
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
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Order details</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Products, selected options, and the customer’s order note.</p>
        </div>

        <dl class="mt-6 grid gap-4 border-y border-gray-100 py-5 sm:grid-cols-3 dark:border-gray-800">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Order ID</dt>
                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">#{{ $order->id }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Payment status</dt>
                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $order->payment_status === 'paid' ? 'Payment confirmed' : \Illuminate\Support\Str::headline((string) ($order->payment_status ?: 'Pending')) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Order total</dt>
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
                            <h4 class="font-semibold text-gray-950 dark:text-white">{{ $item->product?->name ?? 'Product unavailable' }}</h4>
                            <p class="mt-1 text-sm text-gray-500">Quantity: {{ $item->quantity }}</p>
                            <p class="mt-2 text-xs text-gray-500">${{ $money($item->unit_price) }} each</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">${{ $money($item->subtotal) }}</p>
                    </div>

                    @if ($options->isNotEmpty())
                        <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Selected options</p>
                            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($options as $key => $value)
                                    <div>
                                        <dt class="text-xs text-gray-500">{{ \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', (string) $key)) }}</dt>
                                        <dd class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $formatOptionValue($value) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 dark:border-gray-700">No order items found.</p>
            @endforelse
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-start dark:border-gray-800">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="size-4 text-primary-600" />
                Order note
            </div>
            <p class="whitespace-pre-wrap text-sm leading-6 text-gray-500 sm:ml-auto sm:max-w-3xl sm:text-right">{{ $order->notes ?: 'No order note was added.' }}</p>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">03</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Uploaded files</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Every artwork, logo, and reference file attached to this order.</p>
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
                                <span class="shrink-0 text-[11px] text-gray-400">Unavailable</span>
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
                No files were uploaded with this order.
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">04</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Customer details</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Customer contact information, address, and shipping details.</p>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-950/50">
                <div class="mb-5 flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-user" class="size-4" />
                    </span>
                    <h4 class="font-semibold text-gray-950 dark:text-white">Customer</h4>
                </div>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs text-gray-500">Name</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Email</dt>
                        <dd class="break-words text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->customer_email }}</dd>
                    </div>
                    @if ($order->customer_phone)
                        <div>
                            <dt class="text-xs text-gray-500">Phone</dt>
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
                    <h4 class="font-semibold text-gray-950 dark:text-white">Delivery address</h4>
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
                    <h4 class="font-semibold text-gray-950 dark:text-white">Shipping details</h4>
                </div>
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Shipping method</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->shipping_method === 'dhl_express' ? 'DHL Express' : \Illuminate\Support\Str::headline((string) $order->shipping_method) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Carrier</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->shipping_carrier ?: 'Not assigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Shipping fee</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">${{ $money($order->shipping_fee) }}</dd>
                    </div>
                </dl>

                @if ($order->tracking_number || $order->tracking_url)
                    <div class="mt-5 flex flex-col justify-between gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center dark:border-gray-800">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tracking number</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $order->tracking_number ?: 'Not available yet' }}</p>
                        </div>
                        @if ($order->tracking_url)
                            <a href="{{ $order->tracking_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                                Track package
                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="size-4" />
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
