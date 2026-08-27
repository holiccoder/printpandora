<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const FIRST_DESKTOP_IMAGE = '/images/home/homepage-carousel-01.png';

    private const PREVIOUS_FIRST_DESKTOP_IMAGE = '/images/home/homepage-carousel-01.webp';

    private const MOBILE_IMAGE_PREFIX = '/images/home/mobile-banners/';

    public function up(): void
    {
        $this->updateSlides(function (array $slide, int $index): array {
            if ($index === 0) {
                $slide['image_url'] = self::FIRST_DESKTOP_IMAGE;
            }

            if ($index < 10) {
                $slide['mobile_image_url'] = self::MOBILE_IMAGE_PREFIX.str_pad(
                    (string) ($index + 1),
                    2,
                    '0',
                    STR_PAD_LEFT,
                ).'-mobile.png';
            }

            return $slide;
        });
    }

    public function down(): void
    {
        $this->updateSlides(function (array $slide, int $index): array {
            if ($index === 0 && ($slide['image_url'] ?? null) === self::FIRST_DESKTOP_IMAGE) {
                $slide['image_url'] = self::PREVIOUS_FIRST_DESKTOP_IMAGE;
            }

            if ($index < 10) {
                $expectedMobileImage = self::MOBILE_IMAGE_PREFIX.str_pad(
                    (string) ($index + 1),
                    2,
                    '0',
                    STR_PAD_LEFT,
                ).'-mobile.png';

                if (($slide['mobile_image_url'] ?? null) === $expectedMobileImage) {
                    unset($slide['mobile_image_url']);
                }
            }

            return $slide;
        });
    }

    /**
     * @param  callable(array<string, mixed>, int): array<string, mixed>  $transform
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

        $updatedSlides = [];

        foreach (array_values($slides) as $index => $slide) {
            $updatedSlides[] = is_array($slide)
                ? $transform($slide, $index)
                : $slide;
        }

        if ($updatedSlides === $slides) {
            return;
        }

        data_set($value, 'homepage.hero_carousel.slides', $updatedSlides);
        $setting->value = $value;
        $setting->save();
    }
};
