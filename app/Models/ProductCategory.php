<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property string $slug
 * @property int|null $parent_id
 * @property-read ProductCategory|null $parent
 * @property-read Collection<int, ProductCategory> $children
 */
class ProductCategory extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id'];

    protected $casts = [
        'parent_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductCategory $category): void {
            $category->assertCanHaveParent($category->parent_id);
        });
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * @return HasMany<ProductCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /**
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        if (! $this->exists) {
            return [];
        }

        $descendantIds = [];
        $parentIds = [(int) $this->getKey()];

        while (true) {
            $children = static::query()
                ->whereIn('parent_id', $parentIds)
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $descendantIds));

            if ($children === []) {
                break;
            }

            $descendantIds = [...$descendantIds, ...$children];
            $parentIds = $children;
        }

        return $descendantIds;
    }

    /**
     * @throws ValidationException
     */
    public function assertCanHaveParent(?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($this->exists && $parentId === (int) $this->getKey()) {
            throw ValidationException::withMessages([
                'parent_id' => '分类不能将自身设为父级分类。',
            ]);
        }

        if ($this->exists && in_array($parentId, $this->descendantIds(), true)) {
            throw ValidationException::withMessages([
                'parent_id' => '分类不能移动到其子分类下。',
            ]);
        }
    }

    public function hierarchyPath(): string
    {
        $labels = [];
        $category = $this;
        $visited = [];

        while ($category !== null) {
            $categoryId = $category->getKey();

            if ($categoryId !== null && isset($visited[$categoryId])) {
                break;
            }

            if ($categoryId !== null) {
                $visited[$categoryId] = true;
            }

            $labels[] = $category->name;

            if (! $category->parent_id) {
                break;
            }

            $category = $category->relationLoaded('parent')
                ? $category->getRelation('parent')
                : static::query()->find($category->parent_id);
        }

        return implode(' / ', array_reverse($labels));
    }

    public function depth(): int
    {
        $path = $this->hierarchyPath();

        return $path === '' ? 0 : substr_count($path, ' / ');
    }
}
