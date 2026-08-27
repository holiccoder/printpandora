<?php

namespace App\Support;

final class BusinessCardRoutes
{
    /**
     * Public route segments keyed by the existing internal product slugs.
     *
     * @var array<string, string>
     */
    private const ROUTE_SEGMENTS = [
        'basic-cotton-business-card' => 'basic-cotton',
        'classic-cotton-business-card' => 'classic-cotton',
        'premium-cotton-business-card' => 'premium-cotton',
        'luxe-cotton-business-card' => 'luxe-cotton',
        'grand-cotton-business-card' => 'grand-cotton',
        'super-business-cards' => 'super',
        'luxe-business-cards' => 'luxe',
        'basic-pvc-card' => 'basic-pvc',
        'standard-pvc-card' => 'standard-pvc',
        'premium-pvc-card' => 'premium-pvc',
        'classic-metal-business-cards' => 'classic-metal',
        'premium-metal-business-cards' => 'premium-metal',
        'luxe-metal-business-cards' => 'luxe-metal',
        'classic-standard-business-cards' => 'classic-standard',
        'classic-special-business-cards' => 'classic-special',
        'classic-quality-business-cards' => 'classic-quality',
        'classic-solid-business-cards' => 'classic-solid',
    ];

    public static function productSlugForSegment(string $segment): ?string
    {
        $productSlug = array_search($segment, self::ROUTE_SEGMENTS, true);

        return $productSlug === false ? null : (string) $productSlug;
    }

    public static function pathForProductSlug(string $productSlug): ?string
    {
        $segment = self::ROUTE_SEGMENTS[$productSlug] ?? null;

        return $segment === null ? null : '/business-cards/'.$segment;
    }

    public static function hrefForProductSlug(string $productSlug): string
    {
        return self::pathForProductSlug($productSlug)
            ?? '/'.ltrim($productSlug, '/');
    }
}
