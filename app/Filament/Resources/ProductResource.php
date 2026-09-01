<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Services\MediaLibraryCatalog;
use App\Services\ProductImageResolver;
use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = '产品';

    protected static ?string $pluralModelLabel = '产品';

    protected static ?string $navigationLabel = '产品';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('产品编辑')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('product-info')
                            ->label('产品信息')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('产品信息')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('产品名称')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (?string $state, callable $set): mixed => $set('slug', Str::slug($state ?? ''))),
                                        TextInput::make('slug')
                                            ->label('网址别名')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        TextInput::make('weight')
                                            ->label('Weight')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->default(0)
                                            ->required(),
                                        RichEditor::make('subtitle')
                                            ->label('简短描述')
                                            ->columnSpanFull(),
                                        Select::make('product_category_id')
                                            ->label('产品分类')
                                            ->options(fn (): array => ProductCategoryResource::categoryOptions())
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Toggle::make('is_active')
                                            ->label('启用')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                                Section::make('默认图库')
                                    ->description('上传默认产品图库。第一张图片将作为默认主图。')
                                    ->schema([
                                        static::imageUpload(
                                            'product_config.media.gallery',
                                            'product-galleries',
                                            multiple: true,
                                            reorderable: true,
                                            maxFiles: 8,
                                        )
                                            ->label('图库图片')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('常见问题')
                                    ->schema([
                                        Repeater::make('product_config.faq')
                                            ->label('常见问题')
                                            ->schema([
                                                TextInput::make('question')
                                                    ->label('问题')
                                                    ->required()
                                                    ->maxLength(500)
                                                    ->columnSpanFull(),
                                                RichEditor::make('answer')
                                                    ->label('答案')
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->collapsible()
                                            ->reorderable()
                                            ->addActionLabel('添加常见问题')
                                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('搜索引擎优化（SEO）')
                                    ->schema([
                                        Textarea::make('meta_description')
                                            ->label('元描述')
                                            ->maxLength(65535)
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('feature-cards')
                            ->label('Feature Cards')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Cards below product image')
                                    ->description('Customize the two information cards shown below the product image. Leave a field blank to use the storefront default.')
                                    ->schema([
                                        Fieldset::make('Card 1')
                                            ->schema([
                                                TextInput::make('product_config.detail_sections.feature_cards.0.title')
                                                    ->label('Title')
                                                    ->maxLength(255),
                                                Textarea::make('product_config.detail_sections.feature_cards.0.description')
                                                    ->label('Description')
                                                    ->rows(2)
                                                    ->maxLength(1000),
                                                TextInput::make('product_config.detail_sections.feature_cards.0.tooltip_title')
                                                    ->label('Tooltip title')
                                                    ->maxLength(255),
                                                RichEditor::make('product_config.detail_sections.feature_cards.0.tooltip_content')
                                                    ->label('Tooltip content')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2),
                                        Fieldset::make('Card 2')
                                            ->schema([
                                                TextInput::make('product_config.detail_sections.feature_cards.1.title')
                                                    ->label('Title')
                                                    ->maxLength(255),
                                                Textarea::make('product_config.detail_sections.feature_cards.1.description')
                                                    ->label('Description')
                                                    ->rows(2)
                                                    ->maxLength(1000),
                                                TextInput::make('product_config.detail_sections.feature_cards.1.tooltip_title')
                                                    ->label('Tooltip title')
                                                    ->maxLength(255),
                                                RichEditor::make('product_config.detail_sections.feature_cards.1.tooltip_content')
                                                    ->label('Tooltip content')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2),
                                    ])
                                    ->columns(1),
                            ]),
                        Tab::make('options')
                            ->label('选项')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make('选项组')
                                    ->description('可添加或删除完整的选项组。所有选项均为必填，并使用第一个值作为默认值。')
                                    ->schema([
                                        Repeater::make('product_config.options')
                                            ->label('产品选项')
                                            ->schema([
                                                Hidden::make('key'),
                                                Hidden::make('row_key')
                                                    ->default(fn (): string => (string) Str::uuid()),
                                                TextInput::make('label')
                                                    ->label('选项名称')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                                        if (blank($get('key'))) {
                                                            $set('key', Str::slug($state ?? '', '_'));
                                                        }
                                                    }),
                                                Select::make('type')
                                                    ->label('输入类型')
                                                    ->options([
                                                        'select' => '单选',
                                                        'multi_select' => '多选',
                                                    ])
                                                    ->default('select')
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->live()
                                            ->afterStateUpdated(function (Repeater $component): void {
                                                $optionValueTabs = $component->getContainer()->getComponent(
                                                    'product-option-values',
                                                    withHidden: true,
                                                );

                                                if (! $optionValueTabs instanceof Tabs) {
                                                    return;
                                                }

                                                $livewire = $component->getLivewire();
                                                $optionValues = data_get(
                                                    $livewire,
                                                    'data.product_config.option_values',
                                                    [],
                                                );
                                                $needsInitialValueState = ! is_array($optionValues);

                                                foreach ($component->getRawState() ?? [] as $optionKey => $option) {
                                                    if (! is_array($option)) {
                                                        continue;
                                                    }

                                                    $optionStateKey = (string) ($option['row_key'] ?? $optionKey);

                                                    if (! array_key_exists($optionStateKey, $optionValues)) {
                                                        $needsInitialValueState = true;
                                                        break;
                                                    }
                                                }

                                                $optionValueTabs->clearCachedDefaultChildSchemas();

                                                if ($needsInitialValueState) {
                                                    $optionValueTabs->getChildSchema()->fill();

                                                    // Rebuilding the dynamic tabs fills every child repeater. Filament's
                                                    // default fill can replace existing rows with null defaults, so
                                                    // restore the values that already belonged to the earlier options.
                                                    $livewireData = data_get($livewire, 'data', []);
                                                    $filledOptionValues = data_get(
                                                        $livewireData,
                                                        'product_config.option_values',
                                                        [],
                                                    );

                                                    if (is_array($livewireData) && is_array($filledOptionValues)) {
                                                        $livewireData['product_config']['option_values'] = array_replace(
                                                            $filledOptionValues,
                                                            is_array($optionValues) ? $optionValues : [],
                                                        );
                                                        $livewire->data = $livewireData;
                                                    }
                                                }
                                            })
                                            ->reorderable()
                                            ->collapsible()
                                            ->addActionLabel('添加选项')
                                            ->itemLabel(fn (array $state): string => $state['label'] ?? '新选项')
                                            ->columnSpanFull(),
                                        Placeholder::make('product_option_values_help')
                                            ->label('选项值')
                                            ->content('请先添加一个产品选项。添加后会立即出现对应的选项值编辑标签，无需先保存产品。')
                                            ->visible(fn (LivewireComponent $livewire): bool => ! static::hasProductOptionRows(
                                                static::productOptionsState($livewire),
                                            ))
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                        Tabs::make('选项值')
                                            ->key('product-option-values')
                                            ->tabs(fn (LivewireComponent $livewire): array => static::optionValueTabs($livewire))
                                            ->visible(fn (LivewireComponent $livewire): bool => static::hasProductOptionRows(
                                                static::productOptionsState($livewire),
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('gallery')
                            ->label('图库')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make('基于选项的图库规则')
                                    ->description(fn (LivewireComponent $livewire): string => static::hasCompleteProductOptions(
                                        static::productOptionsState($livewire),
                                    )
                                        ? '当所有已选条件匹配时，上传的主图将作为首先显示的图片。'
                                        : '请先在“选项”中添加选项并填写至少一个完整的选项值，完成后此处会自动启用。')
                                    ->disabled(fn (LivewireComponent $livewire): bool => ! static::hasCompleteProductOptions(
                                        static::productOptionsState($livewire),
                                    ))
                                    ->schema([
                                        Repeater::make('product_config.media.gallery_rules')
                                            ->label('图库规则')
                                            ->schema([
                                                TextInput::make('id')
                                                    ->label('规则编号')
                                                    ->required()
                                                    ->maxLength(120),
                                                Repeater::make('match_conditions')
                                                    ->label('匹配条件')
                                                    ->schema(static::conditionFields())
                                                    ->columns(2)
                                                    ->reorderable()
                                                    ->addActionLabel('添加条件')
                                                    ->columnSpanFull(),
                                                static::imageUpload(
                                                    'primary',
                                                    'product-galleries/rules',
                                                    required: true,
                                                    helperText: '该图片也会作为匹配图库中的第一张图片。',
                                                )
                                                    ->label('主图')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->reorderable()
                                            ->addActionLabel('添加图库规则')
                                            ->itemLabel(fn (array $state): ?string => $state['id'] ?? null)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('pricing')
                            ->label('价格')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Hidden::make('product_config.pricing.mode')
                                    ->default('rule_based'),
                                Hidden::make('product_config.pricing.currency')
                                    ->default('USD'),
                                Hidden::make('product_config.pricing.total_rounding')
                                    ->default('nearest_integer'),
                                Section::make('基于条件的价格')
                                    ->description(fn (LivewireComponent $livewire): string => static::hasCompleteProductOptions(
                                        static::productOptionsState($livewire),
                                    )
                                        ? '为每种选项组合添加一个 JSON 价格对象。系统使用第一个匹配的规则；条件留空可作为备用规则。'
                                        : '请先在“选项”中添加选项并填写至少一个完整的选项值，完成后此处会自动启用。')
                                    ->disabled(fn (LivewireComponent $livewire): bool => ! static::hasCompleteProductOptions(
                                        static::productOptionsState($livewire),
                                    ))
                                    ->schema([
                                        Repeater::make('product_config.pricing.rules')
                                            ->label('价格规则')
                                            ->schema([
                                                TextInput::make('id')
                                                    ->label('规则编号')
                                                    ->required()
                                                    ->maxLength(120),
                                                Repeater::make('match_conditions')
                                                    ->label('匹配条件')
                                                    ->schema(static::conditionFields())
                                                    ->columns(2)
                                                    ->defaultItems(0)
                                                    ->reorderable()
                                                    ->addActionLabel('添加条件')
                                                    ->columnSpanFull(),
                                                CodeEditor::make('pricing_json')
                                                    ->label('价格 JSON')
                                                    ->language(Language::Json)
                                                    ->json()
                                                    ->required()
                                                    ->extraAttributes(['style' => 'min-height: 420px'])
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->reorderable()
                                            ->addActionLabel('添加价格规则')
                                            ->itemLabel(fn (array $state): ?string => $state['id'] ?? null)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Tab>
     */
    protected static function optionValueTabs(LivewireComponent $livewire): array
    {
        $options = static::productOptionsState($livewire);

        if (! is_array($options)) {
            return [];
        }

        $tabs = [];

        foreach ($options as $optionKey => $option) {
            if (! is_array($option)) {
                continue;
            }

            $label = trim((string) ($option['label'] ?? ''));
            $label = $label !== '' ? $label : '选项 '.((string) $optionKey);
            $optionStateKey = (string) ($option['row_key'] ?? $optionKey);

            if ($optionStateKey === '') {
                $optionStateKey = (string) $optionKey;
            }

            $tabs[] = Tab::make($optionStateKey)
                ->label($label)
                ->schema([
                    Section::make("{$label} 选项值")
                        ->description('第一个选项值将自动作为默认值。')
                        ->schema([
                            Repeater::make("product_config.option_values.{$optionStateKey}")
                                ->label('选项值')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('色卡标题')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->maxLength(255)
                                        ->afterStateUpdated(function (?string $state, TextInput $component): void {
                                            $codePath = (string) str($component->getStatePath())
                                                ->beforeLast('.')
                                                ->append('.code');
                                            $livewire = $component->getLivewire();

                                            data_set(
                                                $livewire,
                                                $codePath,
                                                Str::slug($state ?? '', '_'),
                                            );
                                        }),
                                    TextInput::make('code')
                                        ->label('色卡编码')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->maxLength(120),
                                    Textarea::make('description')
                                        ->label('色卡描述')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    static::imageUpload('swatch_image', 'product-options/swatches')
                                        ->label('色卡图片')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(1)
                                ->live()
                                ->collapsible()
                                ->reorderable()
                                ->minItems(1)
                                ->addActionLabel('添加选项值')
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['code'] ?? null)
                                ->columnSpanFull(),
                        ]),
                ]);
        }

        return $tabs;
    }

    protected static function hasProductOptionRows(mixed $options): bool
    {
        return is_array($options) && $options !== [];
    }

    /**
     * The options repeater owns only the option label and type. The values
     * are rendered by a sibling dynamic Tabs component, so the repeater's
     * dehydrated state does not include them.
     */
    protected static function productOptionsState(LivewireComponent $livewire): mixed
    {
        $options = data_get($livewire, 'data.product_config.options');

        if (! is_array($options)) {
            return $options;
        }

        $optionValues = data_get($livewire, 'data.product_config.option_values', []);

        if (! is_array($optionValues)) {
            $optionValues = [];
        }

        foreach ($options as $optionKey => &$option) {
            if (! is_array($option)) {
                continue;
            }

            $optionStateKey = (string) ($option['row_key'] ?? $optionKey);

            if (array_key_exists($optionStateKey, $optionValues)) {
                $option['values'] = $optionValues[$optionStateKey];
            } elseif (! array_key_exists('values', $option)) {
                $option['values'] = [];
            }
        }
        unset($option);

        return $options;
    }

    protected static function hasCompleteProductOptions(mixed $options): bool
    {
        if (! static::hasProductOptionRows($options)) {
            return false;
        }

        foreach ($options as $option) {
            if (! is_array($option) || blank($option['label'] ?? null)) {
                return false;
            }

            $values = $option['values'] ?? null;

            if (! is_array($values) || $values === []) {
                return false;
            }

            foreach ($values as $value) {
                if (
                    ! is_array($value)
                    || blank($value['label'] ?? null)
                    || blank($value['code'] ?? null)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array<int, mixed>
     */
    protected static function conditionFields(): array
    {
        return [
            Select::make('option')
                ->label('选项')
                ->options(fn (LivewireComponent $livewire): array => static::optionGroupOptions(
                    static::productOptionsState($livewire),
                ))
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('value', null))
                ->required(),
            Select::make('value')
                ->label('值')
                ->options(fn (Get $get, LivewireComponent $livewire): array => static::optionValueOptions(
                    static::productOptionsState($livewire),
                    $get('option'),
                ))
                ->searchable()
                ->required(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function optionGroupOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $result = [];

        foreach ($options as $key => $option) {
            if (! is_array($option)) {
                continue;
            }

            $optionKey = (string) ($option['key'] ?? $option['label'] ?? $key);
            $optionKey = Str::slug($optionKey, '_');

            if ($optionKey === '') {
                $optionKey = Str::slug((string) ($option['label'] ?? ''), '_');
            }

            if ($optionKey === '') {
                continue;
            }

            $result[$optionKey] = (string) ($option['label'] ?? Str::headline($optionKey));
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    protected static function optionValueOptions(mixed $options, mixed $selectedKey): array
    {
        if (! is_array($options) || blank($selectedKey)) {
            return [];
        }

        foreach ($options as $key => $option) {
            if (! is_array($option)) {
                continue;
            }

            $optionKey = Str::slug(
                (string) ($option['key'] ?? $option['label'] ?? $key),
                '_',
            );

            if ($optionKey === '') {
                $optionKey = Str::slug((string) ($option['label'] ?? ''), '_');
            }

            if ($optionKey !== Str::slug((string) $selectedKey, '_')) {
                continue;
            }

            $values = [];

            foreach (is_array($option['values'] ?? null) ? $option['values'] : [] as $value) {
                if (! is_array($value)) {
                    continue;
                }

                $code = (string) ($value['code'] ?? '');

                if ($code !== '') {
                    $values[$code] = (string) ($value['label'] ?? $code);
                }
            }

            return $values;
        }

        return [];
    }

    protected static function imageUpload(
        string $name,
        string $directory,
        bool $multiple = false,
        bool $reorderable = false,
        ?int $maxFiles = null,
        bool $required = false,
        ?string $helperText = null,
    ): Fieldset {
        $upload = FileUpload::make($name)
            ->hiddenLabel()
            ->image()
            ->acceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->fetchFileInformation(false)
            ->preventFilePathTampering(false)
            ->required($required)
            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                return app(ProductImageUploadService::class)->store(
                    $file,
                    $component->getDirectory() ?? '',
                    $component->getDiskName(),
                    $component->getVisibility(),
                );
            })
            ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                if (Str::startsWith(ltrim($file), '<svg')) {
                    return [
                        'name' => 'swatch.svg',
                        'size' => 0,
                        'type' => 'image/svg+xml',
                        'url' => 'data:image/svg+xml,'.rawurlencode($file),
                    ];
                }

                if (Str::startsWith($file, ['http://', 'https://', '//', '/'])) {
                    return [
                        'name' => basename((string) parse_url($file, PHP_URL_PATH)),
                        'size' => 0,
                        'type' => 'image/*',
                        'url' => $file,
                    ];
                }

                $uploadedFile = $component->getUploadedFile($file, $storedFileNames);

                if ($uploadedFile === null) {
                    return null;
                }

                // Keep local previews on the same host and port as the
                // Filament page, using the original until WebP is ready.
                $uploadedFile['url'] = app(ProductImageResolver::class)->url($file);

                return $uploadedFile;
            });

        if ($helperText !== null) {
            $upload->helperText($helperText);
        }

        if ($multiple) {
            $upload->multiple();
        }

        if ($reorderable) {
            $upload->reorderable();
        }

        if ($maxFiles !== null) {
            $upload->maxFiles($maxFiles);
        }

        $modalUpload = FileUpload::make('new_images')
            ->label('上传新图片')
            ->image()
            ->acceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->fetchFileInformation(false)
            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                return app(ProductImageUploadService::class)->store(
                    $file,
                    $component->getDirectory() ?? '',
                    $component->getDiskName(),
                    $component->getVisibility(),
                );
            })
            ->helperText('新上传的图片会在确认后自动选中，并在下次打开时显示在已上传图片列表中。');

        if ($multiple) {
            $modalUpload->multiple();
        }

        if ($maxFiles !== null) {
            $modalUpload->maxFiles($maxFiles);
        }

        $existingPicker = FormActions::make([
            Actions\Action::make('selectExistingImages')
                ->label('选择已有图片')
                ->icon('heroicon-o-photo')
                ->modalHeading('选择已有图片')
                ->modalDescription($multiple
                    ? '勾选要加入图库的图片，确认后会替换当前选择。也可以继续上传新图片。'
                    : '勾选要使用的图片，确认后会填入当前图片字段。也可以继续上传新图片。')
                ->modalWidth('5xl')
                ->fillForm(function ($schemaGet) use ($name, $multiple): array {
                    $current = $schemaGet($name);
                    $current = is_array($current)
                        ? array_values(array_filter($current))
                        : (filled($current) ? [(string) $current] : []);

                    return [
                        'new_images' => $multiple ? [] : null,
                        'images' => $current,
                    ];
                })
                ->schema([
                    $modalUpload,
                    CheckboxList::make('images')
                        ->label('图片')
                        ->options(fn (): array => static::existingImageOptions(withPreview: true))
                        ->view('filament.forms.components.paginated-checkbox-list')
                        ->allowHtml()
                        ->columns(4)
                        ->searchable()
                        ->bulkToggleable()
                        ->maxItems($multiple ? $maxFiles : 1)
                        ->helperText('显示全部已上传图片；转换状态会在重新打开窗口或刷新页面后更新。'),
                ])
                ->action(function (array $data, $schemaSet) use ($name, $multiple, $maxFiles): void {
                    $uploaded = array_values(array_filter(
                        Arr::wrap($data['new_images'] ?? []),
                        fn (mixed $image): bool => is_string($image) && $image !== '',
                    ));
                    $selected = array_values(array_filter($data['images'] ?? []));

                    if (! $multiple) {
                        $image = $uploaded[0] ?? $selected[0] ?? null;

                        if ($image !== null) {
                            $schemaSet($name, $image);
                        }

                        return;
                    }

                    $images = array_values(array_unique([...$uploaded, ...$selected]));

                    if ($maxFiles !== null) {
                        $images = array_slice($images, 0, $maxFiles);
                    }

                    if ($images !== []) {
                        $schemaSet($name, $images);
                    }
                }),
        ])->key('existing-image-picker-'.Str::slug($name))->fullWidth();

        return Fieldset::make()->schema([$existingPicker, $upload])->columns(1);
    }

    /**
     * @return array<string, string>
     */
    protected static function existingImageOptions(bool $withPreview = false): array
    {
        $assets = app(MediaLibraryCatalog::class)->assets();
        $options = [];

        foreach ($assets as $asset) {
            $file = is_string($asset['source_path'] ?? null)
                ? $asset['source_path']
                : ($asset['primary_path'] ?? null);

            if (! is_string($file) || $file === '') {
                continue;
            }

            $basename = (string) ($asset['name'] ?? basename($file));
            $previewUrl = (string) ($asset['url'] ?? app(ProductImageResolver::class)->url($file));
            $status = (string) ($asset['conversion_status'] ?? ProductImagePolicy::STATUS_ORIGINAL);
            $statusLabel = (string) ($asset['conversion_status_label'] ?? '原图');
            $label = e($basename.' — '.$file.' — '.$statusLabel);

            $options[$file] = $withPreview
                ? '<div class="image-picker-card-content">'
                    .'<div class="image-picker-card-preview">'
                    .'<img src="'.e($previewUrl).'" alt="'.e($basename).'" class="image-picker-card-image" loading="lazy">'
                    .'<span class="image-picker-card-status" data-status="'.e($status).'">'.e($statusLabel).'</span>'
                    .'</div>'
                    .'<div class="image-picker-card-caption">'
                    .'<span class="image-picker-card-name" title="'.e($basename).'">'.e($basename).'</span>'
                    .'<span class="image-picker-card-path" title="'.e($file).'">'.e($file).'</span>'
                    .'</div>'
                    .'</div>'
                : $label;
        }

        return $options;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('产品名称')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('产品分类')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Weight')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('启用')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('产品分类')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('启用'),
            ])
            ->actions([
                Actions\EditAction::make()->label('编辑'),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->label('删除所选'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
