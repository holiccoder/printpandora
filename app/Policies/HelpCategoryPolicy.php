<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HelpCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HelpCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HelpCategory');
    }

    public function view(AuthUser $authUser, HelpCategory $helpCategory): bool
    {
        return $authUser->can('View:HelpCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HelpCategory');
    }

    public function update(AuthUser $authUser, HelpCategory $helpCategory): bool
    {
        return $authUser->can('Update:HelpCategory');
    }

    public function delete(AuthUser $authUser, HelpCategory $helpCategory): bool
    {
        return $authUser->can('Delete:HelpCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HelpCategory');
    }

    public function restore(AuthUser $authUser, HelpCategory $helpCategory): bool
    {
        return $authUser->can('Restore:HelpCategory');
    }

    public function forceDelete(AuthUser $authUser, HelpCategory $helpCategory): bool
    {
        return $authUser->can('ForceDelete:HelpCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HelpCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HelpCategory');
    }

    public function replicate(AuthUser $authUser, HelpCategory $helpCategory): bool
    {
        return $authUser->can('Replicate:HelpCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HelpCategory');
    }
}
