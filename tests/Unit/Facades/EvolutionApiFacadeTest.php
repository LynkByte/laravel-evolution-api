<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Facades\EvolutionApi;
use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;

describe('EvolutionApi Facade', function () {

    afterEach(function () {
        // Clean up fake after each test
        EvolutionApi::clearFake();
    });

    describe('getFacadeAccessor', function () {
        it('has correct facade accessor name', function () {
            // We test the accessor by checking what fake() swaps
            $fake = EvolutionApi::fake();
            
            expect($fake)->toBeInstanceOf(EvolutionApiFake::class);
            expect(EvolutionApi::getFacadeRoot())->toBe($fake);
        });
    });

    describe('fake()', function () {
        it('swaps the bound instance with a fake', function () {
            $fake = EvolutionApi::fake();

            expect($fake)->toBeInstanceOf(EvolutionApiFake::class);
            expect(EvolutionApi::getFacadeRoot())->toBe($fake);
        });

        it('accepts custom stubbed responses', function () {
            $fake = EvolutionApi::fake([
                'sendText' => ['custom' => 'response'],
            ]);

            $result = EvolutionApi::sendText('instance', '5511999999999', 'Hello');

            expect($result)->toBe(['custom' => 'response']);
        });

        it('returns the fake instance for chaining', function () {
            $fake = EvolutionApi::fake();

            expect($fake)->toBeInstanceOf(EvolutionApiFake::class);
        });

        it('replaces previous fake when called multiple times', function () {
            $fake1 = EvolutionApi::fake(['sendText' => ['first' => true]]);
            $fake2 = EvolutionApi::fake(['sendText' => ['second' => true]]);

            $result = EvolutionApi::sendText('instance', '5511999999999', 'Test');

            expect($result)->toBe(['second' => true]);
            expect($fake1)->not->toBe($fake2);
        });
    });

    describe('isFaked()', function () {
        it('returns false when not faked', function () {
            expect(EvolutionApi::isFaked())->toBeFalse();
        });

        it('returns true after fake() is called', function () {
            EvolutionApi::fake();

            expect(EvolutionApi::isFaked())->toBeTrue();
        });

        it('returns false after clearFake() is called', function () {
            EvolutionApi::fake();
            EvolutionApi::clearFake();

            expect(EvolutionApi::isFaked())->toBeFalse();
        });
    });

    describe('clearFake()', function () {
        it('resets the fake instance to null', function () {
            EvolutionApi::fake();

            expect(EvolutionApi::isFaked())->toBeTrue();

            EvolutionApi::clearFake();

            expect(EvolutionApi::isFaked())->toBeFalse();
        });

        it('clears the resolved facade instance', function () {
            EvolutionApi::fake();
            
            expect(EvolutionApi::isFaked())->toBeTrue();
            
            EvolutionApi::clearFake();

            // After clearing, the fake should be reset
            expect(EvolutionApi::isFaked())->toBeFalse();
        });

        it('can be called safely when not faked', function () {
            // Should not throw
            EvolutionApi::clearFake();

            expect(EvolutionApi::isFaked())->toBeFalse();
        });
    });

    describe('assertMessageSent()', function () {
        it('passes when message was sent to number', function () {
            EvolutionApi::fake();
            EvolutionApi::sendText('instance', '5511999999999', 'Hello');

            // Should not throw
            EvolutionApi::assertMessageSent('5511999999999');
            expect(true)->toBeTrue();
        });

        it('throws RuntimeException when not faked', function () {
            expect(fn () => EvolutionApi::assertMessageSent('5511999999999'))
                ->toThrow(RuntimeException::class, 'EvolutionApi facade is not faked. Call EvolutionApi::fake() first.');
        });

        it('delegates to fake instance', function () {
            $fake = EvolutionApi::fake();
            $fake->sendText('instance', '5511999999999', 'Test message');

            EvolutionApi::assertMessageSent('5511999999999');
            expect(true)->toBeTrue();
        });
    });

    describe('assertMessageSentTimes()', function () {
        it('passes when correct number of messages sent', function () {
            EvolutionApi::fake();
            EvolutionApi::sendText('instance', '5511999999999', 'One');
            EvolutionApi::sendText('instance', '5511888888888', 'Two');
            EvolutionApi::sendMedia('instance', '5511777777777', []);

            EvolutionApi::assertMessageSentTimes(3);
            expect(true)->toBeTrue();
        });

        it('throws RuntimeException when not faked', function () {
            expect(fn () => EvolutionApi::assertMessageSentTimes(1))
                ->toThrow(RuntimeException::class, 'EvolutionApi facade is not faked. Call EvolutionApi::fake() first.');
        });

        it('passes with zero messages when none sent', function () {
            EvolutionApi::fake();

            EvolutionApi::assertMessageSentTimes(0);
            expect(true)->toBeTrue();
        });
    });

    describe('assertNothingSent()', function () {
        it('passes when no messages sent', function () {
            EvolutionApi::fake();

            EvolutionApi::assertNothingSent();
            expect(true)->toBeTrue();
        });

        it('throws RuntimeException when not faked', function () {
            expect(fn () => EvolutionApi::assertNothingSent())
                ->toThrow(RuntimeException::class, 'EvolutionApi facade is not faked. Call EvolutionApi::fake() first.');
        });

        it('passes after api calls that are not messages', function () {
            EvolutionApi::fake();
            EvolutionApi::createInstance(['instanceName' => 'test']);
            EvolutionApi::fetchInstances();

            // These are API calls, not messages
            EvolutionApi::assertNothingSent();
            expect(true)->toBeTrue();
        });
    });

    describe('assertMessageContains()', function () {
        it('passes when message contains text', function () {
            EvolutionApi::fake();
            EvolutionApi::sendText('instance', '5511999999999', 'Hello World from Laravel');

            EvolutionApi::assertMessageContains('World');
            expect(true)->toBeTrue();
        });

        it('throws RuntimeException when not faked', function () {
            expect(fn () => EvolutionApi::assertMessageContains('test'))
                ->toThrow(RuntimeException::class, 'EvolutionApi facade is not faked. Call EvolutionApi::fake() first.');
        });

        it('works with partial text match', function () {
            EvolutionApi::fake();
            EvolutionApi::sendText('instance', '5511999999999', 'Welcome to our service');

            EvolutionApi::assertMessageContains('our service');
            expect(true)->toBeTrue();
        });
    });

    describe('ensureFaked()', function () {
        it('throws RuntimeException with descriptive message when not faked', function () {
            expect(fn () => EvolutionApi::assertMessageSent('test'))
                ->toThrow(
                    RuntimeException::class,
                    'EvolutionApi facade is not faked. Call EvolutionApi::fake() first.'
                );
        });

        it('does not throw when faked', function () {
            EvolutionApi::fake();
            EvolutionApi::sendText('instance', '5511999999999', 'Test');

            // Should not throw
            EvolutionApi::assertMessageSent('5511999999999');
            expect(true)->toBeTrue();
        });
    });

    describe('facade proxy methods', function () {
        it('proxies connection() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::connection('my-connection');

            // The fake returns itself for chaining
            expect($result)->toBeInstanceOf(EvolutionApiFake::class);
        });

        it('proxies instance() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::instance('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies message() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::message('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies chat() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::chat('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies group() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::group('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies profile() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::profile('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies webhook() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::webhook('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies settings() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::settings('my-instance');

            expect($result)->not->toBeNull();
        });

        it('proxies createInstance() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::createInstance(['instanceName' => 'new-instance']);

            expect($result)->toBeArray();
            expect($result)->toHaveKey('instance');
        });

        it('proxies fetchInstances() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::fetchInstances();

            expect($result)->toBeArray();
        });

        it('proxies getQrCode() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::getQrCode('my-instance');

            expect($result)->toHaveKey('base64');
            expect($result)->toHaveKey('code');
        });

        it('proxies connectionState() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::connectionState('my-instance');

            expect($result)->toHaveKey('instance');
        });

        it('proxies sendText() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendText('instance', '5511999999999', 'Hello');

            expect($result)->toHaveKey('key');
        });

        it('proxies sendMedia() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendMedia('instance', '5511999999999', [
                'mediatype' => 'image',
                'media' => 'https://example.com/image.jpg',
            ]);

            expect($result)->toHaveKey('key');
        });

        it('proxies sendAudio() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendAudio('instance', '5511999999999', 'https://example.com/audio.mp3');

            expect($result)->toHaveKey('key');
        });

        it('proxies sendLocation() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendLocation('instance', '5511999999999', -23.5505, -46.6333);

            expect($result)->toHaveKey('key');
        });

        it('proxies sendContact() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendContact('instance', '5511999999999', [
                'fullName' => 'John Doe',
                'phoneNumber' => '+5511888888888',
            ]);

            expect($result)->toHaveKey('key');
        });

        it('proxies sendReaction() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendReaction('instance', 'MSG123', '👍');

            expect($result)->toBe(['status' => 'success']);
        });

        it('proxies sendPoll() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendPoll('instance', '5511999999999', 'Question?', ['A', 'B', 'C']);

            expect($result)->toHaveKey('key');
        });

        it('proxies sendList() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::sendList('instance', '5511999999999', [
                'title' => 'Menu',
                'description' => 'Choose an option',
            ]);

            expect($result)->toHaveKey('key');
        });

        it('proxies isWhatsApp() to service', function () {
            EvolutionApi::fake();

            $result = EvolutionApi::isWhatsApp('instance', '5511999999999');

            expect($result)->toBeTrue();
        });
    });

    describe('integration with fake assertions', function () {
        it('can verify message flow with assertions', function () {
            EvolutionApi::fake();

            // Simulate sending messages
            EvolutionApi::sendText('instance', '5511999999999', 'Hello');
            EvolutionApi::sendText('instance', '5511888888888', 'World');

            // Verify with assertions
            EvolutionApi::assertMessageSent('5511999999999');
            EvolutionApi::assertMessageSent('5511888888888');
            EvolutionApi::assertMessageSentTimes(2);
            EvolutionApi::assertMessageContains('Hello');
            EvolutionApi::assertMessageContains('World');

            expect(true)->toBeTrue();
        });

        it('can use stubbed responses for testing', function () {
            EvolutionApi::fake([
                'sendText' => ['key' => ['id' => 'CUSTOM_MSG_123']],
                'isWhatsApp' => ['exists' => false],
            ]);

            $sendResult = EvolutionApi::sendText('instance', '5511999999999', 'Test');
            $checkResult = EvolutionApi::isWhatsApp('instance', '5511999999999');

            expect($sendResult['key']['id'])->toBe('CUSTOM_MSG_123');
            expect($checkResult)->toBeFalse();
        });
    });

});
