<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;

/**
 * Webhook management resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/webhook
 */
class Webhook extends Resource
{
    /**
     * Set webhook configuration for an instance.
     *
     * @param  array<string>|null  $events  Array of webhook events to subscribe to
     */
    public function set(
        string $url,
        ?array $events = null,
        bool $enabled = true,
        ?bool $webhookBase64 = null,
        ?bool $webhookByEvents = null
    ): ApiResponse {
        $this->ensureInstance();

        $data = $this->filterNull([
            'url' => $url,
            'enabled' => $enabled,
            'events' => $events,
            'webhookBase64' => $webhookBase64,
            'webhookByEvents' => $webhookByEvents,
        ]);

        return $this->post('webhook/set/{instance}', $data);
    }

    /**
     * Get webhook configuration for an instance.
     */
    public function find(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('webhook/find/{instance}');
    }

    /**
     * Enable webhook for instance.
     */
    public function enable(string $url, ?array $events = null): ApiResponse
    {
        return $this->set($url, $events, true);
    }

    /**
     * Disable webhook for instance.
     */
    public function disable(): ApiResponse
    {
        return $this->set('', null, false);
    }

    /**
     * Update webhook URL.
     */
    public function updateUrl(string $url): ApiResponse
    {
        $current = $this->find();

        $events = $current->get('events');

        return $this->set($url, $events, true);
    }

    /**
     * Subscribe to specific events.
     *
     * @param  array<string|WebhookEvent>  $events
     */
    public function subscribeToEvents(array $events, ?string $url = null): ApiResponse
    {
        // Convert enums to strings if needed
        $eventStrings = array_map(
            fn ($event) => $event instanceof WebhookEvent ? $event->value : $event,
            $events
        );

        if ($url === null) {
            $current = $this->find();
            $url = $current->get('url');
        }

        return $this->set($url, $eventStrings, true);
    }

    /**
     * Subscribe to all available events.
     */
    public function subscribeToAll(?string $url = null): ApiResponse
    {
        $allEvents = array_map(fn ($event) => $event->value, WebhookEvent::cases());

        return $this->subscribeToEvents($allEvents, $url);
    }

    /**
     * Subscribe to message events only.
     */
    public function subscribeToMessages(?string $url = null): ApiResponse
    {
        $messageEvents = [
            WebhookEvent::MESSAGES_SET->value,
            WebhookEvent::MESSAGES_UPSERT->value,
            WebhookEvent::MESSAGES_UPDATE->value,
            WebhookEvent::MESSAGES_DELETE->value,
            WebhookEvent::SEND_MESSAGE->value,
        ];

        return $this->subscribeToEvents($messageEvents, $url);
    }

    /**
     * Subscribe to connection events only.
     */
    public function subscribeToConnection(?string $url = null): ApiResponse
    {
        $connectionEvents = [
            WebhookEvent::CONNECTION_UPDATE->value,
            WebhookEvent::QRCODE_UPDATED->value,
        ];

        return $this->subscribeToEvents($connectionEvents, $url);
    }

    /**
     * Get list of available webhook events.
     *
     * @return array<string>
     */
    public function availableEvents(): array
    {
        return array_map(fn ($event) => $event->value, WebhookEvent::cases());
    }
}
