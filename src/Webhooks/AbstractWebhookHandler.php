<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Webhooks;

use Lynkbyte\EvolutionApi\Contracts\WebhookHandlerInterface;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

/**
 * Abstract base class for webhook handlers.
 *
 * Developers should extend this class to create custom webhook handlers
 * for specific events or instances.
 *
 * Example:
 * ```php
 * class MyWebhookHandler extends AbstractWebhookHandler
 * {
 *     protected function onMessageReceived(WebhookPayloadDto $payload): void
 *     {
 *         // Handle incoming messages
 *     }
 * }
 * ```
 */
abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    /**
     * Allowed instances for this handler.
     * Empty array means all instances are allowed.
     *
     * @var array<string>
     */
    protected array $allowedInstances = [];

    /**
     * Allowed events for this handler.
     * Empty array means all events are allowed.
     *
     * @var array<WebhookEvent>
     */
    protected array $allowedEvents = [];

    /**
     * {@inheritdoc}
     */
    public function shouldHandle(WebhookPayloadDto $payload): bool
    {
        // Check instance filter
        if (! empty($this->allowedInstances) && ! in_array($payload->instanceName, $this->allowedInstances, true)) {
            return false;
        }

        // Check event filter
        if (! empty($this->allowedEvents) && $payload->webhookEvent !== null) {
            if (! in_array($payload->webhookEvent, $this->allowedEvents, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(WebhookPayloadDto $payload): void
    {
        if (! $this->shouldHandle($payload)) {
            return;
        }

        // Call event-specific handler
        $this->handleEvent($payload);

        // Always call the generic handler
        $this->onWebhookReceived($payload);
    }

    /**
     * Handle the webhook based on event type.
     */
    protected function handleEvent(WebhookPayloadDto $payload): void
    {
        match ($payload->webhookEvent) {
            WebhookEvent::MESSAGES_UPSERT => $this->onMessageReceived($payload),
            WebhookEvent::MESSAGES_UPDATE => $this->onMessageUpdated($payload),
            WebhookEvent::SEND_MESSAGE => $this->onMessageSent($payload),
            WebhookEvent::MESSAGES_DELETE => $this->onMessageDeleted($payload),
            WebhookEvent::CONNECTION_UPDATE => $this->onConnectionUpdated($payload),
            WebhookEvent::QRCODE_UPDATED => $this->onQrCodeReceived($payload),
            WebhookEvent::PRESENCE_UPDATE => $this->onPresenceUpdated($payload),
            WebhookEvent::GROUPS_UPSERT => $this->onGroupCreated($payload),
            WebhookEvent::GROUP_UPDATE => $this->onGroupUpdated($payload),
            WebhookEvent::GROUP_PARTICIPANTS_UPDATE => $this->onGroupParticipantsUpdated($payload),
            WebhookEvent::CONTACTS_UPSERT => $this->onContactCreated($payload),
            WebhookEvent::CONTACTS_UPDATE => $this->onContactUpdated($payload),
            WebhookEvent::CHATS_UPSERT => $this->onChatCreated($payload),
            WebhookEvent::CHATS_UPDATE => $this->onChatUpdated($payload),
            WebhookEvent::CHATS_DELETE => $this->onChatDeleted($payload),
            WebhookEvent::CALL => $this->onCallReceived($payload),
            WebhookEvent::LABELS_EDIT => $this->onLabelsEdited($payload),
            WebhookEvent::LABELS_ASSOCIATION => $this->onLabelsAssociated($payload),
            default => $this->onUnknownEvent($payload),
        };
    }

    /**
     * Called for every webhook received (after event-specific handler).
     * Override this to log or process all webhooks.
     */
    protected function onWebhookReceived(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a message is received.
     */
    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a message is updated (delivered/read).
     */
    protected function onMessageUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a message is sent.
     */
    protected function onMessageSent(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a message is deleted.
     */
    protected function onMessageDeleted(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when connection status changes.
     */
    protected function onConnectionUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a QR code is generated.
     */
    protected function onQrCodeReceived(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when presence (online/typing) status updates.
     */
    protected function onPresenceUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a group is created.
     */
    protected function onGroupCreated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a group is updated.
     */
    protected function onGroupUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when group participants are updated.
     */
    protected function onGroupParticipantsUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a contact is created.
     */
    protected function onContactCreated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a contact is updated.
     */
    protected function onContactUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a chat is created.
     */
    protected function onChatCreated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a chat is updated.
     */
    protected function onChatUpdated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a chat is deleted.
     */
    protected function onChatDeleted(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when a call is received.
     */
    protected function onCallReceived(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when labels are edited.
     */
    protected function onLabelsEdited(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called when labels are associated.
     */
    protected function onLabelsAssociated(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Called for unknown events.
     */
    protected function onUnknownEvent(WebhookPayloadDto $payload): void
    {
        // Override in subclass
    }

    /**
     * Set the allowed instances for this handler.
     *
     * @param  array<string>  $instances
     */
    public function forInstances(array $instances): static
    {
        $this->allowedInstances = $instances;

        return $this;
    }

    /**
     * Set the allowed events for this handler.
     *
     * @param  array<WebhookEvent>  $events
     */
    public function forEvents(array $events): static
    {
        $this->allowedEvents = $events;

        return $this;
    }

    /**
     * Allow only message events.
     */
    public function onlyMessageEvents(): static
    {
        $this->allowedEvents = [
            WebhookEvent::MESSAGES_SET,
            WebhookEvent::MESSAGES_UPSERT,
            WebhookEvent::MESSAGES_UPDATE,
            WebhookEvent::MESSAGES_DELETE,
            WebhookEvent::SEND_MESSAGE,
        ];

        return $this;
    }

    /**
     * Allow only connection events.
     */
    public function onlyConnectionEvents(): static
    {
        $this->allowedEvents = [
            WebhookEvent::CONNECTION_UPDATE,
            WebhookEvent::QRCODE_UPDATED,
        ];

        return $this;
    }

    /**
     * Allow only group events.
     */
    public function onlyGroupEvents(): static
    {
        $this->allowedEvents = [
            WebhookEvent::GROUPS_UPSERT,
            WebhookEvent::GROUP_UPDATE,
            WebhookEvent::GROUP_PARTICIPANTS_UPDATE,
        ];

        return $this;
    }
}
