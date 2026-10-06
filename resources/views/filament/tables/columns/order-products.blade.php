<div class="space-y-2">
    @foreach ($getRecord()->items as $item)
        @php($options = \App\Support\OrderOptionFormatter::options($item->options))
        <div>
            <div>{{ $item->product?->name ?? '产品不可用' }}</div>
            @if ($options !== '')
                <div class="text-gray-500 dark:text-gray-400">{{ $options }}</div>
            @endif
        </div>
    @endforeach
</div>
