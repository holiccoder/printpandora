@php
    use Filament\Support\Enums\GridDirection;

    $fieldWrapperView = $getFieldWrapperView();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $isHtmlAllowed = $isHtmlAllowed();
    $gridDirection = $getGridDirection() ?? GridDirection::Column;
    $isBulkToggleable = $isBulkToggleable();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable();
    $statePath = $getStatePath();
    $options = $getOptions();
    $livewireKey = $getLivewireKey();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
    $perPage = 12;
@endphp

@once
    <style>
        .image-picker-checkbox-list .fi-fo-checkbox-list-options {
            align-items: stretch;
        }

        .image-picker-checkbox-list .fi-fo-checkbox-list-option-ctn {
            min-width: 0;
            height: 100%;
        }

        .image-picker-checkbox-list .fi-fo-checkbox-list-option {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0;
            height: 100%;
            min-width: 0;
            overflow: hidden;
            padding: 0.5rem;
            cursor: pointer;
            border: 1px solid var(--gray-200);
            border-radius: 0.75rem;
            background: white;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .image-picker-checkbox-list .fi-fo-checkbox-list-option:hover {
            border-color: var(--gray-400);
            box-shadow: 0 4px 12px rgb(0 0 0 / 8%);
        }

        .image-picker-checkbox-list .fi-fo-checkbox-list-option:has(.fi-checkbox-input:checked) {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 1px var(--primary-500);
        }

        .image-picker-checkbox-list .fi-fo-checkbox-list-option .fi-checkbox-input {
            position: absolute;
            z-index: 2;
            top: 0.75rem;
            right: 0.75rem;
            margin: 0;
            box-shadow: 0 0 0 2px white;
        }

        .image-picker-checkbox-list .fi-fo-checkbox-list-option-text,
        .image-picker-checkbox-list .fi-fo-checkbox-list-option-label {
            display: block;
            width: 100%;
            min-width: 0;
        }

        .image-picker-card-preview {
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 0.5rem;
            background: var(--gray-100);
        }

        .image-picker-card-image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .image-picker-card-caption {
            display: grid;
            align-content: center;
            gap: 0.125rem;
            height: 3rem;
            min-width: 0;
            padding: 0.375rem 0.25rem 0;
        }

        .image-picker-card-name,
        .image-picker-card-path {
            display: block;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .image-picker-card-name {
            color: var(--gray-950);
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1rem;
        }

        .image-picker-card-path {
            color: var(--gray-500);
            font-size: 0.6875rem;
            font-weight: 400;
            line-height: 1rem;
        }

        .dark .image-picker-checkbox-list .fi-fo-checkbox-list-option {
            border-color: var(--gray-700);
            background: var(--gray-900);
        }

        .dark .image-picker-checkbox-list .fi-fo-checkbox-list-option .fi-checkbox-input {
            box-shadow: 0 0 0 2px var(--gray-900);
        }

        .dark .image-picker-card-preview {
            background: var(--gray-800);
        }

        .dark .image-picker-card-name {
            color: white;
        }

        .dark .image-picker-card-path {
            color: var(--gray-400);
        }
    </style>
@endonce

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('checkbox-list', 'filament/forms') }}"
        x-data="checkboxListFormComponent({
            livewireId: @js($this->getId()),
        })"
        {{ $getExtraAlpineAttributeBag()->class(['fi-fo-checkbox-list', 'image-picker-checkbox-list']) }}
    >
        <div
            x-data="{
                imageSearch: '',
                page: 1,
                perPage: @js($perPage),
                get filteredImageOptions() {
                    const query = this.imageSearch.trim().toLowerCase();

                    return Array.from(this.$root.querySelectorAll('[data-image-option]')).filter((option) => {
                        return ! query || (option.dataset.imageSearch ?? '').includes(query);
                    });
                },
                get totalPages() {
                    return Math.max(1, Math.ceil(this.filteredImageOptions.length / this.perPage));
                },
                isImageOptionVisible(option) {
                    const index = this.filteredImageOptions.indexOf(option);

                    return index >= ((this.page - 1) * this.perPage) && index < (this.page * this.perPage);
                },
                setPage(page) {
                    this.page = Math.min(Math.max(page, 1), this.totalPages);
                },
            }"
            x-init="$watch('imageSearch', () => { page = 1 })"
        >
            @if (! $isDisabled && $isSearchable)
                <x-filament::input.wrapper
                    inline-prefix
                    :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                    :prefix-icon-alias="\Filament\Forms\View\FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD"
                    class="fi-fo-checkbox-list-search-input-wrp"
                >
                    <input
                        placeholder="{{ $getSearchPrompt() }}"
                        type="search"
                        x-model.debounce.{{ $getSearchDebounce() }}="imageSearch"
                        class="fi-input fi-input-has-inline-prefix"
                    />
                </x-filament::input.wrapper>
            @endif

            @if (! $isDisabled && $isBulkToggleable && count($options))
                <div
                    x-cloak
                    class="fi-fo-checkbox-list-actions"
                    wire:key="{{ $livewireKey }}.actions"
                >
                    <span
                        x-show="! areAllCheckboxesChecked"
                        x-on:click="toggleAllCheckboxes()"
                        wire:key="{{ $livewireKey }}.actions.select-all"
                    >
                        {{ $getAction('selectAll') }}
                    </span>

                    <span
                        x-show="areAllCheckboxesChecked"
                        x-on:click="toggleAllCheckboxes()"
                        wire:key="{{ $livewireKey }}.actions.deselect-all"
                    >
                        {{ $getAction('deselectAll') }}
                    </span>
                </div>
            @endif

            <div
                {{
                    $getExtraAttributeBag()
                        ->grid($getColumns(), $gridDirection)
                        ->class([
                            'fi-fo-checkbox-list-options',
                        ])
                }}
            >
                @forelse ($options as $value => $label)
                    <div
                        x-cloak
                        x-show="isImageOptionVisible($el)"
                        data-image-option
                        data-image-search="{{ strtolower(strip_tags($label)) }}"
                        wire:key="{{ $livewireKey }}.options.{{ $value }}"
                        class="fi-fo-checkbox-list-option-ctn"
                    >
                        <label class="fi-fo-checkbox-list-option">
                            <input
                                type="checkbox"
                                {{
                                    $extraInputAttributeBag
                                        ->merge([
                                            'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                            'value' => e($value),
                                            'wire:loading.attr' => 'disabled',
                                            $wireModelAttribute => $statePath,
                                            'x-on:change' => $isBulkToggleable ? 'checkIfAllCheckboxesAreChecked()' : null,
                                        ], escape: false)
                                        ->class([
                                            'fi-checkbox-input',
                                            'fi-valid' => ! $errors->has($statePath),
                                            'fi-invalid' => $errors->has($statePath),
                                        ])
                                }}
                            />

                            <div class="fi-fo-checkbox-list-option-text">
                                <span class="fi-fo-checkbox-list-option-label">
                                    @if ($isHtmlAllowed)
                                        {!! $label !!}
                                    @else
                                        {{ $label }}
                                    @endif
                                </span>

                                @if ($hasDescription($value))
                                    <p class="fi-fo-checkbox-list-option-description">
                                        {{ $getDescription($value) }}
                                    </p>
                                @endif
                            </div>
                        </label>
                    </div>
                @empty
                    <div wire:key="{{ $livewireKey }}.empty"></div>
                @endforelse
            </div>

            <div
                x-cloak
                x-show="imageSearch && ! filteredImageOptions.length"
                class="fi-fo-checkbox-list-no-search-results-message"
            >
                未找到匹配的图片。
            </div>

            <div
                x-cloak
                x-show="totalPages > 1"
                class="mt-4 flex items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-700"
            >
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    第 <span x-text="page"></span> / <span x-text="totalPages"></span> 页
                </span>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="setPage(page - 1)"
                        x-bind:disabled="page <= 1"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        上一页
                    </button>
                    <button
                        type="button"
                        x-on:click="setPage(page + 1)"
                        x-bind:disabled="page >= totalPages"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
