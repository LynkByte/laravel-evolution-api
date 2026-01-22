<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Services;

use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\Contracts\EvolutionClientInterface;
use Lynkbyte\EvolutionApi\Contracts\RateLimiterInterface;
use Lynkbyte\EvolutionApi\Resources\Chat;
use Lynkbyte\EvolutionApi\Resources\Group;
use Lynkbyte\EvolutionApi\Resources\Instance;
use Lynkbyte\EvolutionApi\Resources\Message;
use Lynkbyte\EvolutionApi\Resources\Profile;
use Lynkbyte\EvolutionApi\Resources\Settings;
use Lynkbyte\EvolutionApi\Resources\Webhook;
use Psr\Log\LoggerInterface;

/**
 * Main Evolution API service class.
 *
 * Provides a fluent interface to all Evolution API functionality.
 *
 * @method Instance instance() Access instance management
 * @method Message message() Access message sending
 * @method Chat chat() Access chat operations
 * @method Profile profile() Access profile management
 * @method Group group() Access group management
 * @method Webhook webhook() Access webhook configuration
 * @method Settings settings() Access instance settings
 */
class EvolutionService
{
    /**
     * The Evolution API client.
     */
    protected EvolutionClient $client;

    /**
     * Cached resource instances.
     *
     * @var array<string, object>
     */
    protected array $resources = [];

    /**
     * Create a new Evolution API service.
     */
    public function __construct(
        protected ConnectionManager $connectionManager,
        protected ?RateLimiterInterface $rateLimiter = null,
        protected ?LoggerInterface $logger = null
    ) {
        $this->client = new EvolutionClient(
            $connectionManager,
            $rateLimiter,
            $logger
        );
    }

    /**
     * Create service from config array.
     *
     * @param  array<string, mixed>  $config
     */
    public static function make(array $config): self
    {
        return new self(new ConnectionManager($config));
    }

    /**
     * Get the instance resource.
     */
    public function instances(): Instance
    {
        return $this->getResource('instance', Instance::class);
    }

    /**
     * Get the message resource.
     */
    public function messages(): Message
    {
        return $this->getResource('message', Message::class);
    }

    /**
     * Get the chat resource.
     */
    public function chats(): Chat
    {
        return $this->getResource('chat', Chat::class);
    }

    /**
     * Get the profile resource.
     */
    public function profile(): Profile
    {
        return $this->getResource('profile', Profile::class);
    }

    /**
     * Get the group resource.
     */
    public function groups(): Group
    {
        return $this->getResource('group', Group::class);
    }

    /**
     * Get the webhook resource.
     */
    public function webhooks(): Webhook
    {
        return $this->getResource('webhook', Webhook::class);
    }

    /**
     * Get the settings resource.
     */
    public function settings(): Settings
    {
        return $this->getResource('settings', Settings::class);
    }

    /**
     * Get or create a resource instance.
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function getResource(string $name, string $class): object
    {
        if (! isset($this->resources[$name])) {
            $this->resources[$name] = new $class($this->client);
        }

        return $this->resources[$name];
    }

    /**
     * Switch to a different connection.
     *
     * @return $this
     */
    public function connection(string $name): self
    {
        $this->client->connection($name);

        // Clear cached resources when switching connections
        $this->resources = [];

        return $this;
    }

    /**
     * Set the instance to use for subsequent requests.
     *
     * @return $this
     */
    public function for(string $instanceName): self
    {
        $this->client->instance($instanceName);

        return $this;
    }

    /**
     * Alias for for() method.
     *
     * @return $this
     */
    public function use(string $instanceName): self
    {
        return $this->for($instanceName);
    }

    /**
     * Get the underlying client.
     */
    public function getClient(): EvolutionClientInterface
    {
        return $this->client;
    }

    /**
     * Get the connection manager.
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }

    /**
     * Check if the API is reachable.
     */
    public function ping(): bool
    {
        return $this->client->ping();
    }

    /**
     * Get API server information.
     *
     * @return array<string, mixed>
     */
    public function info(): array
    {
        return $this->client->info();
    }

    // =========================================================================
    // Shortcut Methods for Common Operations
    // =========================================================================

    /**
     * Send a text message (shortcut).
     */
    public function sendText(string $to, string $text): \Lynkbyte\EvolutionApi\DTOs\ApiResponse
    {
        return $this->messages()->text($to, $text);
    }

    /**
     * Send an image (shortcut).
     */
    public function sendImage(
        string $to,
        string $media,
        ?string $caption = null
    ): \Lynkbyte\EvolutionApi\DTOs\ApiResponse {
        return $this->messages()->image($to, $media, $caption);
    }

    /**
     * Send a document (shortcut).
     */
    public function sendDocument(
        string $to,
        string $media,
        ?string $fileName = null,
        ?string $caption = null
    ): \Lynkbyte\EvolutionApi\DTOs\ApiResponse {
        return $this->messages()->document($to, $media, $caption, $fileName);
    }

    /**
     * Send a location (shortcut).
     */
    public function sendLocation(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null
    ): \Lynkbyte\EvolutionApi\DTOs\ApiResponse {
        return $this->messages()->location($to, $latitude, $longitude, $name);
    }

    /**
     * Check if a number is on WhatsApp (shortcut).
     */
    public function isOnWhatsApp(string $number): \Lynkbyte\EvolutionApi\DTOs\ApiResponse
    {
        return $this->chats()->isOnWhatsApp($number);
    }

    /**
     * Create a new instance (shortcut).
     */
    public function createInstance(string $name): \Lynkbyte\EvolutionApi\DTOs\ApiResponse
    {
        return $this->instances()->create($name);
    }

    /**
     * Get QR code for an instance (shortcut).
     */
    public function getQrCode(?string $instanceName = null): \Lynkbyte\EvolutionApi\DTOs\ApiResponse
    {
        return $this->instances()->getQrCode($instanceName);
    }

    /**
     * Get connection state (shortcut).
     */
    public function connectionState(?string $instanceName = null): \Lynkbyte\EvolutionApi\DTOs\ApiResponse
    {
        return $this->instances()->connectionState($instanceName);
    }

    /**
     * Check if instance is connected (shortcut).
     */
    public function isConnected(?string $instanceName = null): bool
    {
        return $this->instances()->isConnected($instanceName);
    }

    /**
     * Create a new group (shortcut).
     *
     * @param  array<string>  $participants
     */
    public function createGroup(
        string $name,
        array $participants,
        ?string $description = null
    ): \Lynkbyte\EvolutionApi\DTOs\ApiResponse {
        return $this->groups()->create($name, $participants, $description);
    }

    // =========================================================================
    // Dynamic Method Access
    // =========================================================================

    /**
     * Handle dynamic method calls.
     *
     * @param  array<mixed>  $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        // Map singular method names to resource methods
        $resourceMap = [
            'instance' => 'instances',
            'message' => 'messages',
            'chat' => 'chats',
            'group' => 'groups',
            'webhook' => 'webhooks',
            'setting' => 'settings',
        ];

        if (isset($resourceMap[$method])) {
            return $this->{$resourceMap[$method]}();
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist on ".static::class);
    }
}
