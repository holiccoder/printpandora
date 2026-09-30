<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DesignerPartnerApplication;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DesignerPartnerApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DesignerPartnerApplication');
    }

    public function view(AuthUser $authUser, DesignerPartnerApplication $designerPartnerApplication): bool
    {
        return $authUser->can('View:DesignerPartnerApplication');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DesignerPartnerApplication');
    }

    public function update(AuthUser $authUser, DesignerPartnerApplication $designerPartnerApplication): bool
    {
        return $authUser->can('Update:DesignerPartnerApplication');
    }

    public function delete(AuthUser $authUser, DesignerPartnerApplication $designerPartnerApplication): bool
    {
        return $authUser->can('Delete:DesignerPartnerApplication');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DesignerPartnerApplication');
    }

    public function restore(AuthUser $authUser, DesignerPartnerApplication $designerPartnerApplication): bool
    {
        return $authUser->can('Restore:DesignerPartnerApplication');
    }

    public function forceDelete(AuthUser $authUser, DesignerPartnerApplication $designerPartnerApplication): bool
    {
        return $authUser->can('ForceDelete:DesignerPartnerApplication');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DesignerPartnerApplication');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DesignerPartnerApplication');
    }

    public function replicate(AuthUser $authUser, DesignerPartnerApplication $designerPartnerApplication): bool
    {
        return $authUser->can('Replicate:DesignerPartnerApplication');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DesignerPartnerApplication');
    }
}
