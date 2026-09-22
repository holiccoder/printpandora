<?php

namespace App\Services;

use App\Models\Product;
use App\Support\BusinessCardOptionCatalog;
use App\Support\ClassicSpecialBusinessCardTexture;
use App\Support\HardcodedContent;
use App\Support\SolidQualityBusinessCardGallery;
use App\Support\StandardQualityBusinessCardGallery;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reads, writes, and adapts the canonical product configuration JSON.
 *
 * The new configuration is stored in products.product_config. During the
 * migration period this service can still read the old product_options JSON
 * or the category product-option files. The storefront keeps product copy,
 * pricing, FAQs, and product-specific detail data in the database, while
 * shared business-card cross-sell sections come from hardcoded content and
 * legacy product files can provide only option metadata and galleries.
 */
class ProductConfigurationService
{
    public function __construct(
        private HardcodedContent $content,
        private ProductImageResolver $imageResolver,
    ) {}

    /**
     * @var array<string, string>
     */
    public const OPTION_GROUP_LABELS = [
        'sizes' => 'Size',
        'corners' => 'Corners',
        'texture' => 'Texture',
        'paper_finish' => 'Paper Finish',
        'uv_finish' => 'UV',
        'special_finish' => 'Special Finish',
        'print_code' => 'Print Code',
        'drill' => 'Drilling',
    ];

    /**
     * Product option groups share one storefront order. Any other option
     * groups keep their original relative order after these groups.
     *
     * @var array<string, int>
     */
    private const OPTION_GROUP_ORDER = [
        'sizes' => 1,
        'size' => 1,
        'corners' => 2,
        'corner' => 2,
        'texture' => 3,
        'paper_finish' => 4,
        'uv_finish' => 5,
        'special_finish' => 6,
    ];

    private const UV_FINISH_SWATCH_IMAGE = '/images/product-options/uv-swatch.png';

    /**
     * @var array<int, string>
     */
    public const PRICING_SCENARIOS = [
        'rectangle',
        'uv',
        'square',
        'square_uv',
    ];

    /**
     * These products retain the shared Gang Run Printing review detail. PVC
     * products are included by their category so newly added PVC products
     * inherit the same behavior automatically.
     *
     * @var array<int, string>
     */
    private const GANG_RUN_PRINTING_PRODUCT_SLUGS = [
        'classic-standard-business-cards',
        'classic-special-business-cards',
    ];

    /**
     * Foil option images are shared across business-card products. The
     * source artwork lives with the classic solid card product, but the
     * storefront should show the same primary image wherever the same foil
     * option is available.
     *
     * @var array<string, string>
     */
    private const SHARED_BUSINESS_CARD_FOIL_IMAGES = [
        'black_gold' => '/images/products/classic-solid/user-hot-black-gold.png',
        'blue_gold' => '/images/products/classic-solid/user-hot-blue-gold.png',
        'bright_gold' => '/images/products/classic-solid/user-hot-bright-gold.png',
        'bright_silver' => '/images/products/classic-solid/user-hot-bright-silver.png',
        'green_gold' => '/images/products/classic-solid/user-hot-green-gold.png',
        'matte_gold' => '/images/products/classic-solid/user-hot-matte-gold.png',
        'matte_silver' => '/images/products/classic-solid/user-hot-matte-silver.png',
        'red_gold' => '/images/products/classic-solid/user-hot-red-gold.png',
        'rose_gold' => '/images/products/classic-solid/user-hot-rose-gold.png',
        'cold_matte_gold' => '/images/products/classic-solid/user-cold-matte-gold.png',
        'cold_matte_silver' => '/images/products/classic-solid/user-cold-matte-silver.png',
        'cold_bright_gold' => '/images/products/classic-solid/user-cold-bright-gold.png',
        'cold_bright_silver' => '/images/products/classic-solid/user-cold-bright-silver.png',
        'cold_red_gold' => '/images/products/classic-solid/user-cold-red-gold.png',
        'cold_green_gold' => '/images/products/classic-solid/user-cold-green-gold.png',
        'cold_blue_gold' => '/images/products/classic-solid/user-cold-blue-gold.png',
    ];

    /**
     * Return the state used by the Filament configuration form.
     *
     * Detail sections are deliberately removed from the form state. They
     * remain in the stored configuration and are preserved on save.
     *
     * @return array<string, mixed>
     */
    public function formState(Product $product): array
    {
        $config = $this->canonicalConfig($product);

        $scenarios = is_array($config['pricing']['scenarios'] ?? null)
            ? $config['pricing']['scenarios']
            : [];

        foreach ($scenarios as $scenarioKey => &$scenario) {
            if (! is_array($scenario)) {
                continue;
            }

            $scenario['quantity_discounts'] = $this->mapToRows(
                is_array($scenario['quantity_discounts_percent'] ?? null)
                    ? $scenario['quantity_discounts_percent']
                    : [],
            );
            unset($scenario['quantity_discounts_percent']);

            foreach ($scenario['processes'] ?? [] as &$process) {
                if (! is_array($process)) {
                    continue;
                }

                $process['quantity_discounts'] = $this->mapToRows(
                    is_array($process['quantity_discounts_percent'] ?? null)
                        ? $process['quantity_discounts_percent']
                        : [],
                );
                unset($process['quantity_discounts_percent']);
            }
            unset($process);
        }
        unset($scenario);

        $config['pricing']['scenarios'] = $scenarios;

        unset($config['detail_sections']);

        return $config;
    }

    /**
     * Return the state used by the main Product create/edit form.
     *
     * Unlike the legacy configuration page, this state represents option
     * groups as a repeater list and pricing as condition + JSON rules.
     *
     * @return array<string, mixed>
     */
    public function resourceFormState(Product $product): array
    {
        $config = $this->canonicalConfig($product);

        return [
            'name' => (string) data_get($config, 'product.name', $product->name),
            'slug' => (string) data_get($config, 'product.slug', $product->slug),
            'subtitle' => data_get($config, 'product.subtitle', $product->subtitle),
            'meta_description' => data_get($config, 'product.meta_description', $product->meta_description),
            'product_category_id' => $product->product_category_id,
            'is_active' => $product->is_active,
            'product_config' => [
                'options' => $this->resourceOptionsFromCanonical($config['options'] ?? []),
                'option_values' => $this->resourceOptionValuesFromCanonical($config['options'] ?? []),
                'media' => [
                    'gallery' => is_array(data_get($config, 'media.gallery'))
                        ? array_values(data_get($config, 'media.gallery'))
                        : [],
                    'gallery_rules' => $this->resourceGalleryRulesFromCanonical(
                        data_get($config, 'media.gallery_rules', []),
                        $config['options'] ?? [],
                    ),
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'currency' => data_get($config, 'pricing.currency', 'USD'),
                    'total_rounding' => data_get($config, 'pricing.total_rounding', 'nearest_integer'),
                    'rules' => $this->resourcePricingRulesFromCanonical($config),
                ],
                'faq' => is_array($config['faq'] ?? null) ? array_values($config['faq']) : [],
                'detail_sections' => [
                    'feature_cards' => $this->resourceFeatureCardsFromCanonical($config),
                ],
            ],
        ];
    }

    /**
     * Convert the main Product form state into the canonical save state.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resourceStateFromProductForm(array $data): array
    {
        $resource = is_array($data['product_config'] ?? null) ? $data['product_config'] : [];
        $product = is_array($resource['product'] ?? null) ? $resource['product'] : [];

        $optionRows = $this->mergeResourceOptionValues(
            $resource['options'] ?? [],
            $resource['option_values'] ?? [],
        );

        $product = array_replace($product, [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'subtitle' => $data['subtitle'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ]);

        return [
            'product' => $product,
            'options' => $optionRows,
            'media' => $resource['media'] ?? [],
            'pricing' => $resource['pricing'] ?? [],
            'faq' => $resource['faq'] ?? [],
            'detail_sections' => $resource['detail_sections'] ?? [],
        ];
    }

    /**
     * Normalize and save the main Product form state.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveResource(Product $product, array $data): Product
    {
        $state = $this->resourceStateFromProductForm($data);

        $state['options'] = $this->optionsFromResourceRows($state['options'] ?? []);

        $media = is_array($state['media'] ?? null) ? $state['media'] : [];
        $media['gallery'] = is_array($media['gallery'] ?? null) ? array_values($media['gallery']) : [];
        $media['gallery_rules'] = $this->galleryRulesFromResourceRows($media['gallery_rules'] ?? []);
        $state['media'] = $media;

        $pricing = is_array($state['pricing'] ?? null) ? $state['pricing'] : [];
        $state['pricing'] = [
            'mode' => 'rule_based',
            'currency' => (string) ($pricing['currency'] ?? 'USD'),
            'total_rounding' => (string) ($pricing['total_rounding'] ?? 'nearest_integer'),
            'rules' => $this->pricingRulesFromResourceRows($pricing['rules'] ?? []),
            'scenarios' => [],
            'quantity_price_table' => [],
        ];

        return $this->save($product, $state);
    }

    /**
     * Save the form state while preserving configuration sections that are
     * intentionally not exposed by the form, such as detail_sections.
     *
     * @param  array<string, mixed>  $state
     */
    public function save(Product $product, array $state): Product
    {
        $existing = $this->canonicalConfig($product);
        $config = $existing;

        $config['product'] = array_replace(
            is_array($existing['product'] ?? null) ? $existing['product'] : [],
            is_array($state['product'] ?? null) ? $state['product'] : [],
        );

        if (array_key_exists('options', $state)) {
            $config['options'] = is_array($state['options']) ? $state['options'] : [];
        }

        $config['media'] = array_replace(
            is_array($existing['media'] ?? null) ? $existing['media'] : [],
            is_array($state['media'] ?? null) ? $state['media'] : [],
        );

        $existingPricing = is_array($existing['pricing'] ?? null) ? $existing['pricing'] : [];
        $statePricing = is_array($state['pricing'] ?? null) ? $state['pricing'] : [];
        $config['pricing'] = array_replace($existingPricing, $statePricing);
        if (array_key_exists('scenarios', $statePricing)) {
            $config['pricing']['scenarios'] = $this->mergeScenarioState(
                is_array($existingPricing['scenarios'] ?? null) ? $existingPricing['scenarios'] : [],
                is_array($statePricing['scenarios'] ?? null) ? $statePricing['scenarios'] : [],
            );
        } elseif (array_key_exists('rules', $statePricing)) {
            $config['pricing']['scenarios'] = [];
        }

        if (array_key_exists('faq', $state)) {
            $config['faq'] = array_values(is_array($state['faq']) ? $state['faq'] : []);
        }

        if (array_key_exists('detail_sections', $state)) {
            $existingDetails = is_array($existing['detail_sections'] ?? null)
                ? $existing['detail_sections']
                : [];
            $stateDetails = is_array($state['detail_sections'] ?? null)
                ? $state['detail_sections']
                : [];

            $config['detail_sections'] = array_replace($existingDetails, $stateDetails);
        }

        $config = $this->normalizeCanonicalConfig($config, $product);

        $product->forceFill([
            'product_config' => $config,
            'name' => (string) data_get($config, 'product.name', $product->name),
            'slug' => (string) data_get($config, 'product.slug', $product->slug),
            'subtitle' => data_get($config, 'product.subtitle'),
            'description' => data_get($config, 'product.description'),
            'meta_description' => data_get($config, 'product.meta_description'),
        ])->save();

        return $product->refresh();
    }

    /**
     * Keep the searchable Product columns in sync when an administrator edits
     * the regular Product Edit page after a canonical configuration exists.
     */
    public function syncProductProjection(Product $product): void
    {
        if (! $this->hasCanonicalConfig($product)) {
            return;
        }

        $config = $this->canonicalConfig($product);
        $config['product']['name'] = $product->name;
        $config['product']['subtitle'] = $product->subtitle;
        $config['product']['description'] = $product->description;
        $config['product']['description_title'] = $product->description_title;
        $config['product']['bullet_points'] = $product->bullet_points ?? [];
        $config['product']['meta_description'] = $product->meta_description;

        $product->forceFill(['product_config' => $config])->saveQuietly();
    }

    /**
     * Return the canonical database configuration for editing.
     *
     * Legacy repository files contribute only option metadata and galleries.
     * If an old product_options column exists, its pricing and detail data
     * remain eligible for this one-time database compatibility path.
     *
     * @return array<string, mixed>
     */
    public function canonicalConfig(Product $product): array
    {
        if ($this->hasCanonicalConfig($product)) {
            $config = $this->normalizeCanonicalConfig($product->product_config ?? [], $product);

            $config['options'] = $this->normalizeProductSpecificOptions($config['options'], $product);
            $config['media']['gallery_rules'] = $this->withSharedBusinessCardFoilGalleryRules(
                is_array($config['media']['gallery_rules'] ?? null)
                    ? $config['media']['gallery_rules']
                    : [],
                $config['options'],
                (string) $product->slug,
            );

            return $config;
        }

        $legacy = $this->databaseLegacyOptions($product);

        return $this->fromLegacyOptions(
            $product,
            $legacy ?? $this->loadHardcodedProductOptions($product) ?? [],
            $legacy !== null,
        );
    }

    public function hasLegacyConfiguration(Product $product): bool
    {
        return $this->loadLegacyOptions($product) !== null;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private function resourceOptionsFromCanonical(array $options): array
    {
        $rows = [];

        foreach ($options as $key => $group) {
            if (! is_array($group)) {
                continue;
            }

            $isMultiSelect = ($group['type'] ?? 'select') === 'multi_select'
                || ((string) $key === 'special_finish'
                    && $this->hasFoilOptionValues($group['values'] ?? []));

            $rows[] = [
                'row_key' => (string) $key,
                'key' => (string) $key,
                'label' => (string) ($group['label'] ?? Str::headline((string) $key)),
                'type' => $isMultiSelect ? 'multi_select' : 'select',
            ];
        }

        return $rows;
    }

    /**
     * Keep option values in a separate Filament state branch. The option
     * repeater and the dynamic option-value repeaters are sibling components,
     * so sharing `product_config.options.*.values` would allow the parent
     * repeater to overwrite the child values during a Livewire update.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function resourceOptionValuesFromCanonical(array $options): array
    {
        $values = [];

        foreach ($options as $key => $group) {
            if (! is_array($group)) {
                continue;
            }

            $values[(string) $key] = array_values(
                is_array($group['values'] ?? null) ? $group['values'] : [],
            );
        }

        return $values;
    }

    /**
     * Return the two image-adjacent feature cards for the Product resource.
     * Existing products fall back to the current global product-detail copy so
     * the new fields are immediately useful when an administrator opens them.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, string>>
     */
    private function resourceFeatureCardsFromCanonical(array $config): array
    {
        $stored = data_get($config, 'detail_sections.feature_cards', []);
        $stored = is_array($stored) ? array_values($stored) : [];
        $defaults = $this->defaultFeatureCards();
        $cards = [];

        for ($index = 0; $index < 2; $index++) {
            $storedCard = is_array($stored[$index] ?? null) ? $stored[$index] : [];
            $defaultCard = $defaults[$index] ?? [
                'title' => '',
                'description' => '',
                'tooltip_title' => '',
                'tooltip_content' => '',
            ];

            $cards[] = [
                'title' => array_key_exists('title', $storedCard)
                    ? (string) $storedCard['title']
                    : $defaultCard['title'],
                'description' => array_key_exists('description', $storedCard)
                    ? (string) $storedCard['description']
                    : $defaultCard['description'],
                'tooltip_title' => array_key_exists('tooltip_title', $storedCard)
                    ? (string) $storedCard['tooltip_title']
                    : $defaultCard['tooltip_title'],
                'tooltip_content' => array_key_exists('tooltip_content', $storedCard)
                    ? (string) $storedCard['tooltip_content']
                    : $defaultCard['tooltip_content'],
            ];
        }

        return $cards;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultFeatureCards(): array
    {
        $content = $this->content->section('product_detail_page', []);
        $chips = is_array($content['feature_chips'] ?? null) ? $content['feature_chips'] : [];
        $descriptions = is_array($content['feature_chip_descriptions'] ?? null)
            ? $content['feature_chip_descriptions']
            : [];
        $turnaround = is_array($content['turnaround_tooltip'] ?? null)
            ? $content['turnaround_tooltip']
            : [];
        $gangRun = is_array($content['gang_run_printing_tooltip'] ?? null)
            ? $content['gang_run_printing_tooltip']
            : [];

        return [
            [
                'title' => (string) ($chips[0] ?? ''),
                'description' => (string) ($descriptions[0] ?? ''),
                'tooltip_title' => (string) ($turnaround['title'] ?? ''),
                'tooltip_content' => $this->tooltipHtmlFromSections($turnaround),
            ],
            [
                'title' => (string) ($chips[1] ?? ''),
                'description' => (string) ($descriptions[1] ?? ''),
                'tooltip_title' => (string) ($gangRun['title'] ?? ''),
                'tooltip_content' => $this->tooltipHtmlFromGangRun($gangRun),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $tooltip
     */
    private function tooltipHtmlFromSections(array $tooltip): string
    {
        $html = '';

        foreach ($tooltip['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $heading = htmlspecialchars((string) ($section['heading'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $body = htmlspecialchars((string) ($section['body'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= "<p><strong>{$heading}</strong><br>{$body}</p>";
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $tooltip
     */
    private function tooltipHtmlFromGangRun(array $tooltip): string
    {
        $html = '';
        $intro = htmlspecialchars((string) ($tooltip['intro'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($intro !== '') {
            $html .= "<p>{$intro}</p>";
        }

        foreach (['pros', 'cons'] as $key) {
            $title = htmlspecialchars((string) ($tooltip[$key.'_title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $items = is_array($tooltip[$key] ?? null) ? $tooltip[$key] : [];

            if ($title !== '') {
                $html .= "<p><strong>{$title}</strong></p>";
            }

            if ($items !== []) {
                $html .= '<ul>'.implode('', array_map(
                    fn (mixed $item): string => '<li>'.htmlspecialchars((string) $item, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>',
                    $items,
                )).'</ul>';
            }
        }

        return $html;
    }

    /**
     * Merge the dynamic option-value branch back into the option rows before
     * the normal canonical option normalization runs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mergeResourceOptionValues(mixed $rows, mixed $optionValues): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $optionValues = is_array($optionValues) ? $optionValues : [];
        $merged = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowKey = trim((string) ($row['row_key'] ?? ''));
            $optionKey = Str::slug((string) ($row['key'] ?? $row['label'] ?? ''), '_');
            $values = null;

            foreach (array_unique(array_filter([
                $rowKey,
                $optionKey,
                (string) $index,
            ])) as $stateKey) {
                if (array_key_exists($stateKey, $optionValues)) {
                    $values = $optionValues[$stateKey];
                    break;
                }
            }

            if ($values !== null) {
                $row['values'] = $values;
            }

            unset($row['row_key']);
            $merged[] = $row;
        }

        return $merged;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function optionsFromResourceRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $options = [];
        $usedKeys = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $key = Str::slug((string) ($row['key'] ?? $label), '_');

            if ($key === '') {
                $key = 'option_'.($index + 1);
            }

            $baseKey = $key;
            $suffix = 2;

            while (isset($usedKeys[$key])) {
                $key = "{$baseKey}_{$suffix}";
                $suffix++;
            }

            $usedKeys[$key] = true;
            $values = [];

            foreach (is_array($row['values'] ?? null) ? $row['values'] : [] as $valueIndex => $value) {
                if (! is_array($value)) {
                    continue;
                }

                $valueLabel = trim((string) ($value['label'] ?? ''));
                $valueCode = trim((string) ($value['code'] ?? ''));

                if ($valueCode === '') {
                    $valueCode = Str::slug($valueLabel, '_') ?: 'value_'.($valueIndex + 1);
                }

                $normalizedValue = [
                    'code' => $valueCode,
                    'label' => $valueLabel,
                ];

                foreach ([
                    'description',
                    'swatch_image',
                    'width',
                    'height',
                    'min_width',
                    'max_width',
                    'min_height',
                    'max_height',
                    'area_sq_m',
                ] as $property) {
                    if (array_key_exists($property, $value) && $value[$property] !== '') {
                        $normalizedValue[$property] = $value[$property];
                    }
                }

                $values[] = $normalizedValue;
            }

            $isMultiSelect = ($row['type'] ?? 'select') === 'multi_select'
                || ($key === 'special_finish' && $this->hasFoilOptionValues($values));

            $options[$key] = [
                'label' => $label !== '' ? $label : Str::headline($key),
                'type' => $isMultiSelect ? 'multi_select' : 'select',
                'required' => true,
                'default' => $values[0]['code'] ?? null,
                'values' => $values,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resourceGalleryRulesFromCanonical(mixed $rules, mixed $options): array
    {
        if (! is_array($rules)) {
            return [];
        }

        return array_map(function (mixed $rule, int|string $index) use ($options): array {
            if (! is_array($rule)) {
                return [
                    'id' => "gallery-rule-{$index}",
                    'match_conditions' => [],
                    'primary' => null,
                ];
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            return [
                'id' => (string) ($rule['id'] ?? "gallery-rule-{$index}"),
                'match_conditions' => $this->conditionsToRowsForOptions($match, $options),
                'primary' => $rule['primary'] ?? (is_array($rule['images'] ?? null) ? ($rule['images'][0] ?? null) : null),
            ];
        }, $rules, array_keys($rules));
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    private function resourcePricingRulesFromCanonical(array $config): array
    {
        $rules = data_get($config, 'pricing.rules', []);

        if (is_array($rules) && $rules !== []) {
            return array_map(function (mixed $rule, int|string $index) use ($config): array {
                $rule = is_array($rule) ? $rule : [];
                $pricing = is_array($rule['pricing'] ?? null)
                    ? $this->normalizePricingPayload($rule['pricing'])
                    : [];

                return [
                    'id' => (string) ($rule['id'] ?? "pricing-rule-{$index}"),
                    'match_conditions' => $this->conditionsToRowsForOptions(
                        is_array($rule['match'] ?? null) ? $rule['match'] : [],
                        $config['options'] ?? [],
                    ),
                    'pricing_json' => $this->encodePricingJson($pricing),
                ];
            }, $rules, array_keys($rules));
        }

        $scenarios = data_get($config, 'pricing.scenarios', []);

        if (! is_array($scenarios)) {
            return [];
        }

        $rows = [];

        foreach ($scenarios as $scenarioKey => $scenario) {
            if (! is_array($scenario)) {
                continue;
            }

            $match = $this->scenarioMatchConditions((string) $scenarioKey, $config['options'] ?? []);
            $pricingJson = $this->encodePricingJson($this->scenarioToPricingJson($scenario));

            $rows[] = [
                'id' => "pricing-{$scenarioKey}",
                'match_conditions' => $this->conditionsToRows($match),
                'pricing_json' => $pricingJson,
            ];

            if (
                in_array((string) $scenarioKey, ['uv', 'square_uv'], true)
                && array_key_exists('uv_finish', $match)
            ) {
                $match['uv_finish'] = 'both_sides_uv';
                $rows[] = [
                    'id' => "pricing-{$scenarioKey}-both-sides",
                    'match_conditions' => $this->conditionsToRows($match),
                    'pricing_json' => $pricingJson,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function galleryRulesFromResourceRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_map(function (mixed $row, int|string $index): array {
            $row = is_array($row) ? $row : [];
            $primary = $row['primary'] ?? null;

            return [
                'id' => (string) ($row['id'] ?? "gallery-rule-{$index}"),
                'match' => $this->conditionsFromRows($row['match_conditions'] ?? []),
                'images' => filled($primary) ? [$primary] : [],
                'primary' => $primary,
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pricingRulesFromResourceRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $rules = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $json = trim((string) ($row['pricing_json'] ?? ''));
            $pricing = json_decode($json, true);

            if (! is_array($pricing)) {
                throw ValidationException::withMessages([
                    "product_config.pricing.rules.{$index}.pricing_json" => '价格 JSON 必须是有效的 JSON 对象。',
                ]);
            }

            $pricing = $this->normalizePricingPayload($pricing);

            $rules[] = [
                'id' => (string) ($row['id'] ?? "pricing-rule-{$index}"),
                'match' => $this->conditionsFromRows($row['match_conditions'] ?? []),
                'pricing' => $pricing,
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function conditionsFromRows(mixed $conditions): array
    {
        if (! is_array($conditions)) {
            return [];
        }

        $match = [];

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $option = trim((string) ($condition['option'] ?? ''));
            $value = trim((string) ($condition['value'] ?? ''));

            if ($option !== '' && $value !== '') {
                $match[$option] = $value;
            }
        }

        return $match;
    }

    /**
     * Convert stored condition labels to the option codes used by the form.
     * Legacy gallery rules stored display labels such as "Square" while the
     * dynamic form uses stable option codes such as "square".
     *
     * @param  array<string, mixed>  $conditions
     * @return array<int, array{option: string, value: string}>
     */
    private function conditionsToRowsForOptions(array $conditions, mixed $options): array
    {
        if (! is_array($options)) {
            return $this->conditionsToRows($conditions);
        }

        $rows = [];

        foreach ($conditions as $optionKey => $value) {
            $optionKey = (string) $optionKey;
            $option = $options[$optionKey] ?? null;
            $resolvedValue = (string) $value;

            if (is_array($option) && is_array($option['values'] ?? null)) {
                foreach ($option['values'] as $optionValue) {
                    if (! is_array($optionValue)) {
                        continue;
                    }

                    $code = (string) ($optionValue['code'] ?? '');
                    $label = (string) ($optionValue['label'] ?? '');

                    if (
                        $this->normalizedRuleValue($code) === $this->normalizedRuleValue($resolvedValue)
                        || $this->normalizedRuleValue($label) === $this->normalizedRuleValue($resolvedValue)
                    ) {
                        $resolvedValue = $code !== '' ? $code : $resolvedValue;
                        break;
                    }
                }
            }

            $rows[] = [
                'option' => $optionKey,
                'value' => $resolvedValue,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $conditions
     * @return array<int, array{option: string, value: string}>
     */
    private function conditionsToRows(array $conditions): array
    {
        return array_map(
            static fn (mixed $value, string|int $option): array => [
                'option' => (string) $option,
                'value' => (string) $value,
            ],
            $conditions,
            array_keys($conditions),
        );
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    private function scenarioToPricingJson(array $scenario): array
    {
        return [
            'packageName' => (string) ($scenario['package_name'] ?? $scenario['packageName'] ?? ''),
            'basePrice' => (float) ($scenario['base_price_per_card'] ?? $scenario['basePrice'] ?? 0),
            'startQuantity' => (int) ($scenario['start_quantity'] ?? $scenario['startQuantity'] ?? 0),
            'paperRates' => $this->mapToNumericValues($scenario['quantity_discounts_percent'] ?? $scenario['paperRates'] ?? []),
            'processes' => array_values(array_map(function (mixed $process): array {
                if (! is_array($process)) {
                    return [];
                }

                $label = (string) ($process['label'] ?? $process['name'] ?? '');
                $code = trim((string) ($process['code'] ?? ''));

                return [
                    'name' => $label,
                    'code' => $code !== '' ? $code : $this->processCode($label),
                    'markup' => (float) ($process['markup_per_card'] ?? $process['markup'] ?? 0),
                    'rates' => $this->mapToNumericValues($process['quantity_discounts_percent'] ?? $process['rates'] ?? []),
                ];
            }, is_array($scenario['processes'] ?? null) ? $scenario['processes'] : [])),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    private function scenarioMatchConditions(string $scenario, array $options): array
    {
        $match = [];
        $sizeGroup = is_array($options['sizes'] ?? null) ? $options['sizes'] : [];
        $finishGroup = is_array($options['paper_finish'] ?? null) ? $options['paper_finish'] : [];
        $uvFinishGroup = is_array($options['uv_finish'] ?? null) ? $options['uv_finish'] : [];
        $sizes = is_array($sizeGroup['values'] ?? null) ? $sizeGroup['values'] : [];
        $finishes = is_array($finishGroup['values'] ?? null) ? $finishGroup['values'] : [];
        $uvFinishes = is_array($uvFinishGroup['values'] ?? null) ? $uvFinishGroup['values'] : [];

        $standard = $sizes[0]['code'] ?? null;
        $square = collect($sizes)->first(function (mixed $value): bool {
            if (! is_array($value)) {
                return false;
            }

            return $this->normalizedRuleValue($value['code'] ?? '') === 'square'
                || $this->normalizedRuleValue($value['label'] ?? '') === 'square';
        });
        $square = is_array($square) ? ($square['code'] ?? null) : ($sizes[1]['code'] ?? null);
        $uv = collect($uvFinishes)->first(function (mixed $value): bool {
            if (! is_array($value)) {
                return false;
            }

            return in_array($this->normalizedRuleValue($value['code'] ?? ''), [
                'single_side_uv',
                'both_sides_uv',
            ], true);
        });
        $uvMatchKey = 'uv_finish';

        if (! is_array($uv)) {
            $uv = collect($finishes)->first(function (mixed $value): bool {
                if (! is_array($value)) {
                    return false;
                }

                return $this->normalizedRuleValue($value['code'] ?? '') === 'uv'
                    || $this->normalizedRuleValue($value['label'] ?? '') === 'uv';
            });
            $uvMatchKey = 'paper_finish';
        }

        $uv = is_array($uv) ? ($uv['code'] ?? null) : null;

        if ($scenario === 'rectangle' && $standard) {
            $match['sizes'] = (string) $standard;
        }

        if ($scenario === 'uv' && $standard) {
            $match['sizes'] = (string) $standard;
            if ($uv) {
                $match[$uvMatchKey] = (string) $uv;
            }
        }

        if ($scenario === 'square' && $square) {
            $match['sizes'] = (string) $square;
        }

        if ($scenario === 'square_uv' && $square) {
            $match['sizes'] = (string) $square;
            if ($uv) {
                $match[$uvMatchKey] = (string) $uv;
            }
        }

        return $match;
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    private function encodePricingJson(array $pricing): string
    {
        return json_encode(
            $pricing,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: '{}';
    }

    /**
     * Add stable option codes to pricing processes that were entered with
     * display names only. The original names and all other pricing fields
     * remain unchanged.
     *
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    private function normalizePricingPayload(array $pricing): array
    {
        if (! is_array($pricing['processes'] ?? null)) {
            return $pricing;
        }

        $pricing['processes'] = array_values(array_map(function (mixed $process): mixed {
            if (! is_array($process)) {
                return $process;
            }

            $name = trim((string) ($process['name'] ?? $process['label'] ?? ''));
            $code = trim((string) ($process['code'] ?? ''));

            if ($code === '' && $name !== '') {
                $process['code'] = $this->processCode($name);
            }

            return $process;
        }, $pricing['processes']));

        return $pricing;
    }

    private function normalizedRuleValue(mixed $value): string
    {
        return Str::slug(strtolower(trim((string) $value)), '_');
    }

    /**
     * Return the legacy flat shape consumed by the current storefront.
     *
     * @return array<string, mixed>|null
     */
    public function storefrontOptions(Product $product): ?array
    {
        $hasCanonicalConfig = $this->hasCanonicalConfig($product);
        $hardcodedProductOptions = $this->loadHardcodedProductOptions($product);
        $databaseLegacyOptions = $this->databaseLegacyOptions($product);

        if (
            ! $hasCanonicalConfig
            && $hardcodedProductOptions === null
            && $databaseLegacyOptions === null
            && ! BusinessCardOptionCatalog::supports((string) $product->slug)
        ) {
            return null;
        }

        // Start with the canonical database record. No product-specific JSON
        // file is allowed to replace this copy, pricing, FAQ, or detail data.
        $config = $this->databaseStorefrontConfig($product);

        // The legacy file is intentionally limited to the two pieces of
        // product-detail presentation that still use it: option metadata and
        // galleries. Its subtitle, price table, and detail sections are
        // ignored even when they are present in an older file.
        if (! $hasCanonicalConfig && $databaseLegacyOptions !== null) {
            // Legacy product_options is already stored in the database. Keep
            // its old product content available for old records, but never
            // read that content from a repository JSON fallback.
            $config = $this->applyDatabaseLegacyProductData(
                $config,
                $databaseLegacyOptions,
            );
        }

        if ($hardcodedProductOptions !== null) {
            $config = $this->applyLegacyOptionAndGalleryData(
                $config,
                $hardcodedProductOptions,
                (string) $product->slug,
            );
        } elseif (! $hasCanonicalConfig && $databaseLegacyOptions !== null) {
            $config = $this->applyLegacyOptionAndGalleryData(
                $config,
                $databaseLegacyOptions,
                (string) $product->slug,
            );
        } elseif (! $hasCanonicalConfig && BusinessCardOptionCatalog::supports((string) $product->slug)) {
            $config['options'] = $this->normalizeProductSpecificOptions(
                $config['options'],
                $product,
            );
        }

        if (BusinessCardOptionCatalog::supports((string) $product->slug)) {
            $config['options'] = $this->normalizeProductSpecificOptions(
                is_array($config['options'] ?? null) ? $config['options'] : [],
                $product,
            );
        }

        if (BusinessCardOptionCatalog::isCottonBusinessCard((string) $product->slug)) {
            $media = is_array($config['media'] ?? null) ? $config['media'] : [];
            $media['gallery_rules'] = BusinessCardOptionCatalog::normalizeCottonGalleryRules(
                is_array($media['gallery_rules'] ?? null) ? $media['gallery_rules'] : [],
            );
            $config['media'] = $media;
        }

        if ($this->shouldRemoveBusinessCardNfc($product)) {
            $config = $this->withoutBusinessCardNfc($config);
        }

        if ($product->slug === StandardQualityBusinessCardGallery::PRODUCT_SLUG) {
            $config = StandardQualityBusinessCardGallery::synchronizeConfig($config);
            $config['media']['gallery_rules'] = $this->withSharedBusinessCardFoilGalleryRules(
                $config['media']['gallery_rules'],
                $config['options'],
                (string) $product->slug,
            );
        }

        if ($product->slug === SolidQualityBusinessCardGallery::PRODUCT_SLUG) {
            $config = SolidQualityBusinessCardGallery::synchronizeConfig($config);
            $config['media']['gallery_rules'] = $this->withSharedBusinessCardFoilGalleryRules(
                $config['media']['gallery_rules'],
                $config['options'],
                (string) $product->slug,
            );
        }

        $options = $this->toStorefrontOptions(
            $config,
            $product,
            $hardcodedProductOptions !== null || $databaseLegacyOptions !== null,
        );
        $options['show_gang_run_printing'] = $this->supportsGangRunPrinting($product);

        return $this->withResolvedStorefrontImages(
            $this->withSharedBusinessCardDetailSections($options, $product),
        );
    }

    /**
     * Apply the centrally maintained business-card detail sections. Shared
     * design specifications are used only for missing fields; product-specific
     * specifications and FAQs remain authoritative.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function withSharedBusinessCardDetailSections(array $options, Product $product): array
    {
        if (! $this->belongsToBusinessCardCategory($product)) {
            return $options;
        }

        $details = is_array($options['detail_sections'] ?? null)
            ? $options['detail_sections']
            : [];
        $shared = $this->content->section(
            'product_detail_page.shared_detail_sections.business_cards',
            [],
        );

        if (! is_array($shared)) {
            return $options;
        }

        $hasDesignSpecifications = is_array($details['design_specifications'] ?? null);
        $sharedDesignSpecifications = $shared['design_specifications'] ?? null;

        if (! $hasDesignSpecifications && is_array($sharedDesignSpecifications)) {
            $details['design_specifications'] = $sharedDesignSpecifications;
        } elseif ($hasDesignSpecifications && is_array($sharedDesignSpecifications)) {
            $downloads = $details['design_specifications']['downloads'] ?? null;
            $sharedDownloads = $sharedDesignSpecifications['downloads'] ?? null;

            if (
                is_array($sharedDownloads)
                && (! is_array($downloads) || $downloads === [])
            ) {
                $details['design_specifications']['downloads'] = $sharedDownloads;
            }
        }

        foreach (['design_service_banner', 'paper_stocks', 'more_good_stuff'] as $key) {
            if (is_array($shared[$key] ?? null)) {
                $details[$key] = $shared[$key];
            }
        }

        $options['detail_sections'] = $details;

        return $options;
    }

    private function belongsToBusinessCardCategory(Product $product): bool
    {
        return $this->belongsToCategorySlug($product, 'business-cards');
    }

    private function supportsGangRunPrinting(Product $product): bool
    {
        return in_array((string) $product->slug, self::GANG_RUN_PRINTING_PRODUCT_SLUGS, true)
            || $this->belongsToCategorySlug($product, 'pvc-business-cards');
    }

    private function belongsToCategorySlug(Product $product, string $categorySlug): bool
    {
        $category = $product->category;
        $visited = [];

        while ($category !== null) {
            if ($category->slug === $categorySlug) {
                return true;
            }

            $categoryId = $category->getKey();

            if ($categoryId !== null) {
                if (isset($visited[$categoryId])) {
                    return false;
                }

                $visited[$categoryId] = true;
            }

            if (! $category->parent_id) {
                return false;
            }

            $category = $category->relationLoaded('parent')
                ? $category->getRelation('parent')
                : $category->parent()->first();
        }

        return false;
    }

    /**
     * Normalize only the database-owned configuration used by the storefront.
     * This intentionally does not call canonicalConfig(), because that method
     * also applies the editor's product-specific option contracts to legacy
     * payloads. The live storefront should preserve the database option map
     * and only overlay the explicitly permitted file option/gallery source.
     *
     * @return array<string, mixed>
     */
    private function databaseStorefrontConfig(Product $product): array
    {
        $config = $this->hasCanonicalConfig($product)
            ? (is_array($product->product_config) ? $product->product_config : [])
            : [];

        return $this->normalizeCanonicalConfig($config, $product);
    }

    /**
     * Return the legacy option payload stored on the product itself. A null
     * result means that the database does not contain the old payload; it does
     * not fall back to a repository file.
     *
     * @return array<string, mixed>|null
     */
    private function databaseLegacyOptions(Product $product): ?array
    {
        return is_array($product->product_options) && $product->product_options !== []
            ? $product->product_options
            : null;
    }

    /**
     * Load the product-detail JSON source used only for option metadata and
     * galleries. Category slugs have changed over time, so the exact product
     * filename is also searched across the product-options directories.
     *
     * @return array<string, mixed>|null
     */
    private function loadHardcodedProductOptions(Product $product): ?array
    {
        $slug = trim((string) $product->slug);

        if ($slug === '') {
            return null;
        }

        $paths = [];
        $categorySlug = $product->category?->slug;

        if (is_string($categorySlug) && $categorySlug !== '') {
            $paths[] = base_path("content/product-options/{$categorySlug}/{$slug}.json");
        }

        $matchingPaths = glob(base_path("content/product-options/*/{$slug}.json"));
        $paths = [
            ...$paths,
            ...(is_array($matchingPaths) ? $matchingPaths : []),
        ];

        foreach (array_values(array_unique($paths)) as $path) {
            if (! is_file($path)) {
                continue;
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            $decoded = json_decode($contents, true);

            if (is_array($decoded)) {
                $allowedData = $this->hardcodedOptionAndGalleryData($decoded);

                return $allowedData === [] ? null : $allowedData;
            }
        }

        return null;
    }

    /**
     * Keep the legacy file boundary explicit. Product copy, pricing, FAQ,
     * and detail sections are intentionally discarded before the payload can
     * reach any configuration adapter.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function hardcodedOptionAndGalleryData(array $payload): array
    {
        $allowedKeys = [
            ...array_keys(self::OPTION_GROUP_LABELS),
            'finish',
            'texture',
            'thickness',
            'print_code_or_signature_stripe',
            'print_code_or_magnetic_stripe',
            'galleries',
        ];

        return array_intersect_key($payload, array_fill_keys($allowedKeys, true));
    }

    /**
     * Copy only the option and gallery portions of a legacy payload into a
     * canonical-shaped configuration. All other keys are deliberately left
     * untouched so database-owned product data cannot be replaced by file
     * content.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function applyLegacyOptionAndGalleryData(array $config, array $legacy, ?string $slug = null): array
    {
        $options = $this->optionsFromLegacy($legacy, $slug);

        if ($options !== []) {
            $config['options'] = $this->orderedOptionGroups(
                BusinessCardOptionCatalog::normalizeSharedSwatchImages(
                    BusinessCardOptionCatalog::normalizeSharedSizeSwatches($options, $slug),
                ),
            );
        }

        $galleries = is_array($legacy['galleries'] ?? null) ? $legacy['galleries'] : [];

        if ($galleries === []) {
            return $config;
        }

        $defaultGallery = collect($galleries)->first(function (mixed $gallery): bool {
            return is_array($gallery) && (
                (bool) ($gallery['is_default'] ?? false)
                || ($gallery['id'] ?? null) === 'default'
                || ($gallery['match'] ?? []) === []
            );
        });
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];

        $media['gallery'] = is_array($defaultGallery['images'] ?? null)
            ? array_values($defaultGallery['images'])
            : [];
        $media['gallery_rules'] = $this->normalizeUvOptionRules(
            $this->galleryRulesFromLegacy($galleries),
        );
        $config['media'] = $media;

        return $config;
    }

    /**
     * Preserve non-option data from the legacy JSON column when an old
     * database row has not been migrated to product_config yet. Repository
     * files are never used for this path.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function applyDatabaseLegacyProductData(array $config, array $legacy): array
    {
        $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];

        if (is_array($legacy['pricing_data'] ?? null)) {
            $pricing['mode'] = 'rule_based';
            $pricing['scenarios'] = $this->scenariosFromDynamicPricing($legacy['pricing_data']);
            $pricing['quantity_price_table'] = [];
        } elseif (is_array($legacy['quantity_price_table'] ?? null)) {
            $pricing['quantity_price_table'] = array_values($legacy['quantity_price_table']);
        }

        $config['pricing'] = $pricing;
        $config['faq'] = $this->faqFromLegacy($legacy);
        $config['detail_sections'] = is_array($legacy['detail_sections'] ?? null)
            ? $legacy['detail_sections']
            : ($config['detail_sections'] ?? []);

        return $config;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadLegacyOptions(Product $product): ?array
    {
        return $this->databaseLegacyOptions($product)
            ?? $this->loadHardcodedProductOptions($product);
    }

    /**
     * Build an editable canonical shape from a legacy payload. Repository
     * JSON contributes only option metadata and galleries; the old
     * product_options database column may also carry its existing pricing,
     * FAQ, and detail data for backward compatibility.
     *
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function fromLegacyOptions(
        Product $product,
        array $legacy,
        bool $legacyDataIsDatabaseOwned = false,
    ): array {
        $galleries = is_array($legacy['galleries'] ?? null) ? $legacy['galleries'] : [];
        $defaultGallery = collect($galleries)->first(function (mixed $gallery): bool {
            return is_array($gallery) && (
                (bool) ($gallery['is_default'] ?? false) ||
                ($gallery['id'] ?? null) === 'default' ||
                ($gallery['match'] ?? []) === []
            );
        });

        $config = [
            'schema_version' => 1,
            'product' => [
                'slug' => $product->slug,
                'name' => $product->name,
                'subtitle' => $product->subtitle,
                'description' => $product->description,
                'description_title' => $product->description_title,
                'bullet_points' => $product->bullet_points ?? [],
                'featured_image' => $product->featured_image,
                'meta_description' => $product->meta_description,
            ],
            'options' => $this->normalizeProductSpecificOptions(
                $this->optionsFromLegacy($legacy, (string) $product->slug),
                $product,
            ),
            'media' => [
                'gallery' => is_array($defaultGallery['images'] ?? null)
                    ? array_values($defaultGallery['images'])
                    : [],
                'gallery_rules' => $this->galleryRulesFromLegacy($galleries),
            ],
            'pricing' => [
                'mode' => $legacyDataIsDatabaseOwned && is_array($legacy['pricing_data'] ?? null)
                    ? 'rule_based'
                    : 'fixed_tiers',
                'currency' => 'USD',
                'total_rounding' => 'nearest_integer',
                'scenarios' => $legacyDataIsDatabaseOwned && is_array($legacy['pricing_data'] ?? null)
                    ? $this->scenariosFromDynamicPricing($legacy['pricing_data'])
                    : [],
                'quantity_price_table' => $legacyDataIsDatabaseOwned
                    && is_array($legacy['quantity_price_table'] ?? null)
                    ? array_values($legacy['quantity_price_table'])
                    : [],
                'rules' => [],
            ],
            'faq' => $legacyDataIsDatabaseOwned ? $this->faqFromLegacy($legacy) : [],
            'detail_sections' => $legacyDataIsDatabaseOwned
                && is_array($legacy['detail_sections'] ?? null)
                ? $legacy['detail_sections']
                : [],
        ];

        return $this->normalizeCanonicalConfig($config, $product);
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, array<string, mixed>>
     */
    private function optionsFromLegacy(array $legacy, ?string $productSlug = null): array
    {
        $options = [];
        $groupLabels = self::OPTION_GROUP_LABELS;

        if (array_key_exists('texture', $legacy)) {
            $groupLabels['texture'] = $productSlug === StandardQualityBusinessCardGallery::PRODUCT_SLUG
                ? 'Paper Finish'
                : 'Texture';
        }

        if (
            array_key_exists('uv_finish', $legacy)
            && $productSlug === SolidQualityBusinessCardGallery::PRODUCT_SLUG
        ) {
            $groupLabels['uv_finish'] = '3D UV';
        }

        foreach ([
            'thickness' => 'Thickness',
            'print_code_or_signature_stripe' => 'Print Code or Signature Stripe',
            'print_code_or_magnetic_stripe' => 'Print Code or Magnetic Stripe',
        ] as $key => $label) {
            if (array_key_exists($key, $legacy)) {
                $groupLabels[$key] = $label;
            }
        }

        if (array_key_exists('finish', $legacy)) {
            $groupLabels['finish'] = 'Finish';
        }

        foreach ($groupLabels as $key => $label) {
            if (! array_key_exists($key, $legacy)) {
                continue;
            }

            $items = is_array($legacy[$key] ?? null) ? $legacy[$key] : [];
            $isOptionalUvGroup = $key === 'uv_finish'
                && in_array($productSlug, [
                    'classic-standard-business-cards',
                    StandardQualityBusinessCardGallery::PRODUCT_SLUG,
                ], true);

            $options[$key] = [
                'label' => $label,
                'type' => $this->legacyOptionGroupType($key, $items, $productSlug),
                'required' => ! $isOptionalUvGroup,
                'default' => $isOptionalUvGroup ? null : ($items[0]['code'] ?? null),
                'values' => array_values(array_map(function (mixed $item): array {
                    if (! is_array($item)) {
                        return [];
                    }

                    $value = [
                        'code' => (string) ($item['code'] ?? Str::slug((string) ($item['name'] ?? ''), '_')),
                        'label' => (string) ($item['name'] ?? ''),
                    ];

                    foreach ([
                        'description',
                        'swatch_image',
                        'width',
                        'height',
                        'min_width',
                        'max_width',
                        'min_height',
                        'max_height',
                    ] as $property) {
                        if (array_key_exists($property, $item) && $item[$property] !== '') {
                            $value[$property] = $item[$property];
                        }
                    }

                    return $value;
                }, $items)),
            ];
        }

        return $this->splitUvPaperFinishOptionGroup($options);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function hasFoilOptionValues(array $items): bool
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $text = strtolower(implode(' ', array_filter([
                (string) ($item['code'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['label'] ?? ''),
                (string) ($item['description'] ?? ''),
            ])));

            if (str_contains($text, 'foil') || str_contains($text, '烫')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hot and cold foil values can be combined, while other special finishes
     * retain their single-choice behavior. Classic standard business cards
     * expose only combinable hot-foil choices, even though their legacy
     * option file does not include the word "foil" in each value.
     *
     * @param  array<int, mixed>  $items
     */
    private function legacyOptionGroupType(
        string $key,
        array $items,
        ?string $productSlug = null,
    ): string {
        if ($key !== 'special_finish') {
            return 'select';
        }

        return $productSlug === 'classic-standard-business-cards'
            || $this->hasFoilOptionValues($items)
            ? 'multi_select'
            : 'select';
    }

    /**
     * @param  array<int, mixed>  $galleries
     * @return array<int, array<string, mixed>>
     */
    private function galleryRulesFromLegacy(array $galleries): array
    {
        return array_map(function (mixed $gallery, int $index): array {
            if (! is_array($gallery)) {
                return [
                    'id' => "gallery-{$index}",
                    'match' => [],
                    'images' => [],
                    'primary' => null,
                ];
            }

            $images = is_array($gallery['images'] ?? null) ? array_values($gallery['images']) : [];
            $primary = $gallery['primary'] ?? null;

            return [
                'id' => (string) ($gallery['id'] ?? "gallery-{$index}"),
                'match' => is_array($gallery['match'] ?? null) ? $gallery['match'] : [],
                'images' => $images,
                'primary' => filled($primary) ? $primary : ($images[0] ?? null),
            ];
        }, $galleries, array_keys($galleries));
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<int, array<string, mixed>>
     */
    private function faqFromLegacy(array $legacy): array
    {
        $items = data_get($legacy, 'detail_sections.faq.items', []);

        if (! is_array($items)) {
            return [];
        }

        return array_map(
            fn (mixed $item, int $index): array => [
                'question' => is_array($item) ? (string) ($item['question'] ?? '') : '',
                'answer' => is_array($item) ? (string) ($item['answer'] ?? '') : '',
                'sort_order' => $index + 1,
                'is_active' => true,
            ],
            $items,
            array_keys($items),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function toStorefrontOptions(
        array $config,
        Product $product,
        bool $hasExternalOptionSource = false,
    ): array {
        $options = [];
        $optionGroups = [];

        $configuredOptions = is_array($config['options'] ?? null)
            ? $this->orderedOptionGroups($config['options'])
            : [];

        foreach ($configuredOptions as $key => $group) {
            if (! is_array($group)) {
                continue;
            }

            $values = array_values(array_map(function (mixed $value): array {
                if (! is_array($value)) {
                    return [];
                }

                $legacy = [
                    'name' => (string) ($value['label'] ?? $value['name'] ?? ''),
                    'code' => (string) ($value['code'] ?? ''),
                ];

                foreach ([
                    'description',
                    'swatch_image',
                    'width',
                    'height',
                    'min_width',
                    'max_width',
                    'min_height',
                    'max_height',
                    'area_sq_m',
                ] as $property) {
                    if (array_key_exists($property, $value)) {
                        $legacy[$property] = $property === 'swatch_image'
                            ? $this->storefrontImageUrl($value[$property])
                            : $value[$property];
                    }
                }

                return $legacy;
            }, is_array($group['values'] ?? null) ? $group['values'] : []));

            $optionKey = (string) $key;
            $isMultiSelect = ($group['type'] ?? 'select') === 'multi_select'
                || ($optionKey === 'special_finish' && $this->hasFoilOptionValues($values));
            $required = (bool) ($group['required'] ?? true);
            $default = array_key_exists('default', $group)
                ? $group['default']
                : ($required ? ($values[0]['code'] ?? '') : null);

            if ($default === null && $required) {
                $default = $values[0]['code'] ?? '';
            }

            $optionGroups[] = [
                'key' => $optionKey,
                'label' => (string) ($group['label'] ?? Str::headline($optionKey)),
                'type' => $isMultiSelect
                    ? 'multi_select'
                    : 'select',
                'required' => $required,
                'default' => is_array($default)
                    ? array_values(array_map(static fn (mixed $code): string => (string) $code, $default))
                    : ($default === null ? null : (string) $default),
                'values' => $values,
            ];
            $options[$optionKey] = $values;
        }

        $hasDynamicOptions = $hasExternalOptionSource
            || $this->hasCanonicalConfig($product)
            || $product->slug === 'classic-special-business-cards'
            || BusinessCardOptionCatalog::supports((string) $product->slug);

        $options['dynamic_options'] = $hasDynamicOptions;
        $options['option_groups'] = $hasDynamicOptions
            ? $optionGroups
            : [];

        $galleryRules = is_array(data_get($config, 'media.gallery_rules'))
            ? data_get($config, 'media.gallery_rules')
            : [];
        $defaultGalleryImages = is_array(data_get($config, 'media.gallery'))
            ? array_values(data_get($config, 'media.gallery'))
            : [];

        $options['galleries'] = array_map(function (mixed $gallery, int $index): array {
            if (! is_array($gallery)) {
                return [];
            }

            $rawImages = is_array($gallery['images'] ?? null) ? array_values($gallery['images']) : [];
            $primary = $gallery['primary'] ?? null;

            if (filled($primary)) {
                $rawImages = [
                    $primary,
                    ...array_values(array_filter($rawImages, fn (mixed $image): bool => $image !== $primary)),
                ];
            }

            $images = array_values(array_filter(array_map(
                fn (mixed $image): mixed => $this->storefrontImageUrl($image),
                array_values(array_unique($rawImages)),
            )));
            $match = is_array($gallery['match'] ?? null) ? $gallery['match'] : [];

            return [
                'id' => (string) ($gallery['id'] ?? "gallery-{$index}"),
                'is_default' => ($gallery['id'] ?? null) === 'default' || $match === [],
                'match' => $match,
                'images' => $images,
            ];
        }, $galleryRules, array_keys($galleryRules));

        if ($defaultGalleryImages !== []) {
            array_unshift($options['galleries'], [
                'id' => 'default',
                'is_default' => true,
                'match' => [],
                'images' => array_values(array_filter(array_map(
                    fn (mixed $image): mixed => $this->storefrontImageUrl($image),
                    $defaultGalleryImages,
                ))),
            ]);
        }

        $options['pricing_data'] = $this->dynamicPricingDataFromConfig($config['pricing'] ?? []);
        $options['pricing_rules'] = $this->storefrontPricingRules(data_get($config, 'pricing.rules', []));
        $options['quantity_price_table'] = is_array(data_get($config, 'pricing.quantity_price_table'))
            ? array_values(data_get($config, 'pricing.quantity_price_table'))
            : [];
        // Product copy belongs to the database projection. In particular,
        // never fall back to `subtitle` from a legacy product-options file.
        $options['subtitle'] = $product->subtitle;
        $options['starting_price_text'] = $product->price_line;

        $details = is_array($config['detail_sections'] ?? null) ? $config['detail_sections'] : [];
        $faq = is_array($config['faq'] ?? null) ? $config['faq'] : [];

        if ($faq !== []) {
            $details['faq'] = [
                'heading' => data_get($details, 'faq.heading', 'Frequently asked questions'),
                'items' => array_values(array_map(
                    fn (mixed $item): array => [
                        'question' => is_array($item) ? ($item['question'] ?? '') : '',
                        'answer' => is_array($item) ? ($item['answer'] ?? '') : '',
                    ],
                    $faq,
                )),
            ];
        }

        $options['detail_sections'] = $details;

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function storefrontPricingRules(mixed $rules): array
    {
        if (! is_array($rules)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $rule, int|string $index): ?array {
            if (! is_array($rule) || ! is_array($rule['pricing'] ?? null)) {
                return null;
            }

            return [
                'id' => (string) ($rule['id'] ?? "pricing-rule-{$index}"),
                'match' => is_array($rule['match'] ?? null) ? $rule['match'] : [],
                'pricing' => $this->normalizePricingPayload($rule['pricing']),
            ];
        }, $rules, array_keys($rules))));
    }

    private function storefrontImageUrl(mixed $image): mixed
    {
        return $this->imageResolver->url($image);
    }

    /**
     * Resolve image-bearing values in both canonical and legacy storefront
     * configurations without changing non-image product data.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function withResolvedStorefrontImages(array $options): array
    {
        $singleImageKeys = [
            'featured_image',
            'image',
            'image_url',
            'primary',
            'swatch_image',
            'thumbnail',
            'thumbnail_url',
        ];
        $imageListKeys = ['gallery', 'images'];

        foreach ($options as $key => $value) {
            if (is_string($value) && in_array($key, $singleImageKeys, true)) {
                $options[$key] = $this->storefrontImageUrl($value);

                continue;
            }

            if (! is_array($value)) {
                continue;
            }

            if (in_array($key, $imageListKeys, true)) {
                $options[$key] = array_map(
                    fn (mixed $image): mixed => is_array($image)
                        ? $this->withResolvedStorefrontImages($image)
                        : $this->storefrontImageUrl($image),
                    $value,
                );

                continue;
            }

            $options[$key] = $this->withResolvedStorefrontImages($value);
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>|null
     */
    private function dynamicPricingDataFromConfig(array $pricing): ?array
    {
        if (($pricing['mode'] ?? null) !== 'rule_based') {
            return null;
        }

        $data = [];

        foreach (self::PRICING_SCENARIOS as $scenarioKey) {
            $scenario = $pricing['scenarios'][$scenarioKey] ?? null;

            if (! is_array($scenario) || ! isset($scenario['base_price_per_card'], $scenario['start_quantity'])) {
                continue;
            }

            $processes = [];

            foreach ($scenario['processes'] ?? [] as $process) {
                if (! is_array($process)) {
                    continue;
                }

                $processes[] = [
                    'name' => (string) ($process['label'] ?? $process['code'] ?? ''),
                    'code' => (string) ($process['code'] ?? ''),
                    'markup' => (float) ($process['markup_per_card'] ?? 0),
                    'rates' => $this->mapToNumericValues($process['quantity_discounts_percent'] ?? []),
                ];
            }

            $data[$scenarioKey] = [
                'packageName' => (string) ($scenario['package_name'] ?? ''),
                'basePrice' => (float) $scenario['base_price_per_card'],
                'startQuantity' => (int) $scenario['start_quantity'],
                'paperRates' => $this->mapToNumericValues($scenario['quantity_discounts_percent'] ?? []),
                'processes' => $processes,
            ];
        }

        return $data === [] ? null : $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function scenariosFromDynamicPricing(array $data): array
    {
        $scenarios = [];

        foreach ($data as $scenarioKey => $scenario) {
            if (! is_array($scenario)) {
                continue;
            }

            $scenarios[$scenarioKey] = [
                'package_name' => $scenario['packageName'] ?? '',
                'base_price_per_card' => (float) ($scenario['basePrice'] ?? 0),
                'start_quantity' => (int) ($scenario['startQuantity'] ?? 0),
                'quantity_discounts_percent' => $this->mapToNumericValues($scenario['paperRates'] ?? []),
                'processes' => array_values(array_map(function (mixed $process): array {
                    if (! is_array($process)) {
                        return [];
                    }

                    $label = (string) ($process['name'] ?? $process['label'] ?? '');

                    return [
                        'code' => (string) ($process['code'] ?? $this->processCode($label)),
                        'label' => $label,
                        'markup_per_card' => (float) ($process['markup'] ?? $process['markup_per_card'] ?? 0),
                        'quantity_discounts_percent' => $this->mapToNumericValues($process['rates'] ?? $process['quantity_discounts_percent'] ?? []),
                    ];
                }, $scenario['processes'] ?? [])),
            ];
        }

        return $scenarios;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $state
     * @return array<string, array<string, mixed>>
     */
    private function mergeScenarioState(array $existing, array $state): array
    {
        $merged = $existing;

        foreach ($state as $scenarioKey => $scenarioState) {
            if (! is_array($scenarioState)) {
                continue;
            }

            $scenario = array_replace(
                is_array($existing[$scenarioKey] ?? null) ? $existing[$scenarioKey] : [],
                $scenarioState,
            );

            if (array_key_exists('quantity_discounts', $scenarioState)) {
                $scenario['quantity_discounts_percent'] = $this->rowsToMap($scenarioState['quantity_discounts'] ?? []);
            }
            unset($scenario['quantity_discounts']);

            $existingProcesses = [];
            foreach ($existing[$scenarioKey]['processes'] ?? [] as $process) {
                if (is_array($process)) {
                    $existingProcesses[(string) ($process['code'] ?? '')] = $process;
                }
            }

            $processes = [];
            foreach ($scenarioState['processes'] ?? [] as $processState) {
                if (! is_array($processState)) {
                    continue;
                }

                $processCode = (string) ($processState['code'] ?? '');
                $process = array_replace($existingProcesses[$processCode] ?? [], $processState);

                if (array_key_exists('quantity_discounts', $processState)) {
                    $process['quantity_discounts_percent'] = $this->rowsToMap($processState['quantity_discounts'] ?? []);
                }
                unset($process['quantity_discounts']);

                $processes[] = $process;
            }

            if (array_key_exists('processes', $scenarioState)) {
                $scenario['processes'] = $processes;
            }

            $merged[$scenarioKey] = $scenario;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeCanonicalConfig(array $config, Product $product): array
    {
        $config['schema_version'] = (int) ($config['schema_version'] ?? 1);
        $config['product'] = array_replace([
            'slug' => $product->slug,
            'name' => $product->name,
            'subtitle' => $product->subtitle,
            'description' => $product->description,
            'description_title' => $product->description_title,
            'bullet_points' => $product->bullet_points ?? [],
            'featured_image' => $product->featured_image,
            'meta_description' => $product->meta_description,
        ], is_array($config['product'] ?? null) ? $config['product'] : []);
        $config['options'] = is_array($config['options'] ?? null) ? $config['options'] : [];
        unset($config['options']['special_finish_on_sides']);
        $config['options'] = BusinessCardOptionCatalog::normalizeSharedSwatchImages($config['options']);
        $config['options'] = $this->normalizeProductSpecificOptions(
            $config['options'],
            $product,
        );
        $config['media'] = is_array($config['media'] ?? null) ? $config['media'] : [];
        $config['media']['gallery'] = is_array($config['media']['gallery'] ?? null)
            ? array_values($config['media']['gallery'])
            : [];
        $config['media']['gallery_rules'] = is_array($config['media']['gallery_rules'] ?? null)
            ? array_values($config['media']['gallery_rules'])
            : [];
        $config['media']['gallery_rules'] = $this->withSharedBusinessCardFoilGalleryRules(
            $config['media']['gallery_rules'],
            $config['options'],
            (string) $product->slug,
        );

        if (BusinessCardOptionCatalog::isCottonBusinessCard((string) $product->slug)) {
            $config['media']['gallery_rules'] = BusinessCardOptionCatalog::normalizeCottonGalleryRules(
                $config['media']['gallery_rules'],
            );
        }
        $config['pricing'] = array_replace([
            'mode' => 'fixed_tiers',
            'currency' => 'USD',
            'total_rounding' => 'nearest_integer',
            'scenarios' => [],
            'quantity_price_table' => [],
            'rules' => [],
        ], is_array($config['pricing'] ?? null) ? $config['pricing'] : []);
        $config['media']['gallery_rules'] = $this->normalizeUvOptionRules(
            $config['media']['gallery_rules'],
        );
        $config['pricing']['rules'] = $this->normalizeUvOptionRules(
            is_array($config['pricing']['rules'] ?? null) ? $config['pricing']['rules'] : [],
        );
        $config['faq'] = is_array($config['faq'] ?? null) ? array_values($config['faq']) : [];
        $config['detail_sections'] = is_array($config['detail_sections'] ?? null)
            ? $config['detail_sections']
            : [];

        if (array_key_exists('feature_cards', $config['detail_sections'])) {
            $cards = is_array($config['detail_sections']['feature_cards'] ?? null)
                ? $config['detail_sections']['feature_cards']
                : [];

            $config['detail_sections']['feature_cards'] = array_values(array_map(
                static fn (mixed $card): array => [
                    'title' => is_array($card) ? (string) ($card['title'] ?? '') : '',
                    'description' => is_array($card) ? (string) ($card['description'] ?? '') : '',
                    'tooltip_title' => is_array($card) ? (string) ($card['tooltip_title'] ?? '') : '',
                    'tooltip_content' => is_array($card) ? (string) ($card['tooltip_content'] ?? '') : '',
                ],
                array_slice($cards, 0, 2),
            ));
        }

        if ($product->slug === StandardQualityBusinessCardGallery::PRODUCT_SLUG) {
            $config = StandardQualityBusinessCardGallery::synchronizeConfig($config);
            $config['media']['gallery_rules'] = $this->withSharedBusinessCardFoilGalleryRules(
                $config['media']['gallery_rules'],
                $config['options'],
                (string) $product->slug,
            );
        }

        if ($product->slug === SolidQualityBusinessCardGallery::PRODUCT_SLUG) {
            $config = SolidQualityBusinessCardGallery::synchronizeConfig($config);
            $config['media']['gallery_rules'] = $this->withSharedBusinessCardFoilGalleryRules(
                $config['media']['gallery_rules'],
                $config['options'],
                (string) $product->slug,
            );
        }

        if ($this->shouldRemoveBusinessCardNfc($product)) {
            $config = $this->withoutBusinessCardNfc($config);
        }

        return $config;
    }

    private function shouldRemoveBusinessCardNfc(Product $product): bool
    {
        return $product->slug !== 'design-service'
            && (
                $this->belongsToBusinessCardCategory($product)
                || BusinessCardOptionCatalog::isBusinessCardProduct((string) $product->slug)
            );
    }

    /**
     * Remove retired NFC options, pricing processes, and gallery matches from
     * a business-card configuration before it reaches an editor or storefront.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function withoutBusinessCardNfc(array $config): array
    {
        if (is_array($config['options'] ?? null)) {
            $config['options'] = BusinessCardOptionCatalog::withoutNfcOptions($config['options']);
        }

        if (is_array($config['pricing'] ?? null)) {
            $config['pricing'] = $this->withoutNfcPricingProcesses($config['pricing']);
        }

        $media = is_array($config['media'] ?? null) ? $config['media'] : [];

        if (is_array($media['gallery_rules'] ?? null)) {
            $media['gallery_rules'] = array_values(array_filter(
                $media['gallery_rules'],
                fn (mixed $rule): bool => ! $this->galleryRuleUsesNfc($rule),
            ));
        }

        if ($media !== []) {
            $config['media'] = $media;
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    private function withoutNfcPricingProcesses(array $pricing): array
    {
        if (is_array($pricing['processes'] ?? null)) {
            $pricing['processes'] = array_values(array_filter(
                $pricing['processes'],
                fn (mixed $process): bool => ! $this->isNfcProcess($process),
            ));
        }

        foreach (['scenarios', 'rules', 'pricing_data'] as $key) {
            if (! is_array($pricing[$key] ?? null)) {
                continue;
            }

            foreach ($pricing[$key] as $entryKey => $entry) {
                if (is_array($entry)) {
                    $pricing[$key][$entryKey] = $this->withoutNfcPricingProcesses($entry);
                }
            }
        }

        if (is_array($pricing['pricing'] ?? null)) {
            $pricing['pricing'] = $this->withoutNfcPricingProcesses($pricing['pricing']);
        }

        return $pricing;
    }

    private function galleryRuleUsesNfc(mixed $rule): bool
    {
        if (! is_array($rule) || ! is_array($rule['match'] ?? null)) {
            return false;
        }

        foreach ($rule['match'] as $value) {
            if (is_array($value)) {
                foreach ($value as $nestedValue) {
                    if ($this->isNfcToken($nestedValue)) {
                        return true;
                    }
                }

                continue;
            }

            if ($this->isNfcToken($value)) {
                return true;
            }
        }

        return false;
    }

    private function isNfcProcess(mixed $process): bool
    {
        if (is_scalar($process)) {
            return $this->isNfcToken($process);
        }

        if (! is_array($process)) {
            return false;
        }

        foreach (['code', 'name', 'label'] as $key) {
            if ($this->isNfcToken($process[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function isNfcToken(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        $token = str_replace(['-', ' '], '_', strtolower(trim((string) $value)));

        return in_array($token, ['nfc', 'with_nfc', 'no_nfc'], true);
    }

    /**
     * Add the shared foil image rules only for foil values the product
     * actually exposes. Existing rules for those values are replaced so an
     * older product configuration cannot override the shared artwork.
     *
     * @param  array<int, mixed>  $rules
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private function withSharedBusinessCardFoilGalleryRules(
        array $rules,
        array $options,
        ?string $productSlug = null,
    ): array
    {
        $specialFinishValues = data_get($options, 'special_finish.values', []);

        if (! is_array($specialFinishValues)) {
            return array_values(array_filter($rules, is_array(...)));
        }

        $availableCodes = [];

        foreach ($specialFinishValues as $value) {
            if (is_array($value) && filled($value['code'] ?? null)) {
                $availableCodes[$this->normalizedRuleValue($value['code'])] = true;
            }
        }

        $sharedRules = [];
        $sharedCodes = [];

        $productFoilImages = match ($productSlug) {
            StandardQualityBusinessCardGallery::PRODUCT_SLUG => StandardQualityBusinessCardGallery::COLD_FOIL_IMAGES,
            SolidQualityBusinessCardGallery::PRODUCT_SLUG => SolidQualityBusinessCardGallery::COLD_FOIL_IMAGES,
            default => [],
        };
        $foilImages = $productFoilImages + self::SHARED_BUSINESS_CARD_FOIL_IMAGES;

        foreach ($foilImages as $code => $image) {
            $normalizedCode = $this->normalizedRuleValue($code);

            if (! isset($availableCodes[$normalizedCode])) {
                continue;
            }

            $sharedCodes[$normalizedCode] = true;
            $sharedRules[] = [
                'id' => "shared-foil-{$code}",
                'match' => ['special_finish' => $code],
                'images' => [$image],
                'primary' => $image,
            ];
        }

        if ($sharedRules === []) {
            return array_values(array_filter($rules, is_array(...)));
        }

        $remainingRules = array_values(array_filter(
            $rules,
            function (mixed $rule) use ($sharedCodes): bool {
                if (! is_array($rule)) {
                    return false;
                }

                $match = $rule['match'] ?? [];
                $specialFinish = is_array($match) ? ($match['special_finish'] ?? null) : null;

                return ! isset($sharedCodes[$this->normalizedRuleValue($specialFinish)]);
            },
        ));

        return [...$remainingRules, ...$sharedRules];
    }

    /**
     * Keep the classic special business-card option contract consistent for
     * canonical database configurations and legacy JSON configurations.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeProductSpecificOptions(array $options, Product $product): array
    {
        $options = $this->splitUvPaperFinishOptionGroup($options);

        if ($product->slug === SolidQualityBusinessCardGallery::PRODUCT_SLUG) {
            return $this->orderedOptionGroups(
                SolidQualityBusinessCardGallery::synchronizeOptions(
                    BusinessCardOptionCatalog::normalizeSharedSwatchImages($options),
                ),
            );
        }

        $catalogOptions = BusinessCardOptionCatalog::normalize((string) $product->slug, $options);

        if ($catalogOptions !== null) {
            return $this->orderedOptionGroups(
                BusinessCardOptionCatalog::normalizeSharedSizeSwatches(
                    $catalogOptions,
                    (string) $product->slug,
                ),
            );
        }

        if ($product->slug !== 'classic-special-business-cards') {
            return $this->orderedOptionGroups(
                BusinessCardOptionCatalog::normalizeSharedSizeSwatches(
                    $options,
                    (string) $product->slug,
                ),
            );
        }

        $group = static fn (string $label, array $values, string $default): array => [
            'label' => $label,
            'type' => 'select',
            'required' => true,
            'default' => $default,
            'values' => array_values($values),
        ];

        $sizes = [
            array_replace(
                $this->existingOptionValue($options, 'sizes', 'standard', [
                    'label' => 'Standard',
                    'width' => '2.0',
                    'height' => '3.5',
                ]),
                [
                    'label' => 'Standard',
                    'description' => '2.0″ x 3.5″',
                    'width' => '2.0',
                    'height' => '3.5',
                    'swatch_image' => BusinessCardOptionCatalog::STANDARD_SIZE_SWATCH_IMAGE,
                ],
            ),
            array_replace(
                $this->existingOptionValue($options, 'sizes', 'square', [
                    'label' => 'Square',
                    'width' => '2.5',
                    'height' => '2.5',
                ]),
                [
                    'label' => 'Square',
                    'description' => '2.5″ x 2.5″',
                    'width' => '2.5',
                    'height' => '2.5',
                    'swatch_image' => BusinessCardOptionCatalog::SQUARE_SIZE_SWATCH_IMAGE,
                ],
            ),
            [
                'code' => 'custom',
                'label' => 'Custom',
                'description' => BusinessCardOptionCatalog::CUSTOM_SIZE_DESCRIPTION,
                'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
            ],
        ];

        $corners = [
            array_replace(
                $this->existingOptionValue($options, 'corners', 'square', ['label' => 'Square']),
                [
                    'label' => 'Square',
                    'description' => 'Sharp and stylish.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/square.webp',
                ],
            ),
            array_replace(
                $this->existingOptionValue($options, 'corners', 'rounded', ['label' => 'Rounded']),
                [
                    'label' => 'Rounded',
                    'description' => 'Smooth and rounded.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/rounded.webp',
                ],
            ),
        ];

        $foilSwatches = '/images/product-options/business-cards/swatches/';
        $foils = [
            ['code' => 'black_gold', 'label' => 'Black Gold', 'swatch_image' => $foilSwatches.'black-gold.png'],
            ['code' => 'blue_gold', 'label' => 'Blue Gold', 'swatch_image' => $foilSwatches.'blue-gold.png'],
            ['code' => 'bright_gold', 'label' => 'Bright Gold', 'swatch_image' => $foilSwatches.'bright-gold.png'],
            ['code' => 'bright_silver', 'label' => 'Bright Silver', 'swatch_image' => $foilSwatches.'bright-silver.png'],
            ['code' => 'green_gold', 'label' => 'Green Gold', 'swatch_image' => $foilSwatches.'green-gold.png'],
            ['code' => 'matte_gold', 'label' => 'Matte Gold', 'swatch_image' => $foilSwatches.'matte-gold.png'],
            ['code' => 'matte_silver', 'label' => 'Matte Silver', 'swatch_image' => $foilSwatches.'matte-silver.png'],
            ['code' => 'red_gold', 'label' => 'Red Gold', 'swatch_image' => $foilSwatches.'red-gold.png'],
            ['code' => 'rose_gold', 'label' => 'Rose Gold', 'swatch_image' => $foilSwatches.'rose-gold.png'],
            ['code' => 'aged_gold', 'label' => 'Aged Gold', 'swatch_image' => $foilSwatches.'aged-gold.png'],
            ['code' => 'muted_purple_gold', 'label' => 'Muted Purple Gold', 'swatch_image' => $foilSwatches.'muted-purple-gold.png'],
        ];
        $specialFinish = [
            array_replace(
                $this->existingOptionValue($options, 'special_finish', 'no_special_finish'),
                [
                    'label' => 'No finish',
                    'description' => 'No special finish, thanks.',
                    'swatch_image' => '/images/product-options/no-foil.png',
                ],
            ),
            ...array_map(
                fn (array $foil): array => array_replace(
                    $this->existingOptionValue($options, 'special_finish', $foil['code'], $foil),
                    [
                        'label' => $foil['label'],
                        'description' => $foil['label'].' hot foil.',
                        'swatch_image' => $foil['swatch_image'],
                    ],
                ),
                $foils,
            ),
        ];

        $textures = array_map(
            fn (array $texture): array => array_replace(
                $this->existingOptionValue($options, 'texture', $texture['code']),
                [
                    'label' => $texture['label'],
                    'description' => '',
                    'swatch_image' => $texture['swatch_image'],
                ],
            ),
            [
                [
                    'code' => 'matte',
                    'label' => 'Matte',
                    'swatch_image' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
                ],
                ...ClassicSpecialBusinessCardTexture::optionDefinitions(),
            ],
        );

        return $this->orderedOptionGroups(BusinessCardOptionCatalog::normalizeSharedSizeSwatches([
            'sizes' => $group('Size', $sizes, 'standard'),
            'corners' => $group('Corners', $corners, 'square'),
            'special_finish' => [
                ...$group('Special Finish', $specialFinish, 'no_special_finish'),
                'type' => 'multi_select',
            ],
            'texture' => $group('Texture', $textures, 'matte'),
        ], (string) $product->slug));
    }

    /**
     * Move the legacy UV value out of Paper Finish when a card exposes the
     * matte/gloss/UV combination. Paper Finish and UV are mutually exclusive
     * optional alternatives, so neither selection is active by default.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function splitUvPaperFinishOptionGroup(array $options): array
    {
        $paperFinish = is_array($options['paper_finish'] ?? null)
            ? $options['paper_finish']
            : [];
        $values = is_array($paperFinish['values'] ?? null)
            ? $paperFinish['values']
            : [];

        $hasMatte = false;
        $hasGloss = false;
        $hasUv = false;
        $remainingValues = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                $remainingValues[] = $value;

                continue;
            }

            $code = $this->normalizedRuleValue($value['code'] ?? '');
            $label = $this->normalizedRuleValue($value['label'] ?? $value['name'] ?? '');

            if ($this->isUvOptionValue($code) || $this->isUvOptionValue($label)) {
                $hasUv = true;

                continue;
            }

            $hasMatte = $hasMatte || $code === 'matte' || $label === 'matte';
            $hasGloss = $hasGloss || $code === 'gloss' || $label === 'gloss';
            $remainingValues[] = $value;
        }

        $existingUvGroup = is_array($options['uv_finish'] ?? null)
            ? $options['uv_finish']
            : [];
        $existingUvValues = is_array($existingUvGroup['values'] ?? null)
            ? $existingUvGroup['values']
            : [];
        $hasSeparateUv = $this->hasUvSideValues($existingUvValues);

        if (! $hasMatte || ! $hasGloss || (! $hasUv && ! $hasSeparateUv)) {
            return $options;
        }

        $paperFinish['values'] = array_values($remainingValues);

        $paperFinish['required'] = false;

        if (
            $this->isUvOptionValue($paperFinish['default'] ?? '')
            || blank($paperFinish['default'] ?? null)
        ) {
            $paperFinish['default'] = 'matte';
        }

        $options['paper_finish'] = $paperFinish;

        $existingByCode = [];

        foreach ($existingUvValues as $value) {
            if (is_array($value) && isset($value['code'])) {
                $existingByCode[(string) $value['code']] = $value;
            }
        }

        $uvValues = [];

        foreach ([
            'single_side_uv' => 'single side UV',
            'both_sides_uv' => 'both sides UV',
        ] as $code => $label) {
            $uvValues[] = array_replace(
                [
                    'code' => $code,
                    'label' => $label,
                    'swatch_image' => self::UV_FINISH_SWATCH_IMAGE,
                ],
                $existingByCode[$code] ?? [],
                [
                    'code' => $code,
                    'label' => $label,
                    'swatch_image' => self::UV_FINISH_SWATCH_IMAGE,
                ],
            );
        }

        $options['uv_finish'] = array_replace(
            [
                'label' => 'UV',
                'type' => 'select',
                'required' => false,
                'default' => null,
            ],
            $existingUvGroup,
            [
                'label' => 'UV',
                'type' => 'select',
                'required' => false,
                'default' => null,
                'values' => $uvValues,
            ],
        );

        return $options;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function hasUvSideValues(array $values): bool
    {
        $codes = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $codes[] = $this->normalizedRuleValue($value['code'] ?? $value['name'] ?? $value['label'] ?? '');
        }

        return in_array('single_side_uv', $codes, true)
            && in_array('both_sides_uv', $codes, true);
    }

    /**
     * Convert pricing and gallery rules that still match the retired
     * Paper Finish=UV value into rules for both new UV side selections.
     *
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    private function normalizeUvOptionRules(array $rules): array
    {
        $uvRules = [];
        $otherRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                $otherRules[] = $rule;

                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (! array_key_exists('paper_finish', $match) || ! $this->isUvOptionValue($match['paper_finish'])) {
                if (array_key_exists('uv_finish', $match)) {
                    $uvRules[] = $rule;
                } else {
                    $otherRules[] = $rule;
                }

                continue;
            }

            unset($match['paper_finish']);

            foreach (['single_side_uv', 'both_sides_uv'] as $index => $uvCode) {
                $migratedRule = $rule;
                $migratedRule['match'] = [...$match, 'uv_finish' => $uvCode];

                if ($index === 1 && isset($rule['id'])) {
                    $migratedRule['id'] = (string) $rule['id'].'-both-sides';
                }

                $uvRules[] = $migratedRule;
            }
        }

        return array_values([...$uvRules, ...$otherRules]);
    }

    private function isUvOptionValue(mixed $value): bool
    {
        $normalized = $this->normalizedRuleValue($value);

        return in_array($normalized, ['uv', '3d_uv'], true);
    }

    /**
     * Order the shared option groups consistently while preserving the
     * relative order of product-specific groups that follow them. Texture
     * belongs immediately after size and corners when those groups exist.
     *
     * @param  array<string, mixed>  $groups
     * @return array<string, mixed>
     */
    private function orderedOptionGroups(array $groups): array
    {
        $indexed = [];
        $fallbackPriority = count(self::OPTION_GROUP_ORDER) + 1;

        foreach ($groups as $index => $group) {
            $key = (string) $index;

            $indexed[] = [
                'key' => $index,
                'group' => $group,
                'priority' => self::OPTION_GROUP_ORDER[$key] ?? $fallbackPriority,
                'index' => count($indexed),
            ];
        }

        usort($indexed, static function (array $left, array $right): int {
            return [$left['priority'], $left['index']] <=> [$right['priority'], $right['index']];
        });

        $ordered = [];

        foreach ($indexed as $item) {
            $ordered[$item['key']] = $item['group'];
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function existingOptionValue(array $options, string $groupKey, string $code, array $defaults = []): array
    {
        $values = data_get($options, "{$groupKey}.values", []);

        if (is_array($values)) {
            foreach ($values as $value) {
                if (is_array($value) && ($value['code'] ?? null) === $code) {
                    return array_replace($defaults, $value, ['code' => $code]);
                }
            }
        }

        return array_replace(['code' => $code], $defaults);
    }

    private function hasCanonicalConfig(Product $product): bool
    {
        return is_array($product->product_config) && $product->product_config !== [];
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<int, array{quantity: string, discount_percent: float}>
     */
    private function mapToRows(array $map): array
    {
        $rows = [];

        foreach ($map as $quantity => $value) {
            $rows[] = [
                'quantity' => (string) $quantity,
                'discount_percent' => (float) $value,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int|string, float>
     */
    private function rowsToMap(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $map = [];

        foreach ($rows as $row) {
            if (! is_array($row) || blank($row['quantity'] ?? null)) {
                continue;
            }

            $quantity = (string) (int) $row['quantity'];
            $map[$quantity] = (float) ($row['discount_percent'] ?? 0);
        }

        return $map;
    }

    /**
     * @return array<int|string, float>
     */
    private function mapToNumericValues(mixed $map): array
    {
        if (! is_array($map)) {
            return [];
        }

        $values = [];

        foreach ($map as $key => $value) {
            $values[(string) $key] = (float) $value;
        }

        return $values;
    }

    private function processCode(string $name): string
    {
        $normalizedName = strtolower(trim($name));

        if (str_contains($name, '圆角')) {
            return 'rounded_corners';
        }

        return match (trim($name)) {
            '激光' => 'laser',
            '滚边' => 'edge_coloring',
            '对裱' => 'double_mounting',
            '异形模切' => 'custom_die_cut',
            default => $this->legacyProcessCode($name, $normalizedName),
        };
    }

    private function legacyProcessCode(string $name, string $normalizedName): string
    {

        if (str_contains($name, '烫金')) {
            return 'foil';
        }

        if (
            str_contains($name, '打码')
            || str_contains($normalizedName, 'print code')
        ) {
            return 'print_code_or_magnetic_stripe';
        }

        if (
            str_contains($name, '立体uv')
            || str_contains($name, '立体UV')
            || str_contains($name, '冷烫')
            || str_contains($name, '热烫')
            || str_contains($name, '激光雕刻')
            || str_contains($name, '彩印')
            || str_contains($name, '镀色')
            || str_contains($normalizedName, 'special finish')
        ) {
            return 'special_finish';
        }

        return Str::slug($name, '_') ?: 'process';
    }
}
