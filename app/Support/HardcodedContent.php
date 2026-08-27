<?php

namespace App\Support;

use App\Services\ProductImageResolver;
use App\Services\SiteSettingsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Loads and caches the storefront content tree from
 * `content/hardcoded-content.json`.
 *
 * The JSON provides the storefront fallback content. Administrator-managed
 * settings overlay editable sections before the tree is shared with the
 * frontend via Inertia and consumed through the `useContent()` hook.
 *
 * Caching is keyed by the file's last-modified timestamp, so saving the
 * JSON file invalidates the cache automatically on the next request —
 * no `php artisan cache:clear` needed.
 */
class HardcodedContent
{
    /** In-process memo to avoid hitting the cache backend twice per request. */
    /** @var array<array-key, mixed>|null */
    protected ?array $memo = null;

    /**
     * Return the full content tree, with dev-only metadata keys stripped.
     *
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $path = $this->path();
        // filemtime() can be opcache-stale on Windows; clear it explicitly.
        clearstatcache(true, $path);
        $mtime = @filemtime($path) ?: 0;
        $settings = app(SiteSettingsService::class);
        $settingsVersion = $settings->cacheVersion();

        return $this->memo = Cache::remember(
            "hardcoded-content:v2:{$mtime}:{$settingsVersion}",
            3600,
            function () use ($path, $settings): array {
                $raw = File::get($path);
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $content = $this->stripDevMetadata($decoded);
                $homepageOverrides = $settings->homepage();

                if ($homepageOverrides !== []) {
                    $content['home_page'] = $settings->mergeHomepage(
                        is_array($content['home_page'] ?? null) ? $content['home_page'] : [],
                        $homepageOverrides,
                    );
                }

                $globalChromeOverrides = $settings->globalChrome();

                if ($globalChromeOverrides !== []) {
                    $content['global_chrome'] = $settings->mergeGlobalChrome(
                        is_array($content['global_chrome'] ?? null) ? $content['global_chrome'] : [],
                        $globalChromeOverrides,
                    );
                }

                return $this->resolveHomepageImages($content);
            }
        );
    }

    /**
     * Clear the per-request memo after settings are saved in a Livewire
     * request, and keep the service deterministic in tests.
     */
    public function forget(): void
    {
        $this->memo = null;
    }

    /**
     * Dot-notation accessor for a single section/leaf, with a default.
     * Example: $svc->section('home_page.hero_carousel.slides').
     */
    public function section(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Absolute path to the JSON file. A config override lets tests point
     * at a fixture without touching the real content tree.
     */
    protected function path(): string
    {
        return config('content.path', base_path('content/hardcoded-content.json'));
    }

    /**
     * Recursively strip any key whose name starts with `_` (e.g. `_meta`,
     * `_source_file`, `_source_files`, `_note`). Numeric keys are kept so
     * lists of objects survive intact.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    protected function stripDevMetadata(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && str_starts_with($key, '_')) {
                continue;
            }
            $out[$key] = is_array($value) ? $this->stripDevMetadata($value) : $value;
        }

        return $out;
    }

    /**
     * Resolve uploaded carousel source paths to their preferred WebP URL,
     * while leaving repository-managed absolute URLs untouched.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    protected function resolveHomepageImages(array $content): array
    {
        $slides = Arr::get($content, 'home_page.hero_carousel.slides', []);

        if (! is_array($slides)) {
            return $content;
        }

        $resolver = app(ProductImageResolver::class);

        foreach ($slides as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            foreach (['image_url', 'mobile_image_url'] as $imageKey) {
                if (! is_string($slide[$imageKey] ?? null)) {
                    continue;
                }

                $content['home_page']['hero_carousel']['slides'][$index][$imageKey] = $resolver->url(
                    $slide[$imageKey],
                );
            }
        }

        return $content;
    }
}
