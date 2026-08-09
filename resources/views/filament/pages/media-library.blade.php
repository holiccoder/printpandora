@php($library = $this->getLibraryState())
@php($assets = $library['paginator'])

@once
    <style>
        [x-cloak] {
            display: none !important;
        }

        .media-library-card {
            overflow: hidden;
            border: 1px solid var(--gray-200);
            border-radius: 1rem;
            background: white;
            box-shadow: 0 1px 2px rgb(0 0 0 / 4%);
            transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
        }

        .media-library-card:hover {
            border-color: var(--primary-300, var(--gray-300));
            box-shadow: 0 12px 28px rgb(15 23 42 / 9%);
            transform: translateY(-2px);
        }

        .media-library-preview {
            position: relative;
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background-color: var(--gray-50);
            background-image:
                linear-gradient(45deg, var(--gray-100) 25%, transparent 25%),
                linear-gradient(-45deg, var(--gray-100) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, var(--gray-100) 75%),
                linear-gradient(-45deg, transparent 75%, var(--gray-100) 75%);
            background-position: 0 0, 0 8px, 8px -8px, -8px 0;
            background-size: 16px 16px;
        }

        .media-library-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 200ms ease;
        }

        .media-library-card:hover .media-library-preview img {
            transform: scale(1.025);
        }

        .media-library-preview-overlay {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            color: white;
            background: rgb(15 23 42 / 0%);
            opacity: 0;
            transition: opacity 150ms ease, background 150ms ease;
        }

        .media-library-preview:hover .media-library-preview-overlay,
        .media-library-preview:focus-visible .media-library-preview-overlay {
            background: rgb(15 23 42 / 42%);
            opacity: 1;
        }

        .media-library-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.625rem;
            color: var(--gray-600);
            background: white;
            transition: color 150ms ease, border-color 150ms ease, background 150ms ease;
        }

        .media-library-icon-button:hover:not(:disabled) {
            border-color: var(--primary-300, var(--gray-300));
            color: var(--primary-700, var(--gray-900));
            background: var(--primary-50, var(--gray-50));
        }

        .media-library-icon-button:focus-visible {
            outline: 2px solid var(--primary-500);
            outline-offset: 2px;
        }

        .media-library-icon-button:disabled {
            cursor: not-allowed;
            color: var(--gray-300);
            background: var(--gray-50);
        }

        .media-library-icon-button.danger:hover:not(:disabled) {
            border-color: #fca5a5;
            color: #b91c1c;
            background: #fef2f2;
        }

        .media-library-pagination > nav {
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.875rem;
            background: linear-gradient(135deg, white, var(--gray-50));
            box-shadow: 0 1px 2px rgb(0 0 0 / 4%);
        }

        .dark .media-library-card,
        .dark .media-library-icon-button {
            border-color: var(--gray-700);
            color: var(--gray-300);
            background: var(--gray-900);
        }

        .dark .media-library-card:hover,
        .dark .media-library-icon-button:hover:not(:disabled) {
            border-color: var(--primary-500, var(--gray-600));
        }

        .dark .media-library-preview {
            background-color: var(--gray-950);
            background-image:
                linear-gradient(45deg, var(--gray-900) 25%, transparent 25%),
                linear-gradient(-45deg, var(--gray-900) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, var(--gray-900) 75%),
                linear-gradient(-45deg, transparent 75%, var(--gray-900) 75%);
        }

        .dark .media-library-icon-button:hover:not(:disabled) {
            color: white;
            background: var(--gray-800);
        }

        .dark .media-library-icon-button:disabled {
            color: var(--gray-700);
            background: var(--gray-950);
        }

        .dark .media-library-icon-button.danger:hover:not(:disabled) {
            border-color: #b91c1c;
            color: #fca5a5;
            background: rgb(127 29 29 / 25%);
        }

        .dark .media-library-pagination > nav {
            border-color: var(--gray-700);
            background: linear-gradient(135deg, var(--gray-900), var(--gray-950));
        }
    </style>
@endonce

<x-filament-panels::page>
    <div
        x-data="{
            selected: null,
            copied: null,
            open(asset) {
                this.selected = asset
                document.documentElement.style.overflow = 'hidden'
            },
            close() {
                this.selected = null
                document.documentElement.style.overflow = ''
            },
            async copy(value, key) {
                await navigator.clipboard.writeText(value)
                this.copied = key
                window.setTimeout(() => this.copied = null, 1600)
            },
        }"
        x-on:keydown.escape.window="close()"
        class="space-y-6"
    >
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">上传到媒体库</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        选择用途后可一次上传多张图片。原图会立即可用，WebP 会在后台生成。
                    </p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                    JPEG · PNG · WebP
                </span>
            </div>

            <form wire:submit="save" class="space-y-5">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-up" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">上传图片</span>
                        <span wire:loading wire:target="save">正在处理…</span>
                    </x-filament::button>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['label' => '全部图片', 'value' => $library['total'], 'icon' => 'heroicon-o-photo'],
                ['label' => '正在使用', 'value' => $library['used'], 'icon' => 'heroicon-o-link'],
                ['label' => '可安全删除', 'value' => $library['unused'], 'icon' => 'heroicon-o-check-circle'],
                ['label' => '占用空间', 'value' => $library['total_size'], 'icon' => 'heroicon-o-circle-stack'],
            ] as $stat)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-300">
                            <x-filament::icon :icon="$stat['icon']" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="mt-0.5 truncate text-xl font-bold text-gray-950 dark:text-white">{{ $stat['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_12rem_11rem_11rem_auto]">
                    <label class="relative block">
                        <span class="sr-only">搜索图片</span>
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-gray-400" />
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="搜索名称、路径或集合…"
                            class="w-full rounded-lg border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-950 dark:text-white"
                        />
                    </label>

                    <select
                        wire:model.live="purposeFilter"
                        aria-label="按用途筛选"
                        class="rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-200"
                    >
                        <option value="all">全部用途</option>
                        @foreach ($library['purpose_options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <select
                        wire:model.live="usageFilter"
                        aria-label="按使用状态筛选"
                        class="rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-200"
                    >
                        <option value="all">全部状态</option>
                        <option value="used">正在使用</option>
                        <option value="unused">可安全删除</option>
                    </select>

                    <select
                        wire:model.live="sort"
                        aria-label="排序方式"
                        class="rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-200"
                    >
                        @foreach ($library['sort_options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <button
                        type="button"
                        wire:click="refreshCatalog"
                        wire:loading.attr="disabled"
                        wire:target="refreshCatalog"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-path" class="size-4" wire:loading.class="animate-spin" wire:target="refreshCatalog" />
                        刷新
                    </button>
                </div>
            </div>

            @if ($assets->isEmpty())
                <div class="flex min-h-72 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 text-center dark:border-gray-700 dark:bg-gray-900">
                    <span class="flex size-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        <x-filament::icon icon="heroicon-o-photo" class="size-7" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">没有找到图片</h3>
                    <p class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                        请调整搜索或筛选条件，也可以在上方上传新图片。
                    </p>
                </div>
            @else
                <div wire:loading.class="opacity-60" wire:target="search,purposeFilter,usageFilter,sort" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($assets as $asset)
                        <article wire:key="media-asset-{{ $asset['id'] }}" class="media-library-card">
                            <button
                                type="button"
                                x-on:click="open(@js($asset))"
                                class="media-library-preview focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-inset"
                                aria-label="预览 {{ $asset['name'] }}"
                            >
                                <img src="{{ $asset['url'] }}" alt="{{ $asset['name'] }}" loading="lazy" />
                                <span class="media-library-preview-overlay">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-950/75 px-3 py-1.5 text-xs font-semibold backdrop-blur">
                                        <x-filament::icon icon="heroicon-o-arrows-pointing-out" class="size-4" />
                                        查看详情
                                    </span>
                                </span>
                            </button>

                            <div class="space-y-3 p-4">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                                        {{ $asset['purpose_label'] }}
                                    </span>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-400/10 dark:text-amber-300' => $asset['conversion_status'] === 'processing',
                                        'bg-green-100 text-green-800 dark:bg-green-400/10 dark:text-green-300' => $asset['conversion_status'] === 'ready',
                                        'bg-red-100 text-red-800 dark:bg-red-400/10 dark:text-red-300' => $asset['conversion_status'] === 'failed',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $asset['conversion_status'] === 'original',
                                    ])>
                                        {{ $asset['conversion_status_label'] }}
                                    </span>
                                    @if ($asset['is_used'])
                                        <button
                                            type="button"
                                            x-on:click="open(@js($asset))"
                                            class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 transition hover:bg-amber-100 dark:bg-amber-400/10 dark:text-amber-300"
                                        >
                                            使用中 · {{ $asset['usage_count'] }}
                                        </button>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                                            未使用
                                        </span>
                                    @endif
                                    @if ($asset['has_original'])
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">含原图</span>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-gray-950 dark:text-white" title="{{ $asset['name'] }}">
                                        {{ $asset['name'] }}
                                    </h3>
                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $asset['primary_path'] }}">
                                        {{ $asset['primary_path'] }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    <span>{{ $asset['dimensions'] }}</span>
                                    <span>{{ $asset['formatted_total_size'] }}</span>
                                    <span>{{ $asset['modified_at_label'] }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            x-on:click="copy(@js($asset['primary_path']), 'path-{{ $asset['id'] }}')"
                                            class="media-library-icon-button"
                                            title="复制存储路径"
                                        >
                                            <x-filament::icon icon="heroicon-o-clipboard" class="size-4" />
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="copy(@js($asset['absolute_url']), 'url-{{ $asset['id'] }}')"
                                            class="media-library-icon-button"
                                            title="复制公开 URL"
                                        >
                                            <x-filament::icon icon="heroicon-o-link" class="size-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="downloadAsset('{{ $asset['id'] }}')"
                                            class="media-library-icon-button"
                                            title="下载"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-4" />
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="deleteAsset('{{ $asset['id'] }}')"
                                        @if ($asset['is_used']) disabled @else wire:confirm="确定删除这张图片及其原图和衍生文件吗？此操作无法撤销。" @endif
                                        class="media-library-icon-button danger"
                                        title="{{ $asset['is_used'] ? '图片正在使用中，请先移除引用' : '删除图片' }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-trash" class="size-4" />
                                    </button>
                                </div>

                                <p
                                    x-cloak
                                    x-show="copied === 'path-{{ $asset['id'] }}' || copied === 'url-{{ $asset['id'] }}'"
                                    x-transition.opacity
                                    class="text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    已复制
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($assets->hasPages())
                    <div class="media-library-pagination pt-2">
                        {{ $assets->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </section>

        <template x-teleport="body">
            <div
                x-cloak
                x-show="selected"
                x-transition.opacity.duration.150ms
                x-on:click.self="close()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/75 p-4 backdrop-blur-sm sm:p-8"
                role="dialog"
                aria-modal="true"
                aria-label="图片详情"
            >
                <div
                    x-show="selected"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100"
                    class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900 lg:grid lg:grid-cols-[minmax(0,1.4fr)_minmax(20rem,0.6fr)]"
                >
                    <div class="flex min-h-64 items-center justify-center overflow-hidden bg-gray-100 p-4 dark:bg-gray-950 sm:p-8">
                        <img :src="selected?.url" :alt="selected?.name" class="max-h-[72vh] max-w-full rounded-lg object-contain shadow-lg" />
                    </div>

                    <div class="flex min-h-0 flex-col">
                        <div class="flex items-start justify-between gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                            <div class="min-w-0">
                                <h2 x-text="selected?.name" class="truncate text-base font-semibold text-gray-950 dark:text-white"></h2>
                                <p x-text="selected?.purpose_label" class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-300"></p>
                            </div>
                            <button type="button" x-on:click="close()" class="media-library-icon-button shrink-0" aria-label="关闭详情">
                                <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">尺寸</dt>
                                    <dd x-text="selected?.dimensions" class="mt-1 font-semibold text-gray-900 dark:text-white"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">总大小</dt>
                                    <dd x-text="selected?.formatted_total_size" class="mt-1 font-semibold text-gray-900 dark:text-white"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">格式</dt>
                                    <dd x-text="selected?.extension?.toUpperCase()" class="mt-1 font-semibold text-gray-900 dark:text-white"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">文件数量</dt>
                                    <dd x-text="selected?.variant_count" class="mt-1 font-semibold text-gray-900 dark:text-white"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">处理状态</dt>
                                    <dd x-text="selected?.conversion_status_label" class="mt-1 font-semibold text-gray-900 dark:text-white"></dd>
                                </div>
                            </dl>

                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">存储路径</p>
                                <button
                                    type="button"
                                    x-on:click="copy(selected.primary_path, 'modal-path')"
                                    class="mt-1.5 flex w-full items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2 text-left text-xs text-gray-700 transition hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    <span x-text="selected?.primary_path" class="min-w-0 break-all"></span>
                                    <x-filament::icon icon="heroicon-o-clipboard" class="size-4 shrink-0" />
                                </button>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">使用情况</h3>
                                    <span
                                        x-text="selected?.usage_count ? `${selected.usage_count} 处引用` : '未使用'"
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                    ></span>
                                </div>

                                <template x-if="selected?.usage_count === 0">
                                    <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        当前没有已知引用，可以安全删除。
                                    </p>
                                </template>

                                <div x-show="selected?.usage_count > 0" class="mt-3 space-y-2">
                                    <template x-for="usage in selected?.usages ?? []" :key="`${usage.type}-${usage.record_id}-${usage.location}`">
                                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                            <p x-text="usage.location" class="text-xs font-semibold text-amber-600 dark:text-amber-300"></p>
                                            <div class="mt-1 flex items-center justify-between gap-3">
                                                <span x-text="usage.label" class="min-w-0 truncate text-sm text-gray-800 dark:text-gray-200"></span>
                                                <a
                                                    x-show="usage.url"
                                                    :href="usage.url"
                                                    class="shrink-0 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300"
                                                >
                                                    打开记录
                                                </a>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-filament-panels::page>
