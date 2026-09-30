<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShowcaseCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ShowcaseCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShowcaseCategory');
    }

    public function view(AuthUser $authUser, ShowcaseCategory $showcaseCategory): bool
    {
        return $authUser->can('View:ShowcaseCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShowcaseCategory');
    }

    public function update(AuthUser $authUser, ShowcaseCategory $showcaseCategory): bool
    {
        return $authUser->can('Update:ShowcaseCategory');
    }

    public function delete(AuthUser $authUser, ShowcaseCategory $showcaseCategory): bool
    {
        return $authUser->can('Delete:ShowcaseCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ShowcaseCategory');
    }

    public function restore(AuthUser $authUser, ShowcaseCategory $showcaseCategory): bool
    {
        return $authUser->can('Restore:ShowcaseCategory');
    }

    public function forceDelete(AuthUser $authUser, ShowcaseCategory $showcaseCategory): bool
    {
        return $authUser->can('ForceDelete:ShowcaseCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShowcaseCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShowcaseCategory');
    }

    public function replicate(AuthUser $authUser, ShowcaseCategory $showcaseCategory): bool
    {
        return $authUser->can('Replicate:ShowcaseCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShowcaseCategory');
    }
}
