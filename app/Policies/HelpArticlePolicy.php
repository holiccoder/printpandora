<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HelpArticle;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HelpArticlePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HelpArticle');
    }

    public function view(AuthUser $authUser, HelpArticle $helpArticle): bool
    {
        return $authUser->can('View:HelpArticle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HelpArticle');
    }

    public function update(AuthUser $authUser, HelpArticle $helpArticle): bool
    {
        return $authUser->can('Update:HelpArticle');
    }

    public function delete(AuthUser $authUser, HelpArticle $helpArticle): bool
    {
        return $authUser->can('Delete:HelpArticle');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HelpArticle');
    }

    public function restore(AuthUser $authUser, HelpArticle $helpArticle): bool
    {
        return $authUser->can('Restore:HelpArticle');
    }

    public function forceDelete(AuthUser $authUser, HelpArticle $helpArticle): bool
    {
        return $authUser->can('ForceDelete:HelpArticle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HelpArticle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HelpArticle');
    }

    public function replicate(AuthUser $authUser, HelpArticle $helpArticle): bool
    {
        return $authUser->can('Replicate:HelpArticle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HelpArticle');
    }
}
