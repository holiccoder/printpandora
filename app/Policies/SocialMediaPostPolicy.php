<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SocialMediaPost;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SocialMediaPostPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SocialMediaPost');
    }

    public function view(AuthUser $authUser, SocialMediaPost $socialMediaPost): bool
    {
        return $authUser->can('View:SocialMediaPost');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SocialMediaPost');
    }

    public function update(AuthUser $authUser, SocialMediaPost $socialMediaPost): bool
    {
        return $authUser->can('Update:SocialMediaPost');
    }

    public function delete(AuthUser $authUser, SocialMediaPost $socialMediaPost): bool
    {
        return $authUser->can('Delete:SocialMediaPost');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SocialMediaPost');
    }

    public function restore(AuthUser $authUser, SocialMediaPost $socialMediaPost): bool
    {
        return $authUser->can('Restore:SocialMediaPost');
    }

    public function forceDelete(AuthUser $authUser, SocialMediaPost $socialMediaPost): bool
    {
        return $authUser->can('ForceDelete:SocialMediaPost');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SocialMediaPost');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SocialMediaPost');
    }

    public function replicate(AuthUser $authUser, SocialMediaPost $socialMediaPost): bool
    {
        return $authUser->can('Replicate:SocialMediaPost');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SocialMediaPost');
    }
}
