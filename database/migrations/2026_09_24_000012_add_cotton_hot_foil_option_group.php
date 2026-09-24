<?php

use App\Support\BusinessCardOptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $config = $this->decode($product->product_config ?? null);

                if (! is_array($config['options'] ?? null)) {
                    return;
                }

                $options = BusinessCardOptionCatalog::normalize(
                    (string) $product->slug,
                    $config['options'],
                );

                if ($options === null || $options === $config['options']) {
                    return;
                }

                $config['options'] = $options;

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'product_config' => $this->encode($config),
                    ]);
            });
    }

    public function down(): void
    {
        // The shared cotton option contract is intentionally retained.
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
