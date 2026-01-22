<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Testing\Fakes;

use PHPUnit\Framework\Assert;

/**
 * Fake Evolution API service for testing.
 *
 * Provides a test double that records all interactions and allows
 * custom response stubbing for isolated testing.
 */
class EvolutionApiFake
{
    /**
     * Recorded messages.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $sentMessages = [];

    /**
     * Recorded API calls.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $apiCalls = [];

    /**
     * Stubbed responses.
     *
     * @var array<string, mixed>
     */
    protected array $responses = [];

    /**
     * Default responses for common operations.
     *
     * @var array<string, mixed>
     */
    protected array $defaultResponses = [];

    /**
     * Whether to record all interactions.
     */
    protected bool $recording = true;

    /**
     * Current instance name for chained calls.
     */
    protected ?string $currentInstance = null;

    /**
     * Create a new fake instance.
     *
     * @param  array<string, mixed>  $responses  Initial stubbed responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
        $this->setupDefaultResponses();
    }

    /**
     * Setup default responses for common operations.
     */
    protected function setupDefaultResponses(): void
    {
        $this->defaultResponses = [
            'sendText' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => true,
                    'id' => 'FAKE_MESSAGE_'.uniqid(),
                ],
                'message' => [
                    'conversation' => 'Test message',
                ],
                'messageTimestamp' => time(),
                'status' => 'PENDING',
            ],
            'sendMedia' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => true,
                    'id' => 'FAKE_MEDIA_'.uniqid(),
                ],
                'messageTimestamp' => time(),
                'status' => 'PENDING',
            ],
            'createInstance' => [
                'instance' => [
                    'instanceName' => 'test-instance',
                    'status' => 'created',
                ],
            ],
            'fetchInstances' => [
                [
                    'instance' => [
                        'instanceName' => 'test-instance',
                        'status' => 'open',
                    ],
                ],
            ],
            'connectionState' => [
                'instance' => [
                    'instanceName' => 'test-instance',
                    'state' => 'open',
                ],
            ],
            'getQrCode' => [
                'base64' => 'data:image/png;base64,FAKE_QR_CODE_DATA',
                'code' => 'fake-qr-code-string',
            ],
            'isWhatsApp' => [
                'exists' => true,
                'jid' => '5511999999999@s.whatsapp.net',
            ],
        ];
    }

    /**
     * Set the current instance for chained operations.
     *
     * @param  string  $name  Instance name
     * @return $this
     */
    public function connection(string $name): static
    {
        $this->currentInstance = $name;

        return $this;
    }

    /**
     * Send a text message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  string  $text  Message text
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed>
     */
    public function sendText(
        string $instanceName,
        string $number,
        string $text,
        array $options = []
    ): array {
        $this->recordMessage('text', $instanceName, $number, [
            'text' => $text,
            'options' => $options,
        ]);

        return $this->getResponse('sendText', [
            'key' => [
                'remoteJid' => $this->formatJid($number),
                'fromMe' => true,
                'id' => 'FAKE_MSG_'.uniqid(),
            ],
            'message' => ['conversation' => $text],
            'messageTimestamp' => time(),
        ]);
    }

    /**
     * Send media message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  array<string, mixed>  $media  Media data
     * @return array<string, mixed>
     */
    public function sendMedia(
        string $instanceName,
        string $number,
        array $media
    ): array {
        $this->recordMessage('media', $instanceName, $number, [
            'media' => $media,
        ]);

        return $this->getResponse('sendMedia');
    }

    /**
     * Send audio message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  string  $audio  Audio URL or base64
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed>
     */
    public function sendAudio(
        string $instanceName,
        string $number,
        string $audio,
        array $options = []
    ): array {
        $this->recordMessage('audio', $instanceName, $number, [
            'audio' => $audio,
            'options' => $options,
        ]);

        return $this->getResponse('sendAudio', $this->defaultResponses['sendMedia']);
    }

    /**
     * Send location message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed>
     */
    public function sendLocation(
        string $instanceName,
        string $number,
        float $latitude,
        float $longitude,
        array $options = []
    ): array {
        $this->recordMessage('location', $instanceName, $number, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'options' => $options,
        ]);

        return $this->getResponse('sendLocation', $this->defaultResponses['sendMedia']);
    }

    /**
     * Send contact message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  array<string, mixed>  $contact  Contact data
     * @return array<string, mixed>
     */
    public function sendContact(
        string $instanceName,
        string $number,
        array $contact
    ): array {
        $this->recordMessage('contact', $instanceName, $number, [
            'contact' => $contact,
        ]);

        return $this->getResponse('sendContact', $this->defaultResponses['sendMedia']);
    }

    /**
     * Send reaction message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $messageId  Message ID to react to
     * @param  string  $reaction  Reaction emoji
     * @return array<string, mixed>
     */
    public function sendReaction(
        string $instanceName,
        string $messageId,
        string $reaction
    ): array {
        $this->recordApiCall('sendReaction', [
            'instance' => $instanceName,
            'message_id' => $messageId,
            'reaction' => $reaction,
        ]);

        return $this->getResponse('sendReaction', ['status' => 'success']);
    }

    /**
     * Send poll message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  string  $name  Poll question
     * @param  array<string>  $values  Poll options
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed>
     */
    public function sendPoll(
        string $instanceName,
        string $number,
        string $name,
        array $values,
        array $options = []
    ): array {
        $this->recordMessage('poll', $instanceName, $number, [
            'name' => $name,
            'values' => $values,
            'options' => $options,
        ]);

        return $this->getResponse('sendPoll', $this->defaultResponses['sendMedia']);
    }

    /**
     * Send list message.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient phone number
     * @param  array<string, mixed>  $list  List data
     * @return array<string, mixed>
     */
    public function sendList(
        string $instanceName,
        string $number,
        array $list
    ): array {
        $this->recordMessage('list', $instanceName, $number, [
            'list' => $list,
        ]);

        return $this->getResponse('sendList', $this->defaultResponses['sendMedia']);
    }

    /**
     * Create a new instance.
     *
     * @param  array<string, mixed>  $data  Instance data
     * @return array<string, mixed>
     */
    public function createInstance(array $data): array
    {
        $this->recordApiCall('createInstance', $data);

        return $this->getResponse('createInstance');
    }

    /**
     * Fetch instances.
     *
     * @param  string|null  $instanceName  Optional instance name filter
     * @return array<string, mixed>
     */
    public function fetchInstances(?string $instanceName = null): array
    {
        $this->recordApiCall('fetchInstances', [
            'instance_name' => $instanceName,
        ]);

        return $this->getResponse('fetchInstances');
    }

    /**
     * Get QR code.
     *
     * @param  string  $instanceName  Instance name
     * @return array<string, mixed>
     */
    public function getQrCode(string $instanceName): array
    {
        $this->recordApiCall('getQrCode', [
            'instance' => $instanceName,
        ]);

        return $this->getResponse('getQrCode');
    }

    /**
     * Get connection state.
     *
     * @param  string  $instanceName  Instance name
     * @return array<string, mixed>
     */
    public function connectionState(string $instanceName): array
    {
        $this->recordApiCall('connectionState', [
            'instance' => $instanceName,
        ]);

        return $this->getResponse('connectionState');
    }

    /**
     * Check if number is on WhatsApp.
     *
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Phone number
     */
    public function isWhatsApp(string $instanceName, string $number): bool
    {
        $this->recordApiCall('isWhatsApp', [
            'instance' => $instanceName,
            'number' => $number,
        ]);

        $response = $this->getResponse('isWhatsApp');

        return $response['exists'] ?? true;
    }

    /**
     * Stub a response for a specific operation.
     *
     * @param  string  $operation  Operation name
     * @param  mixed  $response  Response to return
     * @return $this
     */
    public function stubResponse(string $operation, mixed $response): static
    {
        $this->responses[$operation] = $response;

        return $this;
    }

    /**
     * Stub responses using a callback.
     *
     * @param  callable  $callback  Callback that receives operation name and returns response
     * @return $this
     */
    public function stubUsing(callable $callback): static
    {
        $this->responses['_callback'] = $callback;

        return $this;
    }

    /**
     * Get a response for an operation.
     *
     * @param  string  $operation  Operation name
     * @param  array<string, mixed>|null  $default  Default response
     * @return array<string, mixed>
     */
    protected function getResponse(string $operation, ?array $default = null): array
    {
        // Check for callback stub
        if (isset($this->responses['_callback'])) {
            return ($this->responses['_callback'])($operation);
        }

        // Check for specific stub
        if (isset($this->responses[$operation])) {
            $response = $this->responses[$operation];

            return is_callable($response) ? $response() : $response;
        }

        // Return default
        return $default ?? $this->defaultResponses[$operation] ?? [];
    }

    /**
     * Record a message being sent.
     *
     * @param  string  $type  Message type
     * @param  string  $instanceName  Instance name
     * @param  string  $number  Recipient number
     * @param  array<string, mixed>  $data  Message data
     */
    protected function recordMessage(
        string $type,
        string $instanceName,
        string $number,
        array $data
    ): void {
        if (! $this->recording) {
            return;
        }

        $this->sentMessages[] = [
            'type' => $type,
            'instance' => $instanceName,
            'number' => $number,
            'data' => $data,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Record an API call.
     *
     * @param  string  $operation  Operation name
     * @param  array<string, mixed>  $data  Operation data
     */
    protected function recordApiCall(string $operation, array $data): void
    {
        if (! $this->recording) {
            return;
        }

        $this->apiCalls[] = [
            'operation' => $operation,
            'data' => $data,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Format a phone number as JID.
     */
    protected function formatJid(string $number): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        return $cleaned.'@s.whatsapp.net';
    }

    // =========================================================================
    // Assertion Methods
    // =========================================================================

    /**
     * Assert that a message was sent to the given number.
     *
     * @param  string  $number  Phone number
     * @param  callable|null  $callback  Optional callback for additional assertions
     */
    public function assertMessageSent(string $number, ?callable $callback = null): void
    {
        $found = array_filter($this->sentMessages, function ($message) use ($number) {
            return str_contains($message['number'], preg_replace('/[^0-9]/', '', $number));
        });

        Assert::assertNotEmpty(
            $found,
            "Failed asserting that a message was sent to [{$number}]."
        );

        if ($callback) {
            foreach ($found as $message) {
                $callback($message);
            }
        }
    }

    /**
     * Assert that no message was sent to the given number.
     *
     * @param  string  $number  Phone number
     */
    public function assertMessageNotSent(string $number): void
    {
        $found = array_filter($this->sentMessages, function ($message) use ($number) {
            return str_contains($message['number'], preg_replace('/[^0-9]/', '', $number));
        });

        Assert::assertEmpty(
            $found,
            "Failed asserting that no message was sent to [{$number}]."
        );
    }

    /**
     * Assert that messages were sent a specific number of times.
     *
     * @param  int  $times  Expected count
     */
    public function assertMessageSentTimes(int $times): void
    {
        $actual = count($this->sentMessages);

        Assert::assertEquals(
            $times,
            $actual,
            "Failed asserting that exactly {$times} messages were sent. Actually sent: {$actual}."
        );
    }

    /**
     * Assert that no messages were sent.
     */
    public function assertNothingSent(): void
    {
        Assert::assertEmpty(
            $this->sentMessages,
            'Failed asserting that no messages were sent. Messages sent: '.count($this->sentMessages)
        );
    }

    /**
     * Assert that a message contains specific text.
     *
     * @param  string  $text  Text to search for
     */
    public function assertMessageContains(string $text): void
    {
        $found = false;

        foreach ($this->sentMessages as $message) {
            if (isset($message['data']['text']) && str_contains($message['data']['text'], $text)) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            "Failed asserting that a message containing [{$text}] was sent."
        );
    }

    /**
     * Assert that a message of specific type was sent.
     *
     * @param  string  $type  Message type
     */
    public function assertMessageTypeWas(string $type): void
    {
        $found = array_filter($this->sentMessages, fn ($m) => $m['type'] === $type);

        Assert::assertNotEmpty(
            $found,
            "Failed asserting that a message of type [{$type}] was sent."
        );
    }

    /**
     * Assert that an API call was made.
     *
     * @param  string  $operation  Operation name
     * @param  callable|null  $callback  Optional callback for additional assertions
     */
    public function assertApiCalled(string $operation, ?callable $callback = null): void
    {
        $found = array_filter($this->apiCalls, fn ($c) => $c['operation'] === $operation);

        Assert::assertNotEmpty(
            $found,
            "Failed asserting that API operation [{$operation}] was called."
        );

        if ($callback) {
            foreach ($found as $call) {
                $callback($call);
            }
        }
    }

    /**
     * Assert that an API call was not made.
     *
     * @param  string  $operation  Operation name
     */
    public function assertApiNotCalled(string $operation): void
    {
        $found = array_filter($this->apiCalls, fn ($c) => $c['operation'] === $operation);

        Assert::assertEmpty(
            $found,
            "Failed asserting that API operation [{$operation}] was not called."
        );
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Get all sent messages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    /**
     * Get all API calls.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getApiCalls(): array
    {
        return $this->apiCalls;
    }

    /**
     * Get the last sent message.
     *
     * @return array<string, mixed>|null
     */
    public function getLastMessage(): ?array
    {
        return end($this->sentMessages) ?: null;
    }

    /**
     * Get the last API call.
     *
     * @return array<string, mixed>|null
     */
    public function getLastApiCall(): ?array
    {
        return end($this->apiCalls) ?: null;
    }

    /**
     * Clear all recorded interactions.
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->sentMessages = [];
        $this->apiCalls = [];

        return $this;
    }

    /**
     * Disable recording.
     *
     * @return $this
     */
    public function disableRecording(): static
    {
        $this->recording = false;

        return $this;
    }

    /**
     * Enable recording.
     *
     * @return $this
     */
    public function enableRecording(): static
    {
        $this->recording = true;

        return $this;
    }

    /**
     * Get message count by type.
     *
     * @return array<string, int>
     */
    public function getMessageCountByType(): array
    {
        $counts = [];

        foreach ($this->sentMessages as $message) {
            $type = $message['type'];
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return $counts;
    }

    // =========================================================================
    // Fake Resource Methods (for chained access)
    // =========================================================================

    /**
     * Get instance resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function instance(?string $instanceName = null): FakeInstanceResource
    {
        return new FakeInstanceResource($this, $instanceName ?? $this->currentInstance);
    }

    /**
     * Get message resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function message(?string $instanceName = null): FakeMessageResource
    {
        return new FakeMessageResource($this, $instanceName ?? $this->currentInstance);
    }

    /**
     * Get chat resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function chat(?string $instanceName = null): FakeChatResource
    {
        return new FakeChatResource($this, $instanceName ?? $this->currentInstance);
    }

    /**
     * Get group resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function group(?string $instanceName = null): FakeGroupResource
    {
        return new FakeGroupResource($this, $instanceName ?? $this->currentInstance);
    }

    /**
     * Get profile resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function profile(?string $instanceName = null): FakeProfileResource
    {
        return new FakeProfileResource($this, $instanceName ?? $this->currentInstance);
    }

    /**
     * Get webhook resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function webhook(?string $instanceName = null): FakeWebhookResource
    {
        return new FakeWebhookResource($this, $instanceName ?? $this->currentInstance);
    }

    /**
     * Get settings resource.
     *
     * @param  string|null  $instanceName  Instance name
     */
    public function settings(?string $instanceName = null): FakeSettingsResource
    {
        return new FakeSettingsResource($this, $instanceName ?? $this->currentInstance);
    }
}

/**
 * Base class for fake resources.
 */
abstract class FakeResource
{
    protected EvolutionApiFake $fake;

    protected ?string $instanceName;

    public function __construct(EvolutionApiFake $fake, ?string $instanceName)
    {
        $this->fake = $fake;
        $this->instanceName = $instanceName;
    }
}

/**
 * Fake instance resource.
 */
class FakeInstanceResource extends FakeResource
{
    public function create(array $data): array
    {
        return $this->fake->createInstance($data);
    }

    public function fetchAll(): array
    {
        return $this->fake->fetchInstances();
    }

    public function fetch(): array
    {
        return $this->fake->fetchInstances($this->instanceName);
    }

    public function getQrCode(): array
    {
        return $this->fake->getQrCode($this->instanceName);
    }

    public function connectionState(): array
    {
        return $this->fake->connectionState($this->instanceName);
    }
}

/**
 * Fake message resource.
 */
class FakeMessageResource extends FakeResource
{
    public function sendText(string $number, string $text, array $options = []): array
    {
        return $this->fake->sendText($this->instanceName, $number, $text, $options);
    }

    public function sendMedia(string $number, array $media): array
    {
        return $this->fake->sendMedia($this->instanceName, $number, $media);
    }

    public function sendAudio(string $number, string $audio, array $options = []): array
    {
        return $this->fake->sendAudio($this->instanceName, $number, $audio, $options);
    }

    public function sendLocation(string $number, float $lat, float $lng, array $options = []): array
    {
        return $this->fake->sendLocation($this->instanceName, $number, $lat, $lng, $options);
    }

    public function sendContact(string $number, array $contact): array
    {
        return $this->fake->sendContact($this->instanceName, $number, $contact);
    }

    public function sendPoll(string $number, string $name, array $values, array $options = []): array
    {
        return $this->fake->sendPoll($this->instanceName, $number, $name, $values, $options);
    }

    public function sendList(string $number, array $list): array
    {
        return $this->fake->sendList($this->instanceName, $number, $list);
    }
}

/**
 * Fake chat resource.
 */
class FakeChatResource extends FakeResource
{
    public function isWhatsApp(string $number): bool
    {
        return $this->fake->isWhatsApp($this->instanceName, $number);
    }

    public function findChats(): array
    {
        return [];
    }
}

/**
 * Fake group resource.
 */
class FakeGroupResource extends FakeResource
{
    public function fetchAll(): array
    {
        return [];
    }

    public function create(string $subject, array $participants): array
    {
        return ['id' => 'fake-group-'.uniqid()];
    }
}

/**
 * Fake profile resource.
 */
class FakeProfileResource extends FakeResource
{
    public function fetchProfile(): array
    {
        return [
            'name' => 'Test Profile',
            'status' => 'Available',
        ];
    }

    public function updateName(string $name): array
    {
        return ['status' => 'success'];
    }

    public function updateStatus(string $status): array
    {
        return ['status' => 'success'];
    }
}

/**
 * Fake webhook resource.
 */
class FakeWebhookResource extends FakeResource
{
    public function set(array $config): array
    {
        return ['status' => 'success'];
    }

    public function get(): array
    {
        return [
            'url' => 'https://example.com/webhook',
            'events' => ['MESSAGES_UPSERT'],
        ];
    }
}

/**
 * Fake settings resource.
 */
class FakeSettingsResource extends FakeResource
{
    public function get(): array
    {
        return [
            'reject_call' => false,
            'msg_call' => '',
            'groups_ignore' => false,
        ];
    }

    public function set(array $settings): array
    {
        return ['status' => 'success'];
    }
}
