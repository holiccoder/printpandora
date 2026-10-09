<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'solid-quality-business-cards';

    /** @var list<string> */
    private const REMOVED_OPTION_GROUPS = [
        'drill',
        'drilling',
        'print_code',
    ];

    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if ($product === null) {
            return;
        }

        $updates = [];

        $config = $this->decode($product->product_config ?? null);
        if (is_array($config['options'] ?? null)) {
            $config['options'] = $this->withoutRemovedOptionGroups($config['options']);
        }

        if ($config !== $this->decode($product->product_config ?? null)) {
            $updates['product_config'] = $this->encode($config);
        }

        $legacyOptions = $this->decode($product->product_options ?? null);
        $normalizedLegacyOptions = $this->withoutRemovedOptionGroups($legacyOptions);

        if ($normalizedLegacyOptions !== $legacyOptions) {
            $updates['product_options'] = $this->encode($normalizedLegacyOptions);
        }

        if ($updates !== []) {
            DB::table('products')
                ->where('id', $product->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // Removed option groups are not restored on rollback.
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function withoutRemovedOptionGroups(array $options): array
    {
        foreach (array_keys($options) as $key) {
            if (in_array($this->normalizeKey((string) $key), self::REMOVED_OPTION_GROUPS, true)) {
                unset($options[$key]);
            }
        }

        if (is_array($options['options'] ?? null)) {
            $options['options'] = $this->withoutRemovedOptionGroups($options['options']);
        }

        if (is_array($options['option_groups'] ?? null)) {
            $options['option_groups'] = array_values(array_filter(
                $options['option_groups'],
                fn (mixed $group): bool => ! is_array($group)
                    || ! in_array(
                        $this->normalizeKey((string) ($group['key'] ?? '')),
                        self::REMOVED_OPTION_GROUPS,
                        true,
                    ),
            ));
        }

        return $options;
    }

    private function normalizeKey(string $key): string
    {
        return str_replace(['-', ' '], '_', strtolower(trim($key)));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $encoded): array
    {
        if (is_array($encoded)) {
            return $encoded;
        }

        if (! is_string($encoded) || trim($encoded) === '') {
            return [];
        }

        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
};
