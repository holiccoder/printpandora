<?php

namespace App\Services;

use App\Models\Product;
use App\Support\BusinessCardOptionCatalog;
use App\Support\HardcodedContent;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reads, writes, and adapts the canonical product configuration JSON.
 *
 * The new configuration is stored in products.product_config. During the
 * migration period this service can still read the old product_options JSON
 * or the category product-option files and expose the old flat shape to the
 * storefront.
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
        'paper_finish' => 'Paper Finish',
        'corners' => 'Corners',
        'special_finish' => 'Special Finish',
        'special_finish_on_sides' => 'Special Finish on Sides',
        'print_code' => 'Print Code',
        'drill' => 'Drilling',
    ];

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
     * Return the canonical JSON or import a legacy product configuration for
     * editing.
     *
     * @return array<string, mixed>
     */
    public function canonicalConfig(Product $product): array
    {
        if ($this->hasCanonicalConfig($product)) {
            $config = $this->normalizeCanonicalConfig($product->product_config ?? [], $product);

            $config['options'] = $this->normalizeProductSpecificOptions($config['options'], $product);

            return $config;
        }

        return $this->fromLegacyOptions($product, $this->loadLegacyOptions($product) ?? []);
    }

    /**
     * Build fresh canonical pricing scenarios from the imported reference
     * data for a product. This lets repeatable seeders refresh pricing on
     * products that already have a canonical configuration.
     *
     * @return array<string, array<string, mixed>>|null
     */
    public function dynamicPricingScenarios(Product $product): ?array
    {
        $pricingData = $this->loadDynamicPricingData((string) $product->slug);

        return $pricingData === null ? null : $this->scenariosFromDynamicPricing($pricingData);
    }

    /**
     * Build thickness-matched pricing rules for metal business cards from the
     * imported thin/thick reference files.
     *
     * @return array<int, array{id: string, match: array<string, string>, pricing: array<string, mixed>}>
     */
    public function dynamicPricingRules(Product $product): ?array
    {
        if (! in_array((string) $product->slug, [
            'classic-metal-business-cards',
            'premium-metal-business-cards',
            'luxe-metal-business-cards',
        ], true)) {
            return null;
        }

        $scenarios = $this->dynamicPricingScenarios($product);

        if ($scenarios === null) {
            return null;
        }

        $rules = [];

        foreach ($scenarios as $thickness => $scenario) {
            $rules[] = [
                'id' => "metal-thickness-{$thickness}",
                'match' => ['thickness' => (string) $thickness],
                'pricing' => $this->scenarioToPricingJson($scenario),
            ];
        }

        return $rules === [] ? null : $rules;
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

            $rows[] = [
                'row_key' => (string) $key,
                'key' => (string) $key,
                'label' => (string) ($group['label'] ?? Str::headline((string) $key)),
                'type' => ($group['type'] ?? 'select') === 'multi_select' ? 'multi_select' : 'select',
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

                foreach (['description', 'swatch_image', 'width', 'height'] as $property) {
                    if (array_key_exists($property, $value) && $value[$property] !== '') {
                        $normalizedValue[$property] = $value[$property];
                    }
                }

                $values[] = $normalizedValue;
            }

            $options[$key] = [
                'label' => $label !== '' ? $label : Str::headline($key),
                'type' => ($row['type'] ?? 'select') === 'multi_select' ? 'multi_select' : 'select',
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
                $pricing = is_array($rule['pricing'] ?? null) ? $rule['pricing'] : [];

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

            $rows[] = [
                'id' => "pricing-{$scenarioKey}",
                'match_conditions' => $this->conditionsToRows(
                    $this->scenarioMatchConditions((string) $scenarioKey, $config['options'] ?? []),
                ),
                'pricing_json' => $this->encodePricingJson($this->scenarioToPricingJson($scenario)),
            ];
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

                return [
                    'name' => (string) ($process['label'] ?? $process['name'] ?? ''),
                    'code' => (string) ($process['code'] ?? ''),
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
        $sizes = is_array($sizeGroup['values'] ?? null) ? $sizeGroup['values'] : [];
        $finishes = is_array($finishGroup['values'] ?? null) ? $finishGroup['values'] : [];

        $standard = $sizes[0]['code'] ?? null;
        $square = collect($sizes)->first(function (mixed $value): bool {
            if (! is_array($value)) {
                return false;
            }

            return $this->normalizedRuleValue($value['code'] ?? '') === 'square'
                || $this->normalizedRuleValue($value['label'] ?? '') === 'square';
        });
        $square = is_array($square) ? ($square['code'] ?? null) : ($sizes[1]['code'] ?? null);
        $uv = collect($finishes)->first(function (mixed $value): bool {
            if (! is_array($value)) {
                return false;
            }

            return $this->normalizedRuleValue($value['code'] ?? '') === 'uv'
                || $this->normalizedRuleValue($value['label'] ?? '') === 'uv';
        });
        $uv = is_array($uv) ? ($uv['code'] ?? null) : null;

        if ($scenario === 'rectangle' && $standard) {
            $match['sizes'] = (string) $standard;
        }

        if ($scenario === 'uv' && $standard) {
            $match['sizes'] = (string) $standard;
            if ($uv) {
                $match['paper_finish'] = (string) $uv;
            }
        }

        if ($scenario === 'square' && $square) {
            $match['sizes'] = (string) $square;
        }

        if ($scenario === 'square_uv' && $square) {
            $match['sizes'] = (string) $square;
            if ($uv) {
                $match['paper_finish'] = (string) $uv;
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
        if ($this->hasCanonicalConfig($product)) {
            return $this->withResolvedStorefrontImages($this->withSharedBusinessCardDetailSections(
                $this->toStorefrontOptions($this->canonicalConfig($product), $product),
                $product,
            ));
        }

        $legacy = $this->loadLegacyOptions($product);

        if ($legacy === null && ! BusinessCardOptionCatalog::supports((string) $product->slug)) {
            return null;
        }

        $legacy ??= [];

        if (! isset($legacy['pricing_data'])) {
            $pricingData = $this->loadDynamicPricingData($product->slug ?? '');

            if ($pricingData !== null) {
                $legacy['pricing_data'] = $pricingData;
            }
        }

        if (
            $product->slug === 'classic-special-business-cards'
            || BusinessCardOptionCatalog::supports((string) $product->slug)
        ) {
            $canonical = $this->fromLegacyOptions($product, $legacy);

            return $this->withResolvedStorefrontImages(
                $this->withSharedBusinessCardDetailSections(
                    $this->toStorefrontOptions($canonical, $product),
                    $product,
                ),
            );
        }

        return $this->withResolvedStorefrontImages(
            $this->withSharedBusinessCardDetailSections($legacy, $product),
        );
    }

    /**
     * Apply the centrally maintained business-card detail sections. A shared
     * design specification is used only when the product does not define one;
     * product-specific dimensions and downloads remain authoritative. FAQ and
     * cross-sell content are also preserved. Eligibility follows the Business
     * Cards category hierarchy so direct products and descendants share the
     * same section contract.
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

        if (
            ! is_array($details['design_specifications'] ?? null)
            && is_array($shared['design_specifications'] ?? null)
        ) {
            $details['design_specifications'] = $shared['design_specifications'];
        }

        foreach (['design_service_banner', 'paper_stocks'] as $key) {
            if (is_array($shared[$key] ?? null)) {
                $details[$key] = $shared[$key];
            }
        }

        $options['detail_sections'] = $details;

        return $options;
    }

    private function belongsToBusinessCardCategory(Product $product): bool
    {
        $category = $product->category;
        $visited = [];

        while ($category !== null) {
            if ($category->slug === 'business-cards') {
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
     * @return array<string, mixed>|null
     */
    private function loadLegacyOptions(Product $product): ?array
    {
        if (is_array($product->product_options) && $product->product_options !== []) {
            return $product->product_options;
        }

        $categorySlug = $product->category?->slug;

        if (! $categorySlug || ! $product->slug) {
            return null;
        }

        $path = base_path("content/product-options/{$categorySlug}/{$product->slug}.json");

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $decoded = $content === false ? null : json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function fromLegacyOptions(Product $product, array $legacy): array
    {
        $pricingData = $this->loadDynamicPricingData($product->slug ?? '');
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
                'subtitle' => $legacy['subtitle'] ?? $product->subtitle,
                'description' => $product->description,
                'description_title' => $product->description_title,
                'bullet_points' => $product->bullet_points ?? [],
                'featured_image' => $product->featured_image,
                'meta_description' => $product->meta_description,
            ],
            'options' => $this->normalizeProductSpecificOptions(
                $this->optionsFromLegacy($legacy),
                $product,
            ),
            'media' => [
                'gallery' => is_array($defaultGallery['images'] ?? null)
                    ? array_values($defaultGallery['images'])
                    : [],
                'gallery_rules' => $this->galleryRulesFromLegacy($galleries),
            ],
            'pricing' => [
                'mode' => $pricingData !== null ? 'rule_based' : 'fixed_tiers',
                'currency' => 'USD',
                'total_rounding' => 'nearest_integer',
                'scenarios' => $pricingData !== null
                    ? $this->scenariosFromDynamicPricing($pricingData)
                    : [],
                'quantity_price_table' => $pricingData === null && is_array($legacy['quantity_price_table'] ?? null)
                    ? array_values($legacy['quantity_price_table'])
                    : [],
            ],
            'faq' => $this->faqFromLegacy($legacy),
            'detail_sections' => is_array($legacy['detail_sections'] ?? null)
                ? $legacy['detail_sections']
                : [],
        ];

        return $this->normalizeCanonicalConfig($config, $product);
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, array<string, mixed>>
     */
    private function optionsFromLegacy(array $legacy): array
    {
        $options = [];
        $groupLabels = self::OPTION_GROUP_LABELS;

        if (array_key_exists('texture', $legacy)) {
            $groupLabels['texture'] = 'Texture';
        }

        foreach ([
            'thickness' => 'Thickness',
            'print_code_or_signature_stripe' => 'Print Code or Signature Stripe',
            'print_code_or_magnetic_stripe' => 'Print Code or Magnetic Stripe',
            'with_nfc' => 'With NFC',
        ] as $key => $label) {
            if (array_key_exists($key, $legacy)) {
                $groupLabels[$key] = $label;
            }
        }

        foreach ($groupLabels as $key => $label) {
            $items = is_array($legacy[$key] ?? null) ? $legacy[$key] : [];

            $options[$key] = [
                'label' => $label,
                'type' => 'select',
                'required' => true,
                'default' => $items[0]['code'] ?? null,
                'values' => array_values(array_map(function (mixed $item): array {
                    if (! is_array($item)) {
                        return [];
                    }

                    $value = [
                        'code' => (string) ($item['code'] ?? Str::slug((string) ($item['name'] ?? ''), '_')),
                        'label' => (string) ($item['name'] ?? ''),
                    ];

                    foreach (['description', 'swatch_image', 'width', 'height'] as $property) {
                        if (array_key_exists($property, $item) && $item[$property] !== '') {
                            $value[$property] = $item[$property];
                        }
                    }

                    return $value;
                }, $items)),
            ];
        }

        return $options;
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

            return [
                'id' => (string) ($gallery['id'] ?? "gallery-{$index}"),
                'match' => is_array($gallery['match'] ?? null) ? $gallery['match'] : [],
                'images' => $images,
                'primary' => $images[0] ?? null,
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
    private function toStorefrontOptions(array $config, Product $product): array
    {
        $options = [];
        $optionGroups = [];

        foreach ($config['options'] ?? [] as $key => $group) {
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

                foreach (['description', 'swatch_image', 'width', 'height'] as $property) {
                    if (array_key_exists($property, $value)) {
                        $legacy[$property] = $property === 'swatch_image'
                            ? $this->storefrontImageUrl($value[$property])
                            : $value[$property];
                    }
                }

                return $legacy;
            }, is_array($group['values'] ?? null) ? $group['values'] : []));

            $optionKey = (string) $key;
            $optionGroups[] = [
                'key' => $optionKey,
                'label' => (string) ($group['label'] ?? Str::headline($optionKey)),
                'type' => ($group['type'] ?? 'select') === 'multi_select'
                    ? 'multi_select'
                    : 'select',
                'required' => (bool) ($group['required'] ?? true),
                'default' => (string) ($group['default'] ?? ($values[0]['code'] ?? '')),
                'values' => $values,
            ];
            $options[$optionKey] = $values;
        }

        $hasDynamicOptions = $this->hasCanonicalConfig($product)
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
        $options['subtitle'] = data_get($config, 'product.subtitle', $product->subtitle);

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
                'pricing' => $rule['pricing'],
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
        $config['media'] = is_array($config['media'] ?? null) ? $config['media'] : [];
        $config['media']['gallery'] = is_array($config['media']['gallery'] ?? null)
            ? array_values($config['media']['gallery'])
            : [];
        $config['media']['gallery_rules'] = is_array($config['media']['gallery_rules'] ?? null)
            ? array_values($config['media']['gallery_rules'])
            : [];
        $config['pricing'] = array_replace([
            'mode' => 'fixed_tiers',
            'currency' => 'USD',
            'total_rounding' => 'nearest_integer',
            'scenarios' => [],
            'quantity_price_table' => [],
            'rules' => [],
        ], is_array($config['pricing'] ?? null) ? $config['pricing'] : []);
        $config['faq'] = is_array($config['faq'] ?? null) ? array_values($config['faq']) : [];

        return $config;
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
        $catalogOptions = BusinessCardOptionCatalog::normalize((string) $product->slug, $options);

        if ($catalogOptions !== null) {
            return $catalogOptions;
        }

        if ($product->slug !== 'classic-special-business-cards') {
            return $options;
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
                    'swatch_image' => '/images/product-options/business-cards/swatches/standard-size.webp',
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
                    'swatch_image' => '/images/product-options/business-cards/swatches/square-size.webp',
                ],
            ),
            [
                'code' => 'custom',
                'label' => 'Custom',
                'description' => 'Enter a custom width and height from 2.1 to 3.5 inches.',
                'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
            ],
        ];

        $paperFinish = [
            array_replace(
                $this->existingOptionValue($options, 'paper_finish', 'matte', ['label' => 'Matte']),
                [
                    'label' => 'Matte',
                    'description' => 'With a smooth feel. Shine-free so no glare.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
                ],
            ),
            array_replace(
                $this->existingOptionValue($options, 'paper_finish', 'gloss', ['label' => 'Gloss']),
                [
                    'label' => 'Gloss',
                    'description' => 'Eye-catchingly shiny. Makes color photos pop.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
                ],
            ),
            array_replace(
                $this->existingOptionValue($options, 'paper_finish', 'uv', ['label' => '3D UV']),
                [
                    'label' => '3D UV',
                    'description' => 'Raised gloss highlights with a dimensional feel.',
                    'swatch_image' => '/images/product-options/uv-swatch.png',
                ],
            ),
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
                    'label' => 'No special finish',
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

        $specialFinishOnSides = [
            array_replace(
                $this->existingOptionValue($options, 'special_finish_on_sides', 'one_side', ['label' => 'One side']),
                [
                    'label' => 'One side',
                    'description' => 'Special finish applied to one side only.',
                    'swatch_image' => '/images/product-options/business-cards/special-finishes/special-finish-one-side.png',
                ],
            ),
            array_replace(
                $this->existingOptionValue($options, 'special_finish_on_sides', 'both_sides', ['label' => 'Both sides']),
                [
                    'label' => 'Both sides',
                    'description' => 'Special finish applied to both sides.',
                    'swatch_image' => '/images/product-options/business-cards/special-finishes/special-finish-both-sides.png',
                ],
            ),
        ];

        $textures = array_map(
            fn (array $texture): array => array_replace(
                $this->existingOptionValue($options, 'texture', $texture['code']),
                [
                    'label' => $texture['label'],
                    'description' => '',
                    'swatch_image' => '',
                ],
            ),
            [
                ['code' => 'pin_hole_paper', 'label' => 'Pin-hole Paper'],
                ['code' => 'water_ripple_paper', 'label' => 'Water Ripple Paper'],
                ['code' => 'linen_paper', 'label' => 'Linen Paper'],
                ['code' => 'eggshell_paper', 'label' => 'Eggshell Paper'],
                ['code' => 'white_cardstock', 'label' => 'White Cardstock'],
                ['code' => 'pearlized_paper', 'label' => 'Pearlized Paper'],
            ],
        );

        return [
            'sizes' => $group('Size', $sizes, 'standard'),
            'corners' => $group('Corners', $corners, 'square'),
            'paper_finish' => $group('Paper Finish', $paperFinish, 'matte'),
            'special_finish' => $group('Special Finish', $specialFinish, 'no_special_finish'),
            'special_finish_on_sides' => $group('Special Finish on Sides', $specialFinishOnSides, 'one_side'),
            'texture' => $group('Texture', $textures, 'pin_hole_paper'),
        ];
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

        if (str_contains($name, '烫金')) {
            return 'foil';
        }

        if ($normalizedName === 'nfc') {
            return 'nfc';
        }

        if (
            str_contains($name, '打码')
            || str_contains($normalizedName, 'print code')
        ) {
            return 'print_code_or_magnetic_stripe';
        }

        if (
            str_contains($name, '激光雕刻')
            || str_contains($name, '彩印')
            || str_contains($name, '镀色')
            || str_contains($normalizedName, 'special finish')
        ) {
            return 'special_finish';
        }

        return Str::slug($name, '_') ?: 'process';
    }

    /**
     * @return array<string, array{dir: string, files: array<string, string>}>
     */
    private function pricingFileMap(): array
    {
        return [
            'classic-standard-business-cards' => [
                'dir' => '300g铜版纸',
                'files' => [
                    'rectangle' => '300g铜版纸 长方形.json',
                    'uv' => '300g铜版纸 uv.json',
                    'square' => '300g铜版纸 正方形.json',
                    'square_uv' => '300g铜版纸 正方形uv.json',
                ],
            ],
            'classic-special-business-cards' => [
                'dir' => '300g艺术纸',
                'files' => [
                    'rectangle' => '300g艺术纸-荷兰白卡.json',
                    'square' => '300g艺术纸-荷兰白卡-正方形.json',
                ],
            ],
            'classic-quality-business-cards' => [
                'dir' => '320g铜版纸',
                'files' => [
                    'rectangle' => '320g铜版纸.json',
                    'square' => '320g铜版纸-正方形.json',
                ],
            ],
            'classic-solid-business-cards' => [
                'dir' => '350g白卡',
                'files' => [
                    'rectangle' => '350g白卡.json',
                    'square' => '350g白卡-正方形.json',
                ],
            ],
            'basic-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => ['rectangle' => '棉纸-基础型.json'],
            ],
            'classic-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => ['rectangle' => '棉纸-经典型.json'],
            ],
            'premium-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => ['rectangle' => '棉纸-高级型.json'],
            ],
            'luxe-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => ['rectangle' => '棉纸-奢华型.json'],
            ],
            'grand-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => ['rectangle' => '棉纸-豪华型.json'],
            ],
            'classic-metal-business-cards' => [
                'dir' => '金卡',
                'files' => [
                    '0_3_mm' => '金卡-经典型薄款.json',
                    '0_5_mm' => '金卡-经典型厚款.json',
                ],
            ],
            'premium-metal-business-cards' => [
                'dir' => '金卡',
                'files' => [
                    '0_3_mm' => '金卡-高级型薄款.json',
                    '0_5_mm' => '金卡-高级型厚款.json',
                ],
            ],
            'luxe-metal-business-cards' => [
                'dir' => '金卡',
                'files' => [
                    '0_3_mm' => '金卡-豪华型薄款.json',
                    '0_5_mm' => '金卡-豪华型厚款.json',
                ],
            ],
            'basic-pvc-card' => [
                'dir' => 'pvc',
                'files' => ['rectangle' => 'pvc0.38.json'],
            ],
            'standard-pvc-card' => [
                'dir' => 'pvc',
                'files' => ['rectangle' => 'pvc0.76.json'],
            ],
            'premium-pvc-card' => [
                'dir' => 'pvc',
                'files' => ['rectangle' => 'pvc0.84.json'],
            ],
            'super-business-cards' => [
                'dir' => '350g精品纸',
                'files' => [
                    'rectangle' => '350g精品纸.json',
                    'square' => '350g精品纸-正方形.json',
                ],
            ],
            'luxe-business-cards' => [
                'dir' => '700g精品纸',
                'files' => [
                    'rectangle' => '700g精品纸.json',
                    'square' => '700g精品纸-正方形.json',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function loadDynamicPricingData(string $slug): ?array
    {
        $config = $this->pricingFileMap()[$slug] ?? null;

        if ($config === null) {
            return null;
        }

        $basePath = base_path('storage/from-tool/数据文档/'.$config['dir']);
        $data = [];

        foreach ($config['files'] as $key => $file) {
            $path = $basePath.'/'.$file;

            if (! file_exists($path)) {
                return null;
            }

            $content = file_get_contents($path);
            $decoded = $content === false ? null : json_decode($content, true);

            if (! is_array($decoded)) {
                return null;
            }

            $data[$key] = $decoded;
        }

        return $data;
    }
}
