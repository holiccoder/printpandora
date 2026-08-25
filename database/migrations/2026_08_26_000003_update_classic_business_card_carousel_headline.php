<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
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

        $changed = false;

        foreach ($slides as $index => $slide) {
            if (
                ! is_array($slide)
                || ($slide['headline'] ?? null) !== 'Original Business Cards, designed to be remembered'
            ) {
                continue;
            }

            $slides[$index]['headline'] = 'Classic Business Cards, designed to be remembered';
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        data_set($value, 'homepage.hero_carousel.slides', array_values($slides));
        $setting->value = $value;
        $setting->save();
    }

    public function down(): void
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

        $changed = false;

        foreach ($slides as $index => $slide) {
            if (
                ! is_array($slide)
                || ($slide['headline'] ?? null) !== 'Classic Business Cards, designed to be remembered'
            ) {
                continue;
            }

            $slides[$index]['headline'] = 'Original Business Cards, designed to be remembered';
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        data_set($value, 'homepage.hero_carousel.slides', array_values($slides));
        $setting->value = $value;
        $setting->save();
    }
};
