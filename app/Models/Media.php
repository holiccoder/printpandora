<?php

namespace App\Models;

use App\Services\MediaLibraryCatalog;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    protected static function booted(): void
    {
        static::saved(function (): void {
            app(MediaLibraryCatalog::class)->invalidate();
        });
        static::deleted(function (): void {
            app(MediaLibraryCatalog::class)->invalidate();
        });
    }
}
