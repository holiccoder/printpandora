<?php

namespace App\Support;

final class ProductImagePolicy
{
    public const STOREFRONT_CONVERSION = 'storefront';

    public const MAX_DIMENSION = 2000;

    public const WEBP_QUALITY = 85;

    public const ORIGINALS_DIRECTORY = 'product-upload-originals';

    public const FAILURE_MARKER_SUFFIX = '.failed.json';

    public const MEDIA_FAILURE_PROPERTY = 'storefront_conversion_failure';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ORIGINAL = 'original';

    /**
     * @var array<int, string>
     */
    public const MEDIA_COLLECTIONS = [
        'gallery',
        'product-galleries',
        'product-gallery-overrides',
        'product-featured-overrides',
    ];

    /**
     * @var array<int, string>
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
}
