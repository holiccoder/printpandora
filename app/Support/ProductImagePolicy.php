<?php

namespace App\Support;

final class ProductImagePolicy
{
    public const STOREFRONT_CONVERSION = 'storefront';

    public const MAX_DIMENSION = 2000;

    public const WEBP_QUALITY = 85;

    public const ORIGINALS_DIRECTORY = 'product-upload-originals';

    /**
     * @var array<int, string>
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
}
