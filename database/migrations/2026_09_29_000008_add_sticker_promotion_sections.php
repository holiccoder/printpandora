<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sections = [
            'free_sample' => StickerProductCatalog::freeSampleSection(),
            'sticker_templates' => StickerProductCatalog::stickerTemplatesSection(),
        ];

        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($sections): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === null) {
                    return;
                }

                $details = is_array($config['detail_sections'] ?? null)
                    ? $config['detail_sections']
                    : [];
                $changed = false;

                foreach ($sections as $key => $section) {
                    if (($details[$key] ?? null) === $section) {
                        continue;
                    }

                    $details[$key] = $section;
                    $changed = true;
                }

                if (! $changed) {
                    return;
                }

                $config['detail_sections'] = $details;

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'product_config' => $this->encode($config),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        $sections = [
            'free_sample' => StickerProductCatalog::freeSampleSection(),
            'sticker_templates' => StickerProductCatalog::stickerTemplatesSection(),
        ];

        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($sections): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === null) {
                    return;
                }

                $details = is_array($config['detail_sections'] ?? null)
                    ? $config['detail_sections']
                    : [];
                $changed = false;

                foreach ($sections as $key => $section) {
                    if (($details[$key] ?? null) !== $section) {
                        continue;
                    }

                    unset($details[$key]);
                    $changed = true;
                }

                if (! $changed) {
                    return;
                }

                $config['detail_sections'] = $details;

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'product_config' => $this->encode($config),
                        'updated_at' => now(),
                    ]);
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
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
