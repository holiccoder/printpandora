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
            position: relative;
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

        .image-picker-card-status {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            display: inline-flex;
            align-items: center;
            min-height: 1.5rem;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            color: var(--gray-700);
            background: rgb(255 255 255 / 90%);
            box-shadow: 0 1px 3px rgb(0 0 0 / 14%);
            font-size: 0.6875rem;
            font-weight: 700;
            line-height: 1rem;
            backdrop-filter: blur(4px);
        }

        .image-picker-card-status[data-status='processing'] {
            color: #92400e;
            background: rgb(254 243 199 / 94%);
        }

        .image-picker-card-status[data-status='ready'] {
            color: #166534;
            background: rgb(220 252 231 / 94%);
        }

        .image-picker-card-status[data-status='failed'] {
            color: #991b1b;
            background: rgb(254 226 226 / 94%);
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

        .image-picker-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1.25rem;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.875rem;
            background: linear-gradient(135deg, white 0%, var(--gray-50) 100%);
            box-shadow: 0 1px 2px rgb(0 0 0 / 4%);
        }

        .image-picker-pagination-summary,
        .image-picker-pagination-count {
            display: inline-flex;
            align-items: center;
            color: var(--gray-600);
            font-size: 0.8125rem;
            line-height: 1.25rem;
        }

        .image-picker-pagination-summary {
            gap: 0.375rem;
        }

        .image-picker-pagination-count {
            margin-left: 0.5rem;
            padding-left: 0.75rem;
            border-left: 1px solid var(--gray-200);
            color: var(--gray-500);
        }

        .image-picker-pagination-count-value {
            margin: 0 0.25rem;
            color: var(--gray-700);
            font-weight: 600;
        }

        .image-picker-pagination-current {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 0.5rem;
            border: 1px solid var(--primary-200, var(--gray-300));
            border-radius: 0.625rem;
            background: var(--primary-50, var(--gray-100));
            color: var(--primary-700, var(--gray-900));
            font-weight: 700;
            box-shadow: 0 1px 2px rgb(0 0 0 / 5%);
        }

        .image-picker-pagination-controls {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .image-picker-pagination-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.5rem;
            padding: 0.5rem 0.875rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.625rem;
            background: white;
            color: var(--gray-700);
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1rem;
            box-shadow: 0 1px 2px rgb(0 0 0 / 5%);
            transition: color 150ms ease, border-color 150ms ease, background 150ms ease,
                box-shadow 150ms ease, transform 150ms ease;
        }

        .image-picker-pagination-button:hover:not(:disabled) {
            border-color: var(--primary-400, var(--gray-400));
            background: var(--primary-50, var(--gray-50));
            color: var(--primary-700, var(--gray-900));
            box-shadow: 0 4px 10px rgb(0 0 0 / 8%);
            transform: translateY(-1px);
        }

        .image-picker-pagination-button:active:not(:disabled) {
            box-shadow: 0 1px 2px rgb(0 0 0 / 5%);
            transform: translateY(0);
        }

        .image-picker-pagination-button:focus-visible {
            outline: 2px solid var(--primary-500);
            outline-offset: 2px;
        }

        .image-picker-pagination-button:disabled {
            cursor: not-allowed;
            border-color: var(--gray-200);
            background: var(--gray-100);
            color: var(--gray-400);
            box-shadow: none;
        }

        .image-picker-pagination-arrow {
            font-size: 1rem;
            line-height: 1;
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

        .dark .image-picker-pagination {
            border-color: var(--gray-700);
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-950) 100%);
            box-shadow: 0 1px 2px rgb(0 0 0 / 25%);
        }

        .dark .image-picker-pagination-summary {
            color: var(--gray-300);
        }

        .dark .image-picker-pagination-count {
            border-left-color: var(--gray-700);
            color: var(--gray-400);
        }

        .dark .image-picker-pagination-count-value {
            color: var(--gray-200);
        }

        .dark .image-picker-pagination-current {
            border-color: var(--primary-500, var(--gray-600));
            background: rgb(255 255 255 / 7%);
            color: var(--primary-300, white);
            box-shadow: none;
        }

        .dark .image-picker-pagination-button {
            border-color: var(--gray-600);
            background: var(--gray-800);
            color: var(--gray-200);
            box-shadow: none;
        }

        .dark .image-picker-pagination-button:hover:not(:disabled) {
            border-color: var(--primary-400, var(--gray-500));
            background: var(--gray-700);
            color: white;
        }

        .dark .image-picker-pagination-button:disabled {
            border-color: var(--gray-800);
            background: var(--gray-900);
            color: var(--gray-600);
        }

        @media (max-width: 40rem) {
            .image-picker-pagination {
                align-items: stretch;
                flex-direction: column;
                padding: 0.75rem;
            }

            .image-picker-pagination-summary {
                justify-content: center;
            }

            .image-picker-pagination-controls {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .image-picker-pagination-button {
                width: 100%;
            }
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
                x-transition.opacity.duration.150ms
                class="image-picker-pagination"
            >
                <span class="image-picker-pagination-summary" aria-live="polite">
                    <span>第</span>
                    <strong class="image-picker-pagination-current" x-text="page"></strong>
                    <span>/ <span x-text="totalPages"></span> 页</span>
                    <span class="image-picker-pagination-count">
                        共 <span class="image-picker-pagination-count-value" x-text="filteredImageOptions.length"></span> 张
                    </span>
                </span>

                <div class="image-picker-pagination-controls">
                    <button
                        type="button"
                        x-on:click="setPage(page - 1)"
                        x-bind:disabled="page <= 1"
                        x-bind:aria-disabled="page <= 1"
                        class="image-picker-pagination-button"
                    >
                        <span aria-hidden="true" class="image-picker-pagination-arrow">←</span>
                        <span>上一页</span>
                    </button>
                    <button
                        type="button"
                        x-on:click="setPage(page + 1)"
                        x-bind:disabled="page >= totalPages"
                        x-bind:aria-disabled="page >= totalPages"
                        class="image-picker-pagination-button"
                    >
                        <span>下一页</span>
                        <span aria-hidden="true" class="image-picker-pagination-arrow">→</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
