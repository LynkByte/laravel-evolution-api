<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Tests;

use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Test helper class with utility methods.
 */
class Helpers
{
    /**
     * Create a mock API response.
     *
     * @param array<string, mixed> $data
     * @param bool $success
     * @param int $statusCode
     * @return ApiResponse
     */
    public static function createApiResponse(
        array $data = [],
        bool $success = true,
        int $statusCode = 200
    ): ApiResponse {
        return $success
            ? ApiResponse::success($data, $statusCode)
            : ApiResponse::failure($data['message'] ?? 'Error', $statusCode, $data);
    }

    /**
     * Create a sample text message payload.
     *
     * @param string $number
     * @param string $text
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function textMessagePayload(
        string $number = '5511999999999',
        string $text = 'Hello World',
        array $options = []
    ): array {
        return array_merge([
            'number' => $number,
            'text' => $text,
        ], $options);
    }

    /**
     * Create a sample media message payload.
     *
     * @param string $number
     * @param string $mediaType
     * @param string $url
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function mediaMessagePayload(
        string $number = '5511999999999',
        string $mediaType = 'image',
        string $url = 'https://example.com/image.jpg',
        array $options = []
    ): array {
        return array_merge([
            'number' => $number,
            'mediatype' => $mediaType,
            'media' => $url,
        ], $options);
    }

    /**
     * Create a sample location message payload.
     *
     * @param string $number
     * @param float $latitude
     * @param float $longitude
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function locationMessagePayload(
        string $number = '5511999999999',
        float $latitude = -23.5505,
        float $longitude = -46.6333,
        array $options = []
    ): array {
        return array_merge([
            'number' => $number,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ], $options);
    }

    /**
     * Create a sample contact message payload.
     *
     * @param string $number
     * @param string $contactName
     * @param string $contactNumber
     * @return array<string, mixed>
     */
    public static function contactMessagePayload(
        string $number = '5511999999999',
        string $contactName = 'John Doe',
        string $contactNumber = '5511888888888'
    ): array {
        return [
            'number' => $number,
            'contact' => [
                [
                    'fullName' => $contactName,
                    'wuid' => $contactNumber,
                    'phoneNumber' => $contactNumber,
                ],
            ],
        ];
    }

    /**
     * Create a sample poll message payload.
     *
     * @param string $number
     * @param string $name
     * @param array<string> $values
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function pollMessagePayload(
        string $number = '5511999999999',
        string $name = 'What is your favorite color?',
        array $values = ['Red', 'Blue', 'Green'],
        array $options = []
    ): array {
        return array_merge([
            'number' => $number,
            'name' => $name,
            'values' => $values,
            'selectableCount' => 1,
        ], $options);
    }

    /**
     * Create a sample instance data.
     *
     * @param string $name
     * @param string $status
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function instanceData(
        string $name = 'test-instance',
        string $status = 'open',
        array $options = []
    ): array {
        return array_merge([
            'instanceName' => $name,
            'status' => $status,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ], $options);
    }

    /**
     * Create a sample webhook event payload.
     *
     * @param string $event
     * @param string $instance
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function webhookEvent(
        string $event = 'MESSAGES_UPSERT',
        string $instance = 'test-instance',
        array $data = []
    ): array {
        $defaultData = match ($event) {
            'MESSAGES_UPSERT' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'MSG_' . uniqid(),
                ],
                'message' => [
                    'conversation' => 'Hello!',
                ],
                'messageTimestamp' => time(),
            ],
            'CONNECTION_UPDATE' => [
                'state' => 'open',
                'statusReason' => 200,
            ],
            'QRCODE_UPDATED' => [
                'qrcode' => [
                    'base64' => 'data:image/png;base64,abc123',
                    'code' => '2@QRCODE',
                ],
            ],
            default => [],
        };

        return [
            'event' => $event,
            'instance' => $instance,
            'data' => array_merge($defaultData, $data),
            'destination' => 'http://localhost/webhook',
            'date_time' => date('Y-m-d H:i:s'),
            'server_url' => 'http://localhost:8080',
        ];
    }

    /**
     * Mock multiple Evolution API endpoints.
     *
     * @param array<string, array> $endpoints
     */
    public static function mockEndpoints(array $endpoints): void
    {
        $responses = [];

        foreach ($endpoints as $pattern => $response) {
            $status = $response['status'] ?? 200;
            $body = $response['body'] ?? $response;

            $responses[$pattern] = Http::response($body, $status);
        }

        Http::fake($responses);
    }

    /**
     * Create a rate limit exceeded response.
     *
     * @param int $retryAfter
     * @return array<string, mixed>
     */
    public static function rateLimitResponse(int $retryAfter = 60): array
    {
        return [
            'status' => 429,
            'body' => [
                'error' => true,
                'message' => 'Rate limit exceeded',
                'retry_after' => $retryAfter,
            ],
        ];
    }

    /**
     * Create an authentication error response.
     *
     * @return array<string, mixed>
     */
    public static function authErrorResponse(): array
    {
        return [
            'status' => 401,
            'body' => [
                'error' => true,
                'message' => 'Invalid API key',
            ],
        ];
    }

    /**
     * Create a not found error response.
     *
     * @param string $resource
     * @return array<string, mixed>
     */
    public static function notFoundResponse(string $resource = 'Instance'): array
    {
        return [
            'status' => 404,
            'body' => [
                'error' => true,
                'message' => "{$resource} not found",
            ],
        ];
    }

    /**
     * Create a validation error response.
     *
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    public static function validationErrorResponse(array $errors = []): array
    {
        return [
            'status' => 422,
            'body' => [
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $errors ?: ['number' => 'The number field is required'],
            ],
        ];
    }

    /**
     * Generate a random message ID.
     *
     * @param string $prefix
     * @return string
     */
    public static function messageId(string $prefix = 'MSG'): string
    {
        return $prefix . '_' . strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Generate a random instance name.
     *
     * @return string
     */
    public static function instanceName(): string
    {
        return 'instance_' . strtolower(bin2hex(random_bytes(4)));
    }

    /**
     * Generate a random Brazilian phone number.
     *
     * @param bool $withCountryCode
     * @return string
     */
    public static function phoneNumber(bool $withCountryCode = true): string
    {
        $ddd = rand(11, 99);
        $number = rand(900000000, 999999999);

        return $withCountryCode ? "55{$ddd}{$number}" : "{$ddd}{$number}";
    }

    /**
     * Generate a WhatsApp JID from a phone number.
     *
     * @param string|null $number
     * @param bool $isGroup
     * @return string
     */
    public static function jid(?string $number = null, bool $isGroup = false): string
    {
        $number = $number ?? self::phoneNumber();
        $suffix = $isGroup ? '@g.us' : '@s.whatsapp.net';

        return preg_replace('/[^0-9]/', '', $number) . $suffix;
    }
}
