<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Webhooks;

use Illuminate\Contracts\Events\Dispatcher;
use Lynkbyte\EvolutionApi\Contracts\WebhookHandlerInterface;
use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Enums\InstanceStatus;
use Lynkbyte\EvolutionApi\Enums\MessageType;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;
use Lynkbyte\EvolutionApi\Events\ConnectionUpdated;
use Lynkbyte\EvolutionApi\Events\InstanceStatusChanged;
use Lynkbyte\EvolutionApi\Events\MessageDelivered;
use Lynkbyte\EvolutionApi\Events\MessageRead;
use Lynkbyte\EvolutionApi\Events\MessageReceived;
use Lynkbyte\EvolutionApi\Events\MessageSent;
use Lynkbyte\EvolutionApi\Events\QrCodeReceived;
use Lynkbyte\EvolutionApi\Events\WebhookReceived;
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Processes incoming webhooks from Evolution API.
 */
class WebhookProcessor
{
    /**
     * Custom webhook handlers.
     *
     * @var array<string, WebhookHandlerInterface>
     */
    protected array $handlers = [];

    /**
     * Event dispatcher.
     */
    protected Dispatcher $events;

    /**
     * Logger instance.
     */
    protected LoggerInterface $logger;

    /**
     * Whether to dispatch Laravel events.
     */
    protected bool $dispatchEvents = true;

    /**
     * Whether to queue webhook processing.
     */
    protected bool $shouldQueue = false;

    /**
     * Create a new webhook processor.
     */
    public function __construct(
        Dispatcher $events,
        ?LoggerInterface $logger = null
    ) {
        $this->events = $events;
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Process an incoming webhook payload.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws WebhookException
     */
    public function process(array $payload): void
    {
        $dto = WebhookPayloadDto::fromPayload($payload);

        $this->logger->info('Processing webhook', [
            'event' => $dto->event,
            'instance' => $dto->instanceName,
        ]);

        try {
            // Always dispatch the generic webhook received event
            if ($this->dispatchEvents) {
                $this->dispatchWebhookReceived($dto);
            }

            // Process based on event type
            $this->processEvent($dto);

            // Call custom handlers
            $this->callHandlers($dto);

        } catch (\Throwable $e) {
            $this->logger->error('Webhook processing failed', [
                'event' => $dto->event,
                'instance' => $dto->instanceName,
                'error' => $e->getMessage(),
            ]);

            throw WebhookException::processingFailed($dto->event, $e);
        }
    }

    /**
     * Process the webhook event based on its type.
     */
    protected function processEvent(WebhookPayloadDto $dto): void
    {
        match ($dto->webhookEvent) {
            WebhookEvent::MESSAGES_UPSERT => $this->handleMessageReceived($dto),
            WebhookEvent::MESSAGES_UPDATE => $this->handleMessageUpdate($dto),
            WebhookEvent::SEND_MESSAGE => $this->handleMessageSent($dto),
            WebhookEvent::CONNECTION_UPDATE => $this->handleConnectionUpdate($dto),
            WebhookEvent::QRCODE_UPDATED => $this->handleQrCodeUpdated($dto),
            default => null, // Unhandled events are ignored
        };
    }

    /**
     * Handle incoming message webhook.
     */
    protected function handleMessageReceived(WebhookPayloadDto $dto): void
    {
        if (! $this->dispatchEvents) {
            return;
        }

        $messageData = $dto->getMessageData() ?? [];
        $senderData = $dto->getSenderData() ?? [];

        // Determine message type
        $messageType = $this->determineMessageType($messageData);

        $event = new MessageReceived(
            instanceName: $dto->instanceName,
            message: $messageData,
            sender: $senderData,
            messageType: $messageType,
            isGroup: $dto->isFromGroup(),
            groupId: $dto->getGroupId()
        );

        $this->events->dispatch($event);
    }

    /**
     * Handle message update webhook (delivered/read).
     */
    protected function handleMessageUpdate(WebhookPayloadDto $dto): void
    {
        if (! $this->dispatchEvents) {
            return;
        }

        $status = $dto->get('data.status') ?? $dto->get('status');
        $messageId = $dto->getMessageId();
        $remoteJid = $dto->getRemoteJid();

        if ($messageId === null || $remoteJid === null) {
            return;
        }

        // Status 3 = delivered, Status 4 = read
        if ($status === 3 || $status === 'DELIVERY_ACK') {
            $event = new MessageDelivered(
                instanceName: $dto->instanceName,
                messageId: $messageId,
                remoteJid: $remoteJid,
                data: $dto->data
            );
            $this->events->dispatch($event);
        }

        if ($status === 4 || $status === 'READ') {
            $event = new MessageRead(
                instanceName: $dto->instanceName,
                messageIds: $messageId,
                remoteJid: $remoteJid,
                data: $dto->data
            );
            $this->events->dispatch($event);
        }
    }

    /**
     * Handle sent message webhook.
     */
    protected function handleMessageSent(WebhookPayloadDto $dto): void
    {
        if (! $this->dispatchEvents) {
            return;
        }

        $messageData = $dto->getMessageData() ?? [];
        $messageType = $this->determineMessageType($messageData);

        $event = new MessageSent(
            instanceName: $dto->instanceName,
            messageType: $messageType?->value ?? 'unknown',
            message: $messageData,
            response: $dto->data
        );

        $this->events->dispatch($event);
    }

    /**
     * Handle connection update webhook.
     */
    protected function handleConnectionUpdate(WebhookPayloadDto $dto): void
    {
        if (! $this->dispatchEvents) {
            return;
        }

        $state = $dto->getConnectionStatus();

        if ($state === null) {
            return;
        }

        $status = $this->mapConnectionState($state);

        // Dispatch ConnectionUpdated event
        $connectionEvent = new ConnectionUpdated(
            instanceName: $dto->instanceName,
            status: $status,
            previousStatus: null, // Would need state tracking for this
            data: $dto->data
        );
        $this->events->dispatch($connectionEvent);

        // Also dispatch InstanceStatusChanged event
        $statusEvent = new InstanceStatusChanged(
            instanceName: $dto->instanceName,
            status: $status,
            previousStatus: null,
            phoneNumber: $dto->get('data.phoneNumber') ?? $dto->get('phoneNumber'),
            data: $dto->data
        );
        $this->events->dispatch($statusEvent);
    }

    /**
     * Handle QR code updated webhook.
     */
    protected function handleQrCodeUpdated(WebhookPayloadDto $dto): void
    {
        if (! $this->dispatchEvents) {
            return;
        }

        $qrCode = $dto->getQrCode();

        if ($qrCode === null) {
            return;
        }

        $event = new QrCodeReceived(
            instanceName: $dto->instanceName,
            qrCode: $qrCode,
            pairingCode: $dto->getPairingCode(),
            attempt: (int) ($dto->get('data.count') ?? $dto->get('count') ?? 1),
            data: $dto->data
        );

        $this->events->dispatch($event);
    }

    /**
     * Dispatch the generic webhook received event.
     */
    protected function dispatchWebhookReceived(WebhookPayloadDto $dto): void
    {
        $event = new WebhookReceived(
            instanceName: $dto->instanceName,
            event: $dto->event,
            payload: $dto->data,
            webhookEvent: $dto->webhookEvent
        );

        $this->events->dispatch($event);
    }

    /**
     * Call registered custom handlers.
     */
    protected function callHandlers(WebhookPayloadDto $dto): void
    {
        // Call event-specific handler
        if (isset($this->handlers[$dto->event])) {
            $this->handlers[$dto->event]->handle($dto);
        }

        // Call wildcard handler
        if (isset($this->handlers['*'])) {
            $this->handlers['*']->handle($dto);
        }
    }

    /**
     * Determine the message type from message data.
     *
     * @param  array<string, mixed>  $messageData
     */
    protected function determineMessageType(array $messageData): ?MessageType
    {
        $message = $messageData['message'] ?? $messageData;

        if (isset($message['conversation']) || isset($message['extendedTextMessage'])) {
            return MessageType::TEXT;
        }

        if (isset($message['imageMessage'])) {
            return MessageType::IMAGE;
        }

        if (isset($message['videoMessage'])) {
            return MessageType::VIDEO;
        }

        if (isset($message['audioMessage'])) {
            return MessageType::AUDIO;
        }

        if (isset($message['documentMessage'])) {
            return MessageType::DOCUMENT;
        }

        if (isset($message['stickerMessage'])) {
            return MessageType::STICKER;
        }

        if (isset($message['locationMessage'])) {
            return MessageType::LOCATION;
        }

        if (isset($message['contactMessage']) || isset($message['contactsArrayMessage'])) {
            return MessageType::CONTACT;
        }

        if (isset($message['reactionMessage'])) {
            return MessageType::REACTION;
        }

        if (isset($message['pollCreationMessage'])) {
            return MessageType::POLL;
        }

        if (isset($message['listMessage']) || isset($message['listResponseMessage'])) {
            return MessageType::LIST;
        }

        if (isset($message['buttonsMessage']) || isset($message['buttonsResponseMessage'])) {
            return MessageType::BUTTON;
        }

        if (isset($message['templateMessage'])) {
            return MessageType::TEMPLATE;
        }

        return null;
    }

    /**
     * Map connection state string to InstanceStatus.
     */
    protected function mapConnectionState(string $state): InstanceStatus
    {
        return match (strtolower($state)) {
            'open', 'connected' => InstanceStatus::OPEN,
            'close', 'closed', 'disconnected' => InstanceStatus::CLOSE,
            'connecting' => InstanceStatus::CONNECTING,
            'qrcode', 'qr' => InstanceStatus::QRCODE,
            default => InstanceStatus::UNKNOWN,
        };
    }

    /**
     * Register a custom webhook handler.
     */
    public function registerHandler(string $event, WebhookHandlerInterface $handler): self
    {
        $this->handlers[$event] = $handler;

        return $this;
    }

    /**
     * Register a wildcard handler for all events.
     */
    public function registerWildcardHandler(WebhookHandlerInterface $handler): self
    {
        return $this->registerHandler('*', $handler);
    }

    /**
     * Remove a registered handler.
     */
    public function removeHandler(string $event): self
    {
        unset($this->handlers[$event]);

        return $this;
    }

    /**
     * Enable event dispatching.
     */
    public function enableEvents(): self
    {
        $this->dispatchEvents = true;

        return $this;
    }

    /**
     * Disable event dispatching.
     */
    public function disableEvents(): self
    {
        $this->dispatchEvents = false;

        return $this;
    }

    /**
     * Check if events are enabled.
     */
    public function eventsEnabled(): bool
    {
        return $this->dispatchEvents;
    }

    /**
     * Set the logger instance.
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
