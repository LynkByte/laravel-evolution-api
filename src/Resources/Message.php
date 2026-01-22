<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\DTOs\Message\SendAudioMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendContactMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendListMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendLocationMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendPollMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendReactionMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendStatusMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendStickerMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTemplateMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;

/**
 * Message sending resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/message
 */
class Message extends Resource
{
    /**
     * Send a text message.
     */
    public function sendText(SendTextMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendTextMessageDto::fromArray($message);
        }

        return $this->post('message/sendText/{instance}', $message->toArray());
    }

    /**
     * Send a text message (simple helper).
     */
    public function text(string $number, string $text, ?int $delay = null): ApiResponse
    {
        return $this->sendText(new SendTextMessageDto(
            number: $number,
            text: $text,
            delay: $delay
        ));
    }

    /**
     * Send a media message (image, video, document).
     */
    public function sendMedia(SendMediaMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendMediaMessageDto::fromArray($message);
        }

        return $this->post('message/sendMedia/{instance}', $message->toArray());
    }

    /**
     * Send an image (simple helper).
     */
    public function image(
        string $number,
        string $media,
        ?string $caption = null,
        ?string $fileName = null
    ): ApiResponse {
        return $this->sendMedia(new SendMediaMessageDto(
            number: $number,
            mediatype: 'image',
            media: $media,
            caption: $caption,
            fileName: $fileName
        ));
    }

    /**
     * Send a video (simple helper).
     */
    public function video(
        string $number,
        string $media,
        ?string $caption = null,
        ?string $fileName = null
    ): ApiResponse {
        return $this->sendMedia(new SendMediaMessageDto(
            number: $number,
            mediatype: 'video',
            media: $media,
            caption: $caption,
            fileName: $fileName
        ));
    }

    /**
     * Send a document (simple helper).
     */
    public function document(
        string $number,
        string $media,
        ?string $caption = null,
        ?string $fileName = null,
        ?string $mimetype = null
    ): ApiResponse {
        return $this->sendMedia(new SendMediaMessageDto(
            number: $number,
            mediatype: 'document',
            media: $media,
            caption: $caption,
            fileName: $fileName,
            mimetype: $mimetype
        ));
    }

    /**
     * Send an audio message.
     */
    public function sendAudio(SendAudioMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendAudioMessageDto::fromArray($message);
        }

        return $this->post('message/sendWhatsAppAudio/{instance}', $message->toArray());
    }

    /**
     * Send audio (simple helper).
     */
    public function audio(string $number, string $audio, ?int $delay = null): ApiResponse
    {
        return $this->sendAudio(new SendAudioMessageDto(
            number: $number,
            audio: $audio,
            delay: $delay
        ));
    }

    /**
     * Send a location message.
     */
    public function sendLocation(SendLocationMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendLocationMessageDto::fromArray($message);
        }

        return $this->post('message/sendLocation/{instance}', $message->toArray());
    }

    /**
     * Send location (simple helper).
     */
    public function location(
        string $number,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null
    ): ApiResponse {
        return $this->sendLocation(new SendLocationMessageDto(
            number: $number,
            latitude: $latitude,
            longitude: $longitude,
            name: $name,
            address: $address
        ));
    }

    /**
     * Send a contact/vCard message.
     */
    public function sendContact(SendContactMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendContactMessageDto::fromArray($message);
        }

        return $this->post('message/sendContact/{instance}', $message->toArray());
    }

    /**
     * Send a poll message.
     */
    public function sendPoll(SendPollMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendPollMessageDto::fromArray($message);
        }

        return $this->post('message/sendPoll/{instance}', $message->toArray());
    }

    /**
     * Send poll (simple helper).
     *
     * @param  array<string>  $values  Poll options
     */
    public function poll(
        string $number,
        string $name,
        array $values,
        int $selectableCount = 1
    ): ApiResponse {
        return $this->sendPoll(new SendPollMessageDto(
            number: $number,
            name: $name,
            values: $values,
            selectableCount: $selectableCount
        ));
    }

    /**
     * Send a list message.
     */
    public function sendList(SendListMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendListMessageDto::fromArray($message);
        }

        return $this->post('message/sendList/{instance}', $message->toArray());
    }

    /**
     * Send a reaction to a message.
     */
    public function sendReaction(SendReactionMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendReactionMessageDto::fromArray($message);
        }

        return $this->post('message/sendReaction/{instance}', $message->toArray());
    }

    /**
     * React to a message (simple helper).
     */
    public function react(string $remoteJid, string $messageId, string $reaction, bool $fromMe = false): ApiResponse
    {
        return $this->sendReaction(SendReactionMessageDto::react(
            remoteJid: $remoteJid,
            messageId: $messageId,
            fromMe: $fromMe,
            reaction: $reaction
        ));
    }

    /**
     * Remove a reaction (send empty reaction).
     */
    public function unreact(string $remoteJid, string $messageId, bool $fromMe = false): ApiResponse
    {
        return $this->sendReaction(SendReactionMessageDto::remove(
            remoteJid: $remoteJid,
            messageId: $messageId,
            fromMe: $fromMe
        ));
    }

    /**
     * Send a sticker message.
     */
    public function sendSticker(SendStickerMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendStickerMessageDto::fromArray($message);
        }

        return $this->post('message/sendSticker/{instance}', $message->toArray());
    }

    /**
     * Send sticker (simple helper).
     */
    public function sticker(string $number, string $sticker): ApiResponse
    {
        return $this->sendSticker(new SendStickerMessageDto(
            number: $number,
            sticker: $sticker
        ));
    }

    /**
     * Send a status/story message.
     */
    public function sendStatus(SendStatusMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendStatusMessageDto::fromArray($message);
        }

        return $this->post('message/sendStatus/{instance}', $message->toArray());
    }

    /**
     * Send a template message.
     */
    public function sendTemplate(SendTemplateMessageDto|array $message): ApiResponse
    {
        $this->ensureInstance();

        if (is_array($message)) {
            $message = SendTemplateMessageDto::fromArray($message);
        }

        return $this->post('message/sendTemplate/{instance}', $message->toArray());
    }

    /**
     * Send buttons message.
     *
     * @param  array<array{buttonId: string, buttonText: string}>  $buttons
     */
    public function sendButtons(
        string $number,
        string $title,
        string $description,
        array $buttons,
        ?string $footer = null
    ): ApiResponse {
        $this->ensureInstance();

        $data = $this->filterNull([
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'buttons' => $buttons,
            'footer' => $footer,
        ]);

        return $this->post('message/sendButtons/{instance}', $data);
    }

    /**
     * Read a message (mark as read).
     */
    public function markAsRead(string $remoteJid, string $messageId): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('message/readMessage/{instance}', [
            'readMessages' => [
                [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                    'id' => $messageId,
                ],
            ],
        ]);
    }

    /**
     * Mark multiple messages as read.
     *
     * @param  array<array{remoteJid: string, id: string}>  $messages
     */
    public function markMultipleAsRead(array $messages): ApiResponse
    {
        $this->ensureInstance();

        $formattedMessages = array_map(fn ($msg) => [
            'remoteJid' => $this->formatRemoteJid($msg['remoteJid']),
            'id' => $msg['id'],
        ], $messages);

        return $this->post('message/readMessage/{instance}', [
            'readMessages' => $formattedMessages,
        ]);
    }

    /**
     * Archive a chat.
     */
    public function archiveChat(string $remoteJid, bool $archive = true): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('message/archiveChat/{instance}', [
            'lastMessage' => [
                'key' => [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                ],
            ],
            'archive' => $archive,
        ]);
    }

    /**
     * Unarchive a chat.
     */
    public function unarchiveChat(string $remoteJid): ApiResponse
    {
        return $this->archiveChat($remoteJid, false);
    }

    /**
     * Delete a message for everyone.
     */
    public function deleteMessage(
        string $remoteJid,
        string $messageId,
        bool $onlyMe = false
    ): ApiResponse {
        $this->ensureInstance();

        return $this->delete('message/delete/{instance}', [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'messageId' => $messageId,
            'onlyMe' => $onlyMe,
        ]);
    }

    /**
     * Delete message only for myself.
     */
    public function deleteForMe(string $remoteJid, string $messageId): ApiResponse
    {
        return $this->deleteMessage($remoteJid, $messageId, true);
    }

    /**
     * Update/edit a sent message.
     */
    public function updateMessage(
        string $remoteJid,
        string $messageId,
        string $text
    ): ApiResponse {
        $this->ensureInstance();

        return $this->put('message/update/{instance}', [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'messageId' => $messageId,
            'text' => $text,
        ]);
    }

    /**
     * Star/unstar a message.
     */
    public function starMessage(
        string $remoteJid,
        string $messageId,
        bool $star = true
    ): ApiResponse {
        $this->ensureInstance();

        return $this->post('message/star/{instance}', [
            'remoteJid' => $this->formatRemoteJid($remoteJid),
            'messageId' => $messageId,
            'star' => $star,
        ]);
    }

    /**
     * Unstar a message.
     */
    public function unstarMessage(string $remoteJid, string $messageId): ApiResponse
    {
        return $this->starMessage($remoteJid, $messageId, false);
    }

    /**
     * Get message by ID.
     */
    public function getMessageById(string $remoteJid, string $messageId): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('message/getMessageById/{instance}', [
            'key' => [
                'remoteJid' => $this->formatRemoteJid($remoteJid),
                'id' => $messageId,
            ],
        ]);
    }

    /**
     * Send with typing indicator.
     *
     * Simulates typing before sending the message.
     *
     * @param  int  $delay  Delay in milliseconds to show typing
     */
    public function sendWithTyping(
        string $number,
        string $text,
        int $delay = 1000
    ): ApiResponse {
        $this->ensureInstance();

        return $this->post('message/sendText/{instance}', [
            'number' => $number,
            'text' => $text,
            'options' => [
                'delay' => $delay,
            ],
        ]);
    }

    /**
     * Reply to a message.
     */
    public function reply(
        string $number,
        string $text,
        string $quotedMessageId
    ): ApiResponse {
        $this->ensureInstance();

        return $this->post('message/sendText/{instance}', [
            'number' => $number,
            'text' => $text,
            'options' => [
                'quoted' => [
                    'key' => [
                        'id' => $quotedMessageId,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Forward a message.
     */
    public function forward(
        string $number,
        string $messageId,
        string $remoteJid
    ): ApiResponse {
        $this->ensureInstance();

        return $this->post('message/forwardMessage/{instance}', [
            'number' => $number,
            'message' => [
                'key' => [
                    'remoteJid' => $this->formatRemoteJid($remoteJid),
                    'id' => $messageId,
                ],
            ],
        ]);
    }

    /**
     * Send presence update (typing, recording, etc.).
     */
    public function sendPresence(string $number, string $presence = 'composing'): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('message/sendPresence/{instance}', [
            'number' => $number,
            'presence' => $presence,
        ]);
    }

    /**
     * Send typing indicator.
     */
    public function typing(string $number): ApiResponse
    {
        return $this->sendPresence($number, 'composing');
    }

    /**
     * Send recording indicator.
     */
    public function recording(string $number): ApiResponse
    {
        return $this->sendPresence($number, 'recording');
    }

    /**
     * Stop presence indicator.
     */
    public function stopPresence(string $number): ApiResponse
    {
        return $this->sendPresence($number, 'paused');
    }
}
