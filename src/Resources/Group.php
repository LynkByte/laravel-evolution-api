<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Group management resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/group
 */
class Group extends Resource
{
    /**
     * Create a new group.
     *
     * @param array<string> $participants Array of phone numbers to add
     */
    public function create(string $subject, array $participants, ?string $description = null): ApiResponse
    {
        $this->ensureInstance();

        $data = [
            'subject' => $subject,
            'participants' => $participants,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        return $this->post("group/create/{instance}", $data);
    }

    /**
     * Update group subject (name).
     */
    public function updateSubject(string $groupJid, string $subject): ApiResponse
    {
        $this->ensureInstance();

        return $this->put("group/updateSubject/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'subject' => $subject,
        ]);
    }

    /**
     * Update group description.
     */
    public function updateDescription(string $groupJid, string $description): ApiResponse
    {
        $this->ensureInstance();

        return $this->put("group/updateDescription/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'description' => $description,
        ]);
    }

    /**
     * Update group picture.
     *
     * @param string $image Base64 encoded image or URL
     */
    public function updatePicture(string $groupJid, string $image): ApiResponse
    {
        $this->ensureInstance();

        return $this->put("group/updatePicture/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'image' => $image,
        ]);
    }

    /**
     * Remove group picture.
     */
    public function removePicture(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->delete("group/removePicture/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Fetch group by JID.
     */
    public function fetchOne(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/findGroupInfos/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Fetch all groups.
     */
    public function fetchAll(bool $getParticipants = false): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/fetchAllGroups/{instance}", [
            'getParticipants' => $getParticipants ? 'true' : 'false',
        ]);
    }

    /**
     * Get group participants.
     */
    public function participants(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/participants/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Get group invite code.
     */
    public function inviteCode(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/inviteCode/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Revoke group invite code.
     */
    public function revokeInviteCode(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->put("group/revokeInviteCode/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Accept group invite by code.
     */
    public function acceptInvite(string $inviteCode): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/acceptInviteCode/{instance}", [
            'inviteCode' => $inviteCode,
        ]);
    }

    /**
     * Get group info by invite code.
     */
    public function inviteInfo(string $inviteCode): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/inviteInfo/{instance}", [
            'inviteCode' => $inviteCode,
        ]);
    }

    /**
     * Send group invite link.
     *
     * @param array<string> $numbers Phone numbers to invite
     */
    public function sendInvite(string $groupJid, array $numbers, ?string $description = null): ApiResponse
    {
        $this->ensureInstance();

        $data = [
            'groupJid' => $this->formatGroupJid($groupJid),
            'numbers' => $numbers,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        return $this->post("group/sendInvite/{instance}", $data);
    }

    /**
     * Add participants to group.
     *
     * @param array<string> $participants Phone numbers to add
     */
    public function addParticipants(string $groupJid, array $participants): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/updateParticipant/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'action' => 'add',
            'participants' => $participants,
        ]);
    }

    /**
     * Remove participants from group.
     *
     * @param array<string> $participants Phone numbers to remove
     */
    public function removeParticipants(string $groupJid, array $participants): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/updateParticipant/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'action' => 'remove',
            'participants' => $participants,
        ]);
    }

    /**
     * Promote participants to admin.
     *
     * @param array<string> $participants Phone numbers to promote
     */
    public function promoteToAdmin(string $groupJid, array $participants): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/updateParticipant/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'action' => 'promote',
            'participants' => $participants,
        ]);
    }

    /**
     * Demote participants from admin.
     *
     * @param array<string> $participants Phone numbers to demote
     */
    public function demoteFromAdmin(string $groupJid, array $participants): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/updateParticipant/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'action' => 'demote',
            'participants' => $participants,
        ]);
    }

    /**
     * Update group settings.
     *
     * @param string $action announce (only admins can send), not_announce (everyone can send),
     *                       locked (only admins can edit), unlocked (everyone can edit)
     */
    public function updateSettings(string $groupJid, string $action): ApiResponse
    {
        $this->ensureInstance();

        return $this->put("group/updateSetting/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'action' => $action,
        ]);
    }

    /**
     * Set group to announcement mode (only admins can send messages).
     */
    public function setAnnouncementMode(string $groupJid, bool $enabled = true): ApiResponse
    {
        return $this->updateSettings($groupJid, $enabled ? 'announce' : 'not_announce');
    }

    /**
     * Set group to locked mode (only admins can edit group info).
     */
    public function setLockedMode(string $groupJid, bool $enabled = true): ApiResponse
    {
        return $this->updateSettings($groupJid, $enabled ? 'locked' : 'unlocked');
    }

    /**
     * Toggle ephemeral messages (disappearing messages).
     *
     * @param int $expiration Message expiration in seconds (0 to disable)
     *                        Common values: 86400 (24h), 604800 (7d), 7776000 (90d)
     */
    public function toggleEphemeral(string $groupJid, int $expiration): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/toggleEphemeral/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'expiration' => $expiration,
        ]);
    }

    /**
     * Leave a group.
     */
    public function leave(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->delete("group/leaveGroup/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Check if current user is admin in group.
     */
    public function isAdmin(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/isAdmin/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Get pending participants (join requests).
     */
    public function pendingParticipants(string $groupJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("group/pendingParticipants/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
        ]);
    }

    /**
     * Accept join requests.
     *
     * @param array<string> $participants Participant JIDs to accept
     */
    public function acceptJoinRequests(string $groupJid, array $participants): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/acceptPendingParticipant/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'participants' => $participants,
        ]);
    }

    /**
     * Reject join requests.
     *
     * @param array<string> $participants Participant JIDs to reject
     */
    public function rejectJoinRequests(string $groupJid, array $participants): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("group/rejectPendingParticipant/{instance}", [
            'groupJid' => $this->formatGroupJid($groupJid),
            'participants' => $participants,
        ]);
    }
}
