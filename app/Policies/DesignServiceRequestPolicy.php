<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DesignServiceRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DesignServiceRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DesignServiceRequest');
    }

    public function view(AuthUser $authUser, DesignServiceRequest $designServiceRequest): bool
    {
        return $authUser->can('View:DesignServiceRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DesignServiceRequest');
    }

    public function update(AuthUser $authUser, DesignServiceRequest $designServiceRequest): bool
    {
        return $authUser->can('Update:DesignServiceRequest');
    }

    public function delete(AuthUser $authUser, DesignServiceRequest $designServiceRequest): bool
    {
        return $authUser->can('Delete:DesignServiceRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DesignServiceRequest');
    }

    public function restore(AuthUser $authUser, DesignServiceRequest $designServiceRequest): bool
    {
        return $authUser->can('Restore:DesignServiceRequest');
    }

    public function forceDelete(AuthUser $authUser, DesignServiceRequest $designServiceRequest): bool
    {
        return $authUser->can('ForceDelete:DesignServiceRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DesignServiceRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DesignServiceRequest');
    }

    public function replicate(AuthUser $authUser, DesignServiceRequest $designServiceRequest): bool
    {
        return $authUser->can('Replicate:DesignServiceRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DesignServiceRequest');
    }
}
