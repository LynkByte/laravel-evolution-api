<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Facades;

use Illuminate\Support\Facades\Facade;
use Lynkbyte\EvolutionApi\Resources\Chat;
use Lynkbyte\EvolutionApi\Resources\Group;
use Lynkbyte\EvolutionApi\Resources\Instance;
use Lynkbyte\EvolutionApi\Resources\Message;
use Lynkbyte\EvolutionApi\Resources\Profile;
use Lynkbyte\EvolutionApi\Resources\Settings;
use Lynkbyte\EvolutionApi\Resources\Webhook;
use Lynkbyte\EvolutionApi\Services\EvolutionService;
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

/**
 * @method static EvolutionService connection(string $name)
 * @method static Instance instance(?string $instanceName = null)
 * @method static Message message(?string $instanceName = null)
 * @method static Chat chat(?string $instanceName = null)
 * @method static Group group(?string $instanceName = null)
 * @method static Profile profile(?string $instanceName = null)
 * @method static Webhook webhook(?string $instanceName = null)
 * @method static Settings settings(?string $instanceName = null)
 * @method static array createInstance(array $data)
 * @method static array fetchInstances(?string $instanceName = null)
 * @method static array getQrCode(string $instanceName)
 * @method static array connectionState(string $instanceName)
 * @method static array sendText(string $instanceName, string $number, string $text, array $options = [])
 * @method static array sendMedia(string $instanceName, string $number, array $media)
 * @method static array sendAudio(string $instanceName, string $number, string $audio, array $options = [])
 * @method static array sendLocation(string $instanceName, string $number, float $latitude, float $longitude, array $options = [])
 * @method static array sendContact(string $instanceName, string $number, array $contact)
 * @method static array sendReaction(string $instanceName, string $messageId, string $reaction)
 * @method static array sendPoll(string $instanceName, string $number, string $name, array $values, array $options = [])
 * @method static array sendList(string $instanceName, string $number, array $list)
 * @method static bool isWhatsApp(string $instanceName, string $number)
 * @method static void fake(array $responses = [])
 * @method static void assertMessageSent(string $number)
 * @method static void assertMessageSentTimes(int $times)
 * @method static void assertNothingSent()
 *
 * @see \Lynkbyte\EvolutionApi\Services\EvolutionService
 */
class EvolutionApi extends Facade
{
    /**
     * The fake instance for testing.
     */
    protected static ?EvolutionApiFake $fake = null;

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'evolution-api';
    }

    /**
     * Replace the bound instance with a fake.
     *
     * @param  array<string, mixed>  $responses
     */
    public static function fake(array $responses = []): EvolutionApiFake
    {
        static::$fake = new EvolutionApiFake($responses);

        static::swap(static::$fake);

        return static::$fake;
    }

    /**
     * Determine if the facade is currently faked.
     */
    public static function isFaked(): bool
    {
        return static::$fake !== null;
    }

    /**
     * Clear the fake instance.
     */
    public static function clearFake(): void
    {
        static::$fake = null;
        static::clearResolvedInstance(static::getFacadeAccessor());
    }

    /**
     * Assert that a message was sent to the given number.
     */
    public static function assertMessageSent(string $number): void
    {
        static::ensureFaked();
        static::$fake->assertMessageSent($number);
    }

    /**
     * Assert that messages were sent a specific number of times.
     */
    public static function assertMessageSentTimes(int $times): void
    {
        static::ensureFaked();
        static::$fake->assertMessageSentTimes($times);
    }

    /**
     * Assert that no messages were sent.
     */
    public static function assertNothingSent(): void
    {
        static::ensureFaked();
        static::$fake->assertNothingSent();
    }

    /**
     * Assert that a message contains specific text.
     */
    public static function assertMessageContains(string $text): void
    {
        static::ensureFaked();
        static::$fake->assertMessageContains($text);
    }

    /**
     * Ensure the facade is currently faked.
     *
     * @throws \RuntimeException
     */
    protected static function ensureFaked(): void
    {
        if (static::$fake === null) {
            throw new \RuntimeException(
                'EvolutionApi facade is not faked. Call EvolutionApi::fake() first.'
            );
        }
    }
}
