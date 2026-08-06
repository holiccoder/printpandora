<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-neutral-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-neutral-950 dark:text-white">Product image manager</h2>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Replace images for this product only. The original product-option image remains available as the reset value.
                    </p>
                </div>
                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ count($this->imageSlots) }} configurable gallery {{ count($this->imageSlots) === 1 ? 'image' : 'images' }}
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-neutral-900">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-neutral-950 dark:text-white">Featured image</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    This image is used by product listings, cart items, and search previews.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,24rem)_minmax(0,1fr)] lg:items-start">
                <div class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-lg bg-neutral-50 dark:bg-neutral-950">
                    @if ($featuredImage)
                        <img src="{{ $featuredImage }}" alt="{{ $this->getProduct()->name }}" class="h-full w-full object-contain" />
                    @else
                        <span class="px-4 text-center text-sm text-neutral-400">No featured image</span>
                    @endif
                </div>

                <form wire:submit="replaceFeaturedImage" class="space-y-3">
                    <label for="featured-upload" class="flex cursor-pointer items-center justify-center rounded-lg border border-dashed border-neutral-300 px-4 py-6 text-center text-sm font-medium text-neutral-700 transition hover:border-[#800020] hover:bg-[#800020]/5 dark:border-white/15 dark:text-neutral-200">
                        <span>Choose a replacement image</span>
                    </label>
                    <input id="featured-upload" type="file" accept="image/jpeg,image/png,image/webp" wire:model="uploads.featured" class="sr-only" />

                    @if (isset($uploads['featured']) && $uploads['featured'])
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Replacement image ready to upload.</p>
                    @endif

                    <div wire:loading wire:target="uploads.featured" class="text-xs text-neutral-500 dark:text-neutral-400">
                        Uploading image…
                    </div>

                    @error('uploads.featured')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror

                    <x-filament::button type="submit" color="primary">
                        Upload and replace featured image
                    </x-filament::button>
                </form>
            </div>
        </div>

        @forelse ($galleryGroups as $group)
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-neutral-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-950 dark:text-white">{{ $group['label'] }}</h2>
                        @if ($group['is_default'])
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-[#800020]">Default gallery</p>
                        @endif
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ count($group['images']) }} {{ count($group['images']) === 1 ? 'image' : 'images' }}
                    </span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($group['images'] as $slot)
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 dark:border-white/10 dark:bg-neutral-950">
                            <div class="mb-3 flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-neutral-700 dark:text-neutral-200">
                                        Image {{ $slot['image_index'] + 1 }}
                                    </p>
                                    <p class="mt-1 truncate text-[11px] text-neutral-400" title="{{ $slot['source_path'] }}">
                                        {{ $slot['source_path'] }}
                                    </p>
                                </div>
                                @if ($slot['is_overridden'])
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        Replaced
                                    </span>
                                @endif
                            </div>

                            <label for="upload-{{ $slot['key'] }}" class="mb-3 flex cursor-pointer items-center justify-center rounded-md border border-dashed border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-700 transition hover:border-[#800020] hover:bg-[#800020]/5 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200">
                                Choose replacement image
                            </label>
                            <input id="upload-{{ $slot['key'] }}" type="file" accept="image/jpeg,image/png,image/webp" wire:model="uploads.{{ $slot['key'] }}" class="sr-only" />

                            <div class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-md bg-white dark:bg-neutral-900">
                                @if ($slot['current_url'])
                                    <img src="{{ $slot['current_url'] }}" alt="{{ $group['label'] }} image {{ $slot['image_index'] + 1 }}" loading="lazy" class="h-full w-full object-contain" />
                                @else
                                    <span class="text-xs text-neutral-400">No image</span>
                                @endif
                            </div>

                            @if (isset($uploads[$slot['key']]) && $uploads[$slot['key']])
                                <p class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">Replacement image ready to upload.</p>
                            @endif

                            <div wire:loading wire:target="uploads.{{ $slot['key'] }}" class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">
                                Uploading image…
                            </div>

                            @error('uploads.' . $slot['key'])
                                <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex gap-2">
                                <x-filament::button type="button" wire:click="replaceImage('{{ $slot['key'] }}')" size="sm" class="flex-1">
                                    Upload and replace
                                </x-filament::button>

                                @if ($slot['is_overridden'])
                                    <x-filament::button type="button" wire:click="resetImage('{{ $slot['key'] }}')" wire:confirm="Restore this image to the original product-option image?" color="gray" size="sm">
                                        Reset
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-white/15 dark:bg-neutral-900">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No configurable gallery images were found for this product.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
