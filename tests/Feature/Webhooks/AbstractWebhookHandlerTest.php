<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Webhook\WebhookPayloadDto;
use Lynkbyte\EvolutionApi\Enums\WebhookEvent;
use Lynkbyte\EvolutionApi\Webhooks\AbstractWebhookHandler;

// Concrete implementation for testing
class TestableWebhookHandler extends AbstractWebhookHandler
{
    public array $receivedPayloads = [];
    public array $calledMethods = [];

    public function events(): array
    {
        return array_map(fn($event) => $event->value, $this->allowedEvents);
    }

    protected function onWebhookReceived(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onWebhookReceived';
        $this->receivedPayloads[] = $payload;
    }

    protected function onMessageReceived(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onMessageReceived';
    }

    protected function onMessageUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onMessageUpdated';
    }

    protected function onMessageSent(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onMessageSent';
    }

    protected function onMessageDeleted(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onMessageDeleted';
    }

    protected function onConnectionUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onConnectionUpdated';
    }

    protected function onQrCodeReceived(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onQrCodeReceived';
    }

    protected function onPresenceUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onPresenceUpdated';
    }

    protected function onGroupCreated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onGroupCreated';
    }

    protected function onGroupUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onGroupUpdated';
    }

    protected function onGroupParticipantsUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onGroupParticipantsUpdated';
    }

    protected function onContactCreated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onContactCreated';
    }

    protected function onContactUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onContactUpdated';
    }

    protected function onChatCreated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onChatCreated';
    }

    protected function onChatUpdated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onChatUpdated';
    }

    protected function onChatDeleted(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onChatDeleted';
    }

    protected function onCallReceived(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onCallReceived';
    }

    protected function onLabelsEdited(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onLabelsEdited';
    }

    protected function onLabelsAssociated(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onLabelsAssociated';
    }

    protected function onUnknownEvent(WebhookPayloadDto $payload): void
    {
        $this->calledMethods[] = 'onUnknownEvent';
    }

    // Expose protected properties for testing
    public function getAllowedInstances(): array
    {
        return $this->allowedInstances;
    }

    public function getAllowedEvents(): array
    {
        return $this->allowedEvents;
    }
}

describe('AbstractWebhookHandler', function () {

    beforeEach(function () {
        $this->handler = new TestableWebhookHandler();
    });

    describe('shouldHandle', function () {
        it('returns true for all payloads by default', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => ['message' => 'test'],
            ]);

            expect($this->handler->shouldHandle($payload))->toBeTrue();
        });

        it('filters by instance when allowedInstances is set', function () {
            $this->handler->forInstances(['allowed-instance']);

            $allowedPayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'allowed-instance',
                'data' => [],
            ]);

            $blockedPayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'blocked-instance',
                'data' => [],
            ]);

            expect($this->handler->shouldHandle($allowedPayload))->toBeTrue();
            expect($this->handler->shouldHandle($blockedPayload))->toBeFalse();
        });

        it('filters by event when allowedEvents is set', function () {
            $this->handler->forEvents([WebhookEvent::MESSAGES_UPSERT]);

            $allowedPayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ]);

            $blockedPayload = WebhookPayloadDto::fromPayload([
                'event' => 'SEND_MESSAGE',
                'instance' => 'test-instance',
                'data' => [],
            ]);

            expect($this->handler->shouldHandle($allowedPayload))->toBeTrue();
            expect($this->handler->shouldHandle($blockedPayload))->toBeFalse();
        });

        it('allows payloads matching both instance and event filters', function () {
            $this->handler
                ->forInstances(['my-instance'])
                ->forEvents([WebhookEvent::MESSAGES_UPSERT, WebhookEvent::SEND_MESSAGE]);

            $matchingPayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'my-instance',
                'data' => [],
            ]);

            $wrongInstance = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'other-instance',
                'data' => [],
            ]);

            $wrongEvent = WebhookPayloadDto::fromPayload([
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'my-instance',
                'data' => [],
            ]);

            expect($this->handler->shouldHandle($matchingPayload))->toBeTrue();
            expect($this->handler->shouldHandle($wrongInstance))->toBeFalse();
            expect($this->handler->shouldHandle($wrongEvent))->toBeFalse();
        });
    });

    describe('handle', function () {
        it('does not call handlers when shouldHandle returns false', function () {
            $this->handler->forInstances(['allowed-instance']);

            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'blocked-instance',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toBeEmpty();
        });

        it('calls event handler and onWebhookReceived when handling', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test-instance',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onMessageReceived');
            expect($this->handler->calledMethods)->toContain('onWebhookReceived');
        });
    });

    describe('event routing', function () {
        it('routes MESSAGES_UPSERT to onMessageReceived', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onMessageReceived');
        });

        it('routes MESSAGES_UPDATE to onMessageUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onMessageUpdated');
        });

        it('routes SEND_MESSAGE to onMessageSent', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'SEND_MESSAGE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onMessageSent');
        });

        it('routes MESSAGES_DELETE to onMessageDeleted', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_DELETE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onMessageDeleted');
        });

        it('routes CONNECTION_UPDATE to onConnectionUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onConnectionUpdated');
        });

        it('routes QRCODE_UPDATED to onQrCodeReceived', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'QRCODE_UPDATED',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onQrCodeReceived');
        });

        it('routes PRESENCE_UPDATE to onPresenceUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'PRESENCE_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onPresenceUpdated');
        });

        it('routes GROUPS_UPSERT to onGroupCreated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'GROUPS_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onGroupCreated');
        });

        it('routes GROUP_UPDATE to onGroupUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'GROUP_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onGroupUpdated');
        });

        it('routes GROUP_PARTICIPANTS_UPDATE to onGroupParticipantsUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'GROUP_PARTICIPANTS_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onGroupParticipantsUpdated');
        });

        it('routes CONTACTS_UPSERT to onContactCreated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CONTACTS_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onContactCreated');
        });

        it('routes CONTACTS_UPDATE to onContactUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CONTACTS_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onContactUpdated');
        });

        it('routes CHATS_UPSERT to onChatCreated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CHATS_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onChatCreated');
        });

        it('routes CHATS_UPDATE to onChatUpdated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CHATS_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onChatUpdated');
        });

        it('routes CHATS_DELETE to onChatDeleted', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CHATS_DELETE',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onChatDeleted');
        });

        it('routes CALL to onCallReceived', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'CALL',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onCallReceived');
        });

        it('routes LABELS_EDIT to onLabelsEdited', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'LABELS_EDIT',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onLabelsEdited');
        });

        it('routes LABELS_ASSOCIATION to onLabelsAssociated', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'LABELS_ASSOCIATION',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onLabelsAssociated');
        });

        it('routes unknown events to onUnknownEvent', function () {
            $payload = WebhookPayloadDto::fromPayload([
                'event' => 'UNKNOWN_EVENT',
                'instance' => 'test',
                'data' => [],
            ]);

            $this->handler->handle($payload);

            expect($this->handler->calledMethods)->toContain('onUnknownEvent');
        });
    });

    describe('forInstances', function () {
        it('sets allowed instances', function () {
            $this->handler->forInstances(['instance-1', 'instance-2']);

            expect($this->handler->getAllowedInstances())->toBe(['instance-1', 'instance-2']);
        });

        it('returns self for fluent chaining', function () {
            $result = $this->handler->forInstances(['test']);

            expect($result)->toBe($this->handler);
        });
    });

    describe('forEvents', function () {
        it('sets allowed events', function () {
            $events = [WebhookEvent::MESSAGES_UPSERT, WebhookEvent::SEND_MESSAGE];
            $this->handler->forEvents($events);

            expect($this->handler->getAllowedEvents())->toBe($events);
        });

        it('returns self for fluent chaining', function () {
            $result = $this->handler->forEvents([WebhookEvent::CALL]);

            expect($result)->toBe($this->handler);
        });
    });

    describe('onlyMessageEvents', function () {
        it('sets allowed events to message-related events', function () {
            $this->handler->onlyMessageEvents();

            $allowedEvents = $this->handler->getAllowedEvents();

            expect($allowedEvents)->toContain(WebhookEvent::MESSAGES_SET);
            expect($allowedEvents)->toContain(WebhookEvent::MESSAGES_UPSERT);
            expect($allowedEvents)->toContain(WebhookEvent::MESSAGES_UPDATE);
            expect($allowedEvents)->toContain(WebhookEvent::MESSAGES_DELETE);
            expect($allowedEvents)->toContain(WebhookEvent::SEND_MESSAGE);
            expect(count($allowedEvents))->toBe(5);
        });

        it('returns self for fluent chaining', function () {
            $result = $this->handler->onlyMessageEvents();

            expect($result)->toBe($this->handler);
        });

        it('filters non-message events', function () {
            $this->handler->onlyMessageEvents();

            $messagePayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            $connectionPayload = WebhookPayloadDto::fromPayload([
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            expect($this->handler->shouldHandle($messagePayload))->toBeTrue();
            expect($this->handler->shouldHandle($connectionPayload))->toBeFalse();
        });
    });

    describe('onlyConnectionEvents', function () {
        it('sets allowed events to connection-related events', function () {
            $this->handler->onlyConnectionEvents();

            $allowedEvents = $this->handler->getAllowedEvents();

            expect($allowedEvents)->toContain(WebhookEvent::CONNECTION_UPDATE);
            expect($allowedEvents)->toContain(WebhookEvent::QRCODE_UPDATED);
            expect(count($allowedEvents))->toBe(2);
        });

        it('returns self for fluent chaining', function () {
            $result = $this->handler->onlyConnectionEvents();

            expect($result)->toBe($this->handler);
        });

        it('filters non-connection events', function () {
            $this->handler->onlyConnectionEvents();

            $connectionPayload = WebhookPayloadDto::fromPayload([
                'event' => 'CONNECTION_UPDATE',
                'instance' => 'test',
                'data' => [],
            ]);

            $messagePayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            expect($this->handler->shouldHandle($connectionPayload))->toBeTrue();
            expect($this->handler->shouldHandle($messagePayload))->toBeFalse();
        });
    });

    describe('onlyGroupEvents', function () {
        it('sets allowed events to group-related events', function () {
            $this->handler->onlyGroupEvents();

            $allowedEvents = $this->handler->getAllowedEvents();

            expect($allowedEvents)->toContain(WebhookEvent::GROUPS_UPSERT);
            expect($allowedEvents)->toContain(WebhookEvent::GROUP_UPDATE);
            expect($allowedEvents)->toContain(WebhookEvent::GROUP_PARTICIPANTS_UPDATE);
            expect(count($allowedEvents))->toBe(3);
        });

        it('returns self for fluent chaining', function () {
            $result = $this->handler->onlyGroupEvents();

            expect($result)->toBe($this->handler);
        });

        it('filters non-group events', function () {
            $this->handler->onlyGroupEvents();

            $groupPayload = WebhookPayloadDto::fromPayload([
                'event' => 'GROUPS_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            $messagePayload = WebhookPayloadDto::fromPayload([
                'event' => 'MESSAGES_UPSERT',
                'instance' => 'test',
                'data' => [],
            ]);

            expect($this->handler->shouldHandle($groupPayload))->toBeTrue();
            expect($this->handler->shouldHandle($messagePayload))->toBeFalse();
        });
    });

    describe('fluent interface', function () {
        it('supports chaining multiple configuration methods', function () {
            $result = $this->handler
                ->forInstances(['test-instance'])
                ->forEvents([WebhookEvent::MESSAGES_UPSERT]);

            expect($result)->toBe($this->handler);
            expect($this->handler->getAllowedInstances())->toBe(['test-instance']);
            expect($this->handler->getAllowedEvents())->toBe([WebhookEvent::MESSAGES_UPSERT]);
        });
    });

});
