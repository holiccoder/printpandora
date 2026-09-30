<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DatabaseNotification');
    }

    public function view(AuthUser $authUser, DatabaseNotification $databaseNotification): bool
    {
        return $authUser->can('View:DatabaseNotification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DatabaseNotification');
    }

    public function update(AuthUser $authUser, DatabaseNotification $databaseNotification): bool
    {
        return $authUser->can('Update:DatabaseNotification');
    }

    public function delete(AuthUser $authUser, DatabaseNotification $databaseNotification): bool
    {
        return $authUser->can('Delete:DatabaseNotification');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DatabaseNotification');
    }

    public function restore(AuthUser $authUser, DatabaseNotification $databaseNotification): bool
    {
        return $authUser->can('Restore:DatabaseNotification');
    }

    public function forceDelete(AuthUser $authUser, DatabaseNotification $databaseNotification): bool
    {
        return $authUser->can('ForceDelete:DatabaseNotification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DatabaseNotification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DatabaseNotification');
    }

    public function replicate(AuthUser $authUser, DatabaseNotification $databaseNotification): bool
    {
        return $authUser->can('Replicate:DatabaseNotification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DatabaseNotification');
    }
}
