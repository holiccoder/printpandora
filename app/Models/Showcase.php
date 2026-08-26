<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $image_name
 * @property string|null $link
 * @property string $image_url
 */
class Showcase extends Model
{
    protected $fillable = [
        'image_name',
        'link',
        'image_url',
    ];
}
