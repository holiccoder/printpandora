<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiChatConversation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AiChatConversationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiChatConversation');
    }

    public function view(AuthUser $authUser, AiChatConversation $aiChatConversation): bool
    {
        return $authUser->can('View:AiChatConversation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiChatConversation');
    }

    public function update(AuthUser $authUser, AiChatConversation $aiChatConversation): bool
    {
        return $authUser->can('Update:AiChatConversation');
    }

    public function delete(AuthUser $authUser, AiChatConversation $aiChatConversation): bool
    {
        return $authUser->can('Delete:AiChatConversation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AiChatConversation');
    }

    public function restore(AuthUser $authUser, AiChatConversation $aiChatConversation): bool
    {
        return $authUser->can('Restore:AiChatConversation');
    }

    public function forceDelete(AuthUser $authUser, AiChatConversation $aiChatConversation): bool
    {
        return $authUser->can('ForceDelete:AiChatConversation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiChatConversation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiChatConversation');
    }

    public function replicate(AuthUser $authUser, AiChatConversation $aiChatConversation): bool
    {
        return $authUser->can('Replicate:AiChatConversation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AiChatConversation');
    }
}
