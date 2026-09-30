<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductDesignRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductDesignRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductDesignRequest');
    }

    public function view(AuthUser $authUser, ProductDesignRequest $productDesignRequest): bool
    {
        return $authUser->can('View:ProductDesignRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductDesignRequest');
    }

    public function update(AuthUser $authUser, ProductDesignRequest $productDesignRequest): bool
    {
        return $authUser->can('Update:ProductDesignRequest');
    }

    public function delete(AuthUser $authUser, ProductDesignRequest $productDesignRequest): bool
    {
        return $authUser->can('Delete:ProductDesignRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductDesignRequest');
    }

    public function restore(AuthUser $authUser, ProductDesignRequest $productDesignRequest): bool
    {
        return $authUser->can('Restore:ProductDesignRequest');
    }

    public function forceDelete(AuthUser $authUser, ProductDesignRequest $productDesignRequest): bool
    {
        return $authUser->can('ForceDelete:ProductDesignRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductDesignRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductDesignRequest');
    }

    public function replicate(AuthUser $authUser, ProductDesignRequest $productDesignRequest): bool
    {
        return $authUser->can('Replicate:ProductDesignRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductDesignRequest');
    }
}
