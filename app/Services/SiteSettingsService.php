<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class SiteSettingsService
{
    public const KEY = 'site';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        $value = SiteSetting::query()
            ->where('key', self::KEY)
            ->value('value');

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function homepage(): array
    {
        $homepage = Arr::get($this->all(), 'homepage', []);

        return is_array($homepage) ? $homepage : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function globalChrome(): array
    {
        $globalChrome = Arr::get($this->all(), 'global_chrome', []);

        return is_array($globalChrome) ? $globalChrome : [];
    }

    /**
     * Provide a cache-key component that changes when the setting value
     * changes, even if two updates happen within the same timestamp tick.
     */
    public function cacheVersion(): string
    {
        if (! $this->tableExists()) {
            return 'none';
        }

        $setting = SiteSetting::query()
            ->where('key', self::KEY)
            ->first(['updated_at', 'value']);

        if ($setting === null) {
            return 'none';
        }

        return sha1((string) $setting->updated_at.'|'.json_encode($setting->value));
    }

    /**
     * Save one or more settings sections while retaining sections managed by
     * other tabs. Carousel slides are replaced as a list so deletions remain
     * deletions rather than being merged back by numeric array indexes.
     *
     * @param  array<string, mixed>  $sections
     */
    public function saveSections(array $sections): void
    {
        $settings = $this->all();

        foreach ($sections as $section => $value) {
            if ($section === 'homepage' && is_array($value)) {
                $settings[$section] = $this->mergeHomepage(
                    is_array($settings['homepage'] ?? null) ? $settings['homepage'] : [],
                    $value,
                );

                continue;
            }

            if ($section === 'global_chrome' && is_array($value)) {
                $settings[$section] = $this->mergeGlobalChrome(
                    is_array($settings['global_chrome'] ?? null) ? $settings['global_chrome'] : [],
                    $value,
                );

                continue;
            }

            $settings[$section] = $value;
        }

        SiteSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $settings],
        );
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function mergeHomepage(array $base, array $overrides): array
    {
        $merged = array_replace_recursive($base, $overrides);

        if (
            isset($overrides['hero_carousel'])
            && is_array($overrides['hero_carousel'])
            && array_key_exists('slides', $overrides['hero_carousel'])
        ) {
            $merged['hero_carousel']['slides'] = array_values(
                array_filter(
                    is_array($overrides['hero_carousel']['slides'])
                        ? $overrides['hero_carousel']['slides']
                        : [],
                    is_array(...),
                ),
            );
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function mergeGlobalChrome(array $base, array $overrides): array
    {
        $merged = array_replace_recursive($base, $overrides);

        if (
            isset($overrides['announcement_bar'])
            && is_array($overrides['announcement_bar'])
            && array_key_exists('default_messages', $overrides['announcement_bar'])
        ) {
            $merged['announcement_bar']['default_messages'] = array_values(
                array_filter(
                    is_array($overrides['announcement_bar']['default_messages'])
                        ? $overrides['announcement_bar']['default_messages']
                        : [],
                    is_array(...),
                ),
            );
        }

        return $merged;
    }

    protected function tableExists(): bool
    {
        return Schema::hasTable('site_settings');
    }
}
