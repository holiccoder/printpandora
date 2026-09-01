<div class="space-y-6">
    @if ($designRequests->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 text-sm text-gray-600">
            No product-page design submissions are linked to this order.
        </div>
    @else
        @foreach ($designRequests as $designRequest)
            @php
                $payload = is_array($designRequest->desgin) ? $designRequest->desgin : [];
                $files = [];

                foreach ([
                    'logo_path' => 'Logo',
                    'design_path' => 'Canva design',
                ] as $pathKey => $label) {
                    $path = data_get($payload, $pathKey);

                    if (is_string($path) && trim($path) !== '') {
                        $files[] = ['label' => $label, 'path' => $path];
                    }
                }

                foreach ((array) data_get($payload, 'example_paths', []) as $index => $path) {
                    if (is_string($path) && trim($path) !== '') {
                        $files[] = ['label' => 'Example '.($index + 1), 'path' => $path];
                    }
                }
            @endphp

            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            Submission #{{ $designRequest->id }}
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Submitted {{ $designRequest->created_at?->format('Y-m-d H:i') }}
                        </p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                        {{ data_get($payload, 'mode', 'Unknown mode') }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    @foreach ([
                        'product_name' => 'Product',
                        'email' => 'Email',
                        'business_name' => 'Business name',
                        'business_card_type' => 'Business card type',
                        'design_service_code' => 'Design service',
                        'terms_accepted' => 'Terms accepted',
                    ] as $key => $label)
                        @php($value = data_get($payload, $key))
                        @if ($value !== null && $value !== '')
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    {{ $label }}
                                </dt>
                                <dd class="mt-1 break-words text-sm text-gray-900">
                                    @if (is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                    @elseif (is_scalar($value))
                                        {{ $value }}
                                    @else
                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>

                @if (filled(data_get($payload, 'card_info')))
                    <div class="mt-5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Card information
                        </dt>
                        <dd class="mt-1 whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 text-sm text-gray-900">{{ data_get($payload, 'card_info') }}</dd>
                    </div>
                @endif

                @if ($files !== [])
                    <div class="mt-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Uploaded files
                        </p>
                        <ul class="mt-2 space-y-2">
                            @foreach ($files as $file)
                                <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <span class="break-all text-gray-700">{{ $file['label'] }}</span>
                                    @if (Storage::disk('public')->exists($file['path']))
                                        <a
                                            href="{{ Storage::disk('public')->url($file['path']) }}"
                                            target="_blank"
                                            rel="noopener"
                                            download
                                            class="font-medium text-primary-600 hover:text-primary-700"
                                        >
                                            Download
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500">File unavailable</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </article>
        @endforeach
    @endif
</div>
