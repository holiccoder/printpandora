<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const DEFAULT_FIRST_DESKTOP_IMAGE = '/images/home/homepage-carousel-01.png';

    private const DEFAULT_THIRD_DESKTOP_IMAGE = '/images/home/homepage-carousel-03.webp';

    private const NEW_THIRD_DESKTOP_IMAGE = '/images/home/homepage-carousel-03-ryft-metal-cutout.png';

    public function up(): void
    {
        $this->updateSlides(function (array $slides): array {
            if (! $this->hasImageSlides($slides)) {
                return $slides;
            }

            if (($slides[2]['image_url'] ?? null) === self::NEW_THIRD_DESKTOP_IMAGE) {
                return $slides;
            }

            $currentThirdImage = $slides[2]['image_url'] ?? null;

            if (! is_string($currentThirdImage) || $currentThirdImage === '') {
                return $slides;
            }

            $slides[0]['image_url'] = $currentThirdImage;
            $slides[2]['image_url'] = self::NEW_THIRD_DESKTOP_IMAGE;

            return $slides;
        });
    }

    public function down(): void
    {
        $this->updateSlides(function (array $slides): array {
            if (! $this->hasImageSlides($slides)) {
                return $slides;
            }

            if (
                ($slides[0]['image_url'] ?? null) !== self::DEFAULT_THIRD_DESKTOP_IMAGE
                || ($slides[2]['image_url'] ?? null) !== self::NEW_THIRD_DESKTOP_IMAGE
            ) {
                return $slides;
            }

            $slides[0]['image_url'] = self::DEFAULT_FIRST_DESKTOP_IMAGE;
            $slides[2]['image_url'] = self::DEFAULT_THIRD_DESKTOP_IMAGE;

            return $slides;
        });
    }

    /**
     * @param  callable(array<int, array<string, mixed>>): array<int, array<string, mixed>>  $transform
     */
    private function updateSlides(callable $transform): void
    {
        $setting = SiteSetting::query()
            ->where('key', 'site')
            ->first();

        if ($setting === null) {
            return;
        }

        $value = is_array($setting->value) ? $setting->value : [];
        $slides = data_get($value, 'homepage.hero_carousel.slides');

        if (! is_array($slides)) {
            return;
        }

        $slides = array_values($slides);
        $updatedSlides = $transform($slides);

        if ($updatedSlides === $slides) {
            return;
        }

        data_set($value, 'homepage.hero_carousel.slides', $updatedSlides);
        $setting->value = $value;
        $setting->save();
    }

    /**
     * @param  array<int, mixed>  $slides
     */
    private function hasImageSlides(array $slides): bool
    {
        return is_array($slides[0] ?? null) && is_array($slides[2] ?? null);
    }
};
