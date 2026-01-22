<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use Lynkbyte\EvolutionApi\Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature/Models', 'Feature/Console/Commands');

pest()->extend(TestCase::class)
    ->in('Feature/Client', 'Feature/Resources', 'Feature/Services', 'Feature/Webhooks', 'Feature/Jobs');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeValidPhoneNumber', function () {
    return $this->toMatch('/^\d{10,15}$/');
});

expect()->extend('toBeValidJid', function () {
    return $this->toMatch('/^\d+@(s\.whatsapp\.net|g\.us)$/');
});

expect()->extend('toBeValidMessageId', function () {
    return $this->toBeString()->not->toBeEmpty();
});

expect()->extend('toBeSuccessfulApiResponse', function () {
    return $this->toBeArray()
        ->toHaveKey('key')
        ->toHaveKey('messageTimestamp');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Get a fixture file path.
 */
function fixture(string $path): string
{
    return __DIR__ . '/Fixtures/' . ltrim($path, '/');
}

/**
 * Load a fixture file as array.
 */
function fixtureArray(string $path): array
{
    $content = file_get_contents(fixture($path));
    return json_decode($content, true);
}

/**
 * Load a fixture file as JSON string.
 */
function fixtureJson(string $path): string
{
    return file_get_contents(fixture($path));
}

/**
 * Create a mock HTTP response.
 */
function mockHttpResponse(array $data, int $status = 200): \GuzzleHttp\Psr7\Response
{
    return new \GuzzleHttp\Psr7\Response(
        $status,
        ['Content-Type' => 'application/json'],
        json_encode($data)
    );
}

/**
 * Create a sample webhook payload.
 */
function sampleWebhookPayload(string $event = 'MESSAGES_UPSERT', array $overrides = []): array
{
    $base = [
        'event' => $event,
        'instance' => 'test-instance',
        'data' => [
            'key' => [
                'remoteJid' => '5511999999999@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG_' . uniqid(),
            ],
            'message' => [
                'conversation' => 'Test message',
            ],
            'messageTimestamp' => time(),
        ],
        'destination' => 'http://localhost/webhook',
        'date_time' => date('Y-m-d H:i:s'),
        'sender' => '5511999999999@s.whatsapp.net',
        'server_url' => 'http://localhost:8080',
        'apikey' => 'test-api-key',
    ];

    return array_replace_recursive($base, $overrides);
}

/**
 * Create a sample message response.
 */
function sampleMessageResponse(array $overrides = []): array
{
    $base = [
        'key' => [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'fromMe' => true,
            'id' => 'MSG_' . uniqid(),
        ],
        'message' => [
            'conversation' => 'Test message',
        ],
        'messageTimestamp' => time(),
        'status' => 'PENDING',
    ];

    return array_replace_recursive($base, $overrides);
}

/**
 * Create a sample instance response.
 */
function sampleInstanceResponse(string $status = 'open', array $overrides = []): array
{
    $base = [
        'instance' => [
            'instanceName' => 'test-instance',
            'status' => $status,
            'state' => $status,
        ],
    ];

    return array_replace_recursive($base, $overrides);
}

/**
 * Format a phone number as WhatsApp JID.
 */
function formatJid(string $number, bool $isGroup = false): string
{
    $cleaned = preg_replace('/[^0-9]/', '', $number);
    return $cleaned . ($isGroup ? '@g.us' : '@s.whatsapp.net');
}
