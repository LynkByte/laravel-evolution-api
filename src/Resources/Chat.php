<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Chat management resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/chat
 */
class Chat extends Resource
{
    /**
     * Check if a phone number is registered on WhatsApp.
     *
     * @param array<string>|string $numbers One or more phone numbers
     */
    public function checkNumber(array|string $numbers): ApiResponse
    {
        $this->ensureInstance();

        $numbers = is_array($numbers) ? $numbers : [$numbers];

        return $this->post("chat/whatsappNumbers/{instance}", [
            'numbers' => $numbers,
        ]);
    }

    /**
     * Alias for checkNumber.
     */
    public function isOnWhatsApp(string $number): ApiResponse
    {
        return $this->checkNumber($number);
    }

    /**
     * Get all chats.
     */
    public function findAll(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("chat/findChats/{instance}");
    }

    /**
     * Find chats with pagination.
     */
    public function findPaginated(int $page = 1, int $limit = 20): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("chat/findChats/{instance}", [
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Find a specific chat by JID.
     */
    public function find(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/findChat/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
        ]);
    }

    /**
     * Get all contacts.
     */
    public function findContacts(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("chat/findContacts/{instance}");
    }

    /**
     * Find contacts by query.
     */
    public function searchContacts(string $query): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/findContacts/{instance}", [
            'where' => [
                'pushName' => [
                    'contains' => $query,
                ],
            ],
        ]);
    }

    /**
     * Get chat messages.
     */
    public function findMessages(
        string $remoteJid,
        int $limit = 20,
        ?string $cursor = null
    ): ApiResponse {
        $this->ensureInstance();

        $data = [
            'where' => [
                'key' => [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                ],
            ],
            'limit' => $limit,
        ];

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        return $this->post("chat/findMessages/{instance}", $data);
    }

    /**
     * Get all messages (optionally filtered).
     *
     * @param array<string, mixed>|null $where Filter conditions
     */
    public function findAllMessages(?array $where = null, int $limit = 100): ApiResponse
    {
        $this->ensureInstance();

        $data = ['limit' => $limit];

        if ($where !== null) {
            $data['where'] = $where;
        }

        return $this->post("chat/findMessages/{instance}", $data);
    }

    /**
     * Get messages from a specific date range.
     */
    public function findMessagesByDate(
        string $remoteJid,
        int $startTimestamp,
        int $endTimestamp,
        int $limit = 100
    ): ApiResponse {
        $this->ensureInstance();

        return $this->post("chat/findMessages/{instance}", [
            'where' => [
                'key' => [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                ],
                'messageTimestamp' => [
                    'gte' => $startTimestamp,
                    'lte' => $endTimestamp,
                ],
            ],
            'limit' => $limit,
        ]);
    }

    /**
     * Get status messages (stories).
     */
    public function findStatusMessages(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("chat/findStatusMessages/{instance}");
    }

    /**
     * Get labels.
     */
    public function findLabels(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("chat/findLabels/{instance}");
    }

    /**
     * Mark chat as unread.
     */
    public function markChatUnread(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/markChatUnread/{instance}", [
            'chat' => $this->formatRemoteJid($remoteJid),
        ]);
    }

    /**
     * Archive a chat.
     */
    public function archive(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/archiveChat/{instance}", [
            'lastMessage' => [
                'key' => [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                ],
            ],
            'archive' => true,
        ]);
    }

    /**
     * Unarchive a chat.
     */
    public function unarchive(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/archiveChat/{instance}", [
            'lastMessage' => [
                'key' => [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                ],
            ],
            'archive' => false,
        ]);
    }

    /**
     * Delete a chat.
     */
    public function deleteChat(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->delete("chat/deleteChat/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
        ]);
    }

    /**
     * Clear all messages in a chat.
     */
    public function clearMessages(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->delete("chat/clearMessages/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
        ]);
    }

    /**
     * Fetch profile picture URL.
     */
    public function fetchProfilePicture(string $number): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/fetchProfilePictureUrl/{instance}", [
            'number' => $number,
        ]);
    }

    /**
     * Get business profile.
     */
    public function getBusinessProfile(string $number): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/fetchBusinessProfile/{instance}", [
            'number' => $number,
        ]);
    }

    /**
     * Mute a chat.
     *
     * @param int $expiration Mute expiration timestamp (0 for indefinite)
     */
    public function mute(string $remoteJid, int $expiration = 0): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/muteChat/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'expiration' => $expiration,
        ]);
    }

    /**
     * Unmute a chat.
     */
    public function unmute(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/unmuteChat/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
        ]);
    }

    /**
     * Pin a chat.
     */
    public function pin(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/pinChat/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'pin' => true,
        ]);
    }

    /**
     * Unpin a chat.
     */
    public function unpin(string $remoteJid): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/pinChat/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'pin' => false,
        ]);
    }

    /**
     * Block a contact.
     */
    public function block(string $number): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/blockContact/{instance}", [
            'number' => $number,
            'status' => 'block',
        ]);
    }

    /**
     * Unblock a contact.
     */
    public function unblock(string $number): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/blockContact/{instance}", [
            'number' => $number,
            'status' => 'unblock',
        ]);
    }

    /**
     * Get presence (online status) of a contact.
     */
    public function getPresence(string $number): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/fetchPresence/{instance}", [
            'number' => $number,
        ]);
    }

    /**
     * Update contact name.
     */
    public function updateContactName(string $remoteJid, string $name): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("chat/updateContact/{instance}", [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'name' => $name,
        ]);
    }
}
