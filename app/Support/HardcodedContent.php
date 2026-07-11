<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Loads and caches the storefront content tree from
 * `content/hardcoded-content.json`.
 *
 * The JSON is the single source of truth for hardcoded strings, image
 * URLs, and link hrefs in the React storefront. It's shared with the
 * frontend via Inertia and consumed component-side through the
 * `useContent()` hook.
 *
 * Caching is keyed by the file's last-modified timestamp, so saving the
 * JSON file invalidates the cache automatically on the next request —
 * no `php artisan cache:clear` needed.
 */
class HardcodedContent
{
    /** In-process memo to avoid hitting the cache backend twice per request. */
    protected ?array $memo = null;

    /**
     * Return the full content tree, with dev-only metadata keys stripped.
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

        return $this->memo = Cache::remember(
            "hardcoded-content:v1:{$mtime}",
            3600,
            function () use ($path): array {
                $raw = File::get($path);
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

                return $this->stripDevMetadata($decoded);
            }
        );
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
}
