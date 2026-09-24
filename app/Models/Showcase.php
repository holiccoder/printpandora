<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $image_name
 * @property string|null $link
 * @property string $image_url
 * @property int|null $category_id
 */
class Showcase extends Model
{
    protected $fillable = [
        'image_name',
        'link',
        'image_url',
        'category_id',
    ];

    /** @return BelongsTo<ShowcaseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShowcaseCategory::class);
    }
}
