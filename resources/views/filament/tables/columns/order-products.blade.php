<div class="space-y-2">
    @foreach ($getRecord()->items as $item)
        @php($options = \App\Support\OrderOptionFormatter::optionPairs($item->options))
        <div>
            <div>{{ $item->product?->name ?? '产品不可用' }}</div>
            @if ($options !== [])
                <div class="mt-1 grid grid-cols-4 gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    @foreach ($options as $option)
                        <div class="min-w-0 break-words">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $option['name'] }}:</span>
                            <span>{{ $option['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
