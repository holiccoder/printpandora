<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An OAuth identity connected to an application user.
 *
 * Provider access tokens are intentionally not persisted because the site
 * only needs the identity for sign-in, not ongoing access to provider APIs.
 */
#[Fillable(['provider', 'provider_id', 'provider_email'])]
class SocialAccount extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
