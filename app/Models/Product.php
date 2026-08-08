<?php

namespace App\Models;

use App\Support\ProductImagePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property array<string, mixed>|null $product_options
 * @property array<string, mixed>|null $product_config
 * @property string|null $featured_image
 * @property string|null $slug
 * @property-read ProductCategory|null $category
 */
class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'subtitle',
        'description_title',
        'description',
        'bullet_points',
        'product_options',
        'product_config',
        'price_line',
        'meta_description',
        'slug',
        'featured_image',
        'product_category_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bullet_points' => 'array',
            'product_options' => 'array',
            'product_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
            ->withResponsiveImages();

        $this->addMediaCollection('product-gallery-overrides')
            ->acceptsMimeTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
            ->withResponsiveImages();

        $this->addMediaCollection('product-featured-overrides')
            ->acceptsMimeTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
            ->withResponsiveImages();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion(ProductImagePolicy::STOREFRONT_CONVERSION)
            ->performOnCollections(
                'gallery',
                'product-gallery-overrides',
                'product-featured-overrides',
            )
            ->nonQueued()
            ->orientation()
            ->fit(
                Fit::Max,
                ProductImagePolicy::MAX_DIMENSION,
                ProductImagePolicy::MAX_DIMENSION,
            )
            ->format('webp')
            ->quality(ProductImagePolicy::WEBP_QUALITY);
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
