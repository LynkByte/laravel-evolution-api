<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Testing\Fakes\EvolutionApiFake;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeInstanceResource;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeMessageResource;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeChatResource;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeGroupResource;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeProfileResource;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeWebhookResource;
use Lynkbyte\EvolutionApi\Testing\Fakes\FakeSettingsResource;

describe('EvolutionApiFake', function () {

    beforeEach(function () {
        $this->fake = new EvolutionApiFake();
    });

    describe('constructor', function () {
        it('creates instance with default responses', function () {
            $fake = new EvolutionApiFake();

            expect($fake)->toBeInstanceOf(EvolutionApiFake::class);
            expect($fake->getSentMessages())->toBe([]);
            expect($fake->getApiCalls())->toBe([]);
        });

        it('accepts custom stubbed responses', function () {
            $fake = new EvolutionApiFake([
                'sendText' => ['custom' => 'response'],
            ]);

            $result = $fake->sendText('instance', '5511999999999', 'Hello');

            expect($result)->toBe(['custom' => 'response']);
        });
    });

    describe('connection', function () {
        it('sets current instance for chained calls', function () {
            $result = $this->fake->connection('my-instance');

            expect($result)->toBe($this->fake);
        });
    });

    describe('sendText', function () {
        it('records text message', function () {
            $this->fake->sendText('test-instance', '5511999999999', 'Hello World');

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('text');
            expect($messages[0]['instance'])->toBe('test-instance');
            expect($messages[0]['number'])->toBe('5511999999999');
            expect($messages[0]['data']['text'])->toBe('Hello World');
        });

        it('records message options', function () {
            $this->fake->sendText('test-instance', '5511999999999', 'Hello', [
                'delay' => 1000,
                'quoted' => 'message-id',
            ]);

            $messages = $this->fake->getSentMessages();

            expect($messages[0]['data']['options'])->toBe([
                'delay' => 1000,
                'quoted' => 'message-id',
            ]);
        });

        it('returns default response', function () {
            $result = $this->fake->sendText('instance', '5511999999999', 'Test');

            expect($result)->toHaveKey('key');
            expect($result['key'])->toHaveKey('remoteJid');
            expect($result['key'])->toHaveKey('fromMe');
            expect($result['key'])->toHaveKey('id');
            expect($result['message'])->toHaveKey('conversation');
        });

        it('formats JID correctly', function () {
            $result = $this->fake->sendText('instance', '+55 11 99999-9999', 'Test');

            expect($result['key']['remoteJid'])->toBe('5511999999999@s.whatsapp.net');
        });
    });

    describe('sendMedia', function () {
        it('records media message', function () {
            $media = [
                'mediatype' => 'image',
                'media' => 'https://example.com/image.jpg',
                'caption' => 'My photo',
            ];

            $this->fake->sendMedia('test-instance', '5511999999999', $media);

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('media');
            expect($messages[0]['data']['media'])->toBe($media);
        });

        it('returns default media response', function () {
            $result = $this->fake->sendMedia('instance', '5511999999999', []);

            expect($result)->toHaveKey('key');
            expect($result)->toHaveKey('messageTimestamp');
        });
    });

    describe('sendAudio', function () {
        it('records audio message', function () {
            $this->fake->sendAudio('test-instance', '5511999999999', 'https://example.com/audio.mp3', [
                'encoding' => 'opus',
            ]);

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('audio');
            expect($messages[0]['data']['audio'])->toBe('https://example.com/audio.mp3');
            expect($messages[0]['data']['options'])->toBe(['encoding' => 'opus']);
        });
    });

    describe('sendLocation', function () {
        it('records location message', function () {
            $this->fake->sendLocation('test-instance', '5511999999999', -23.5505, -46.6333, [
                'name' => 'São Paulo',
            ]);

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('location');
            expect($messages[0]['data']['latitude'])->toBe(-23.5505);
            expect($messages[0]['data']['longitude'])->toBe(-46.6333);
            expect($messages[0]['data']['options']['name'])->toBe('São Paulo');
        });
    });

    describe('sendContact', function () {
        it('records contact message', function () {
            $contact = [
                'fullName' => 'John Doe',
                'wuid' => '5511999999999',
                'phoneNumber' => '+55 11 99999-9999',
            ];

            $this->fake->sendContact('test-instance', '5511888888888', $contact);

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('contact');
            expect($messages[0]['data']['contact'])->toBe($contact);
        });
    });

    describe('sendReaction', function () {
        it('records reaction as api call', function () {
            $this->fake->sendReaction('test-instance', 'MSG123', '👍');

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls)->toHaveCount(1);
            expect($apiCalls[0]['operation'])->toBe('sendReaction');
            expect($apiCalls[0]['data']['instance'])->toBe('test-instance');
            expect($apiCalls[0]['data']['message_id'])->toBe('MSG123');
            expect($apiCalls[0]['data']['reaction'])->toBe('👍');
        });

        it('returns success response', function () {
            $result = $this->fake->sendReaction('instance', 'MSG123', '❤️');

            expect($result)->toBe(['status' => 'success']);
        });
    });

    describe('sendPoll', function () {
        it('records poll message', function () {
            $this->fake->sendPoll(
                'test-instance',
                '5511999999999',
                'What is your favorite color?',
                ['Red', 'Blue', 'Green'],
                ['selectableCount' => 1]
            );

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('poll');
            expect($messages[0]['data']['name'])->toBe('What is your favorite color?');
            expect($messages[0]['data']['values'])->toBe(['Red', 'Blue', 'Green']);
            expect($messages[0]['data']['options'])->toBe(['selectableCount' => 1]);
        });
    });

    describe('sendList', function () {
        it('records list message', function () {
            $list = [
                'title' => 'Menu',
                'description' => 'Choose an option',
                'sections' => [
                    ['title' => 'Options', 'rows' => [['title' => 'Option 1']]],
                ],
            ];

            $this->fake->sendList('test-instance', '5511999999999', $list);

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]['type'])->toBe('list');
            expect($messages[0]['data']['list'])->toBe($list);
        });
    });

    describe('createInstance', function () {
        it('records api call', function () {
            $data = ['instanceName' => 'new-instance', 'token' => 'secret'];

            $this->fake->createInstance($data);

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls)->toHaveCount(1);
            expect($apiCalls[0]['operation'])->toBe('createInstance');
            expect($apiCalls[0]['data'])->toBe($data);
        });

        it('returns default response', function () {
            $result = $this->fake->createInstance(['instanceName' => 'test']);

            expect($result)->toHaveKey('instance');
            expect($result['instance'])->toHaveKey('instanceName');
            expect($result['instance'])->toHaveKey('status');
        });
    });

    describe('fetchInstances', function () {
        it('records api call without filter', function () {
            $this->fake->fetchInstances();

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls)->toHaveCount(1);
            expect($apiCalls[0]['operation'])->toBe('fetchInstances');
            expect($apiCalls[0]['data']['instance_name'])->toBeNull();
        });

        it('records api call with filter', function () {
            $this->fake->fetchInstances('specific-instance');

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls[0]['data']['instance_name'])->toBe('specific-instance');
        });

        it('returns default response', function () {
            $result = $this->fake->fetchInstances();

            expect($result)->toBeArray();
            expect($result[0]['instance'])->toHaveKey('instanceName');
        });
    });

    describe('getQrCode', function () {
        it('records api call', function () {
            $this->fake->getQrCode('test-instance');

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls)->toHaveCount(1);
            expect($apiCalls[0]['operation'])->toBe('getQrCode');
            expect($apiCalls[0]['data']['instance'])->toBe('test-instance');
        });

        it('returns default qr code response', function () {
            $result = $this->fake->getQrCode('instance');

            expect($result)->toHaveKey('base64');
            expect($result)->toHaveKey('code');
            expect($result['base64'])->toContain('data:image/png;base64,');
        });
    });

    describe('connectionState', function () {
        it('records api call', function () {
            $this->fake->connectionState('test-instance');

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls)->toHaveCount(1);
            expect($apiCalls[0]['operation'])->toBe('connectionState');
            expect($apiCalls[0]['data']['instance'])->toBe('test-instance');
        });

        it('returns default connection state', function () {
            $result = $this->fake->connectionState('instance');

            expect($result)->toHaveKey('instance');
            expect($result['instance']['state'])->toBe('open');
        });
    });

    describe('isWhatsApp', function () {
        it('records api call', function () {
            $this->fake->isWhatsApp('test-instance', '5511999999999');

            $apiCalls = $this->fake->getApiCalls();

            expect($apiCalls)->toHaveCount(1);
            expect($apiCalls[0]['operation'])->toBe('isWhatsApp');
            expect($apiCalls[0]['data']['number'])->toBe('5511999999999');
        });

        it('returns true by default', function () {
            $result = $this->fake->isWhatsApp('instance', '5511999999999');

            expect($result)->toBeTrue();
        });

        it('can be stubbed to return false', function () {
            $fake = new EvolutionApiFake([
                'isWhatsApp' => ['exists' => false],
            ]);

            $result = $fake->isWhatsApp('instance', '5511999999999');

            expect($result)->toBeFalse();
        });
    });

    describe('stubResponse', function () {
        it('stubs specific operation response', function () {
            $this->fake->stubResponse('sendText', ['stubbed' => true]);

            $result = $this->fake->sendText('instance', '5511999999999', 'Test');

            expect($result)->toBe(['stubbed' => true]);
        });

        it('allows chaining', function () {
            $result = $this->fake
                ->stubResponse('sendText', ['a' => 1])
                ->stubResponse('sendMedia', ['b' => 2]);

            expect($result)->toBe($this->fake);
        });

        it('accepts callable for dynamic responses', function () {
            $this->fake->stubResponse('sendText', function () {
                return ['dynamic' => uniqid()];
            });

            $result1 = $this->fake->sendText('instance', '5511999999999', 'Test');
            $result2 = $this->fake->sendText('instance', '5511999999999', 'Test');

            expect($result1['dynamic'])->not->toBe($result2['dynamic']);
        });
    });

    describe('stubUsing', function () {
        it('uses callback for all responses', function () {
            $this->fake->stubUsing(function ($operation) {
                return ['operation' => $operation];
            });

            $sendTextResult = $this->fake->sendText('instance', '5511999999999', 'Test');
            $createInstanceResult = $this->fake->createInstance([]);

            expect($sendTextResult)->toBe(['operation' => 'sendText']);
            expect($createInstanceResult)->toBe(['operation' => 'createInstance']);
        });

        it('allows chaining', function () {
            $result = $this->fake->stubUsing(fn($op) => ['op' => $op]);

            expect($result)->toBe($this->fake);
        });
    });

    describe('assertMessageSent', function () {
        it('passes when message was sent to number', function () {
            $this->fake->sendText('instance', '5511999999999', 'Hello');

            // Should not throw
            $this->fake->assertMessageSent('5511999999999');
            expect(true)->toBeTrue();
        });

        it('passes with partial number match', function () {
            $this->fake->sendText('instance', '5511999999999', 'Hello');

            // Search for partial match (last digits)
            $this->fake->assertMessageSent('999999999');
            expect(true)->toBeTrue();
        });

        it('executes callback for additional assertions', function () {
            $this->fake->sendText('instance', '5511999999999', 'Hello World');

            $called = false;
            $this->fake->assertMessageSent('5511999999999', function ($message) use (&$called) {
                $called = true;
                expect($message['data']['text'])->toBe('Hello World');
            });

            expect($called)->toBeTrue();
        });
    });

    describe('assertMessageNotSent', function () {
        it('passes when no message was sent to number', function () {
            $this->fake->sendText('instance', '5511999999999', 'Hello');

            $this->fake->assertMessageNotSent('5511888888888');
            expect(true)->toBeTrue();
        });
    });

    describe('assertMessageSentTimes', function () {
        it('passes when correct number of messages sent', function () {
            $this->fake->sendText('instance', '5511999999999', 'One');
            $this->fake->sendText('instance', '5511888888888', 'Two');
            $this->fake->sendMedia('instance', '5511777777777', []);

            $this->fake->assertMessageSentTimes(3);
            expect(true)->toBeTrue();
        });
    });

    describe('assertNothingSent', function () {
        it('passes when no messages sent', function () {
            $this->fake->assertNothingSent();
            expect(true)->toBeTrue();
        });

        it('passes after clear is called', function () {
            $this->fake->sendText('instance', '5511999999999', 'Hello');
            $this->fake->clear();

            $this->fake->assertNothingSent();
            expect(true)->toBeTrue();
        });
    });

    describe('assertMessageContains', function () {
        it('passes when message contains text', function () {
            $this->fake->sendText('instance', '5511999999999', 'Hello World from Laravel');

            $this->fake->assertMessageContains('World');
            expect(true)->toBeTrue();
        });
    });

    describe('assertMessageTypeWas', function () {
        it('passes when message type was sent', function () {
            $this->fake->sendMedia('instance', '5511999999999', []);

            $this->fake->assertMessageTypeWas('media');
            expect(true)->toBeTrue();
        });

        it('works with various message types', function () {
            $this->fake->sendText('instance', '5511999999999', 'Test');
            $this->fake->sendAudio('instance', '5511999999999', 'audio.mp3');
            $this->fake->sendLocation('instance', '5511999999999', 0.0, 0.0);

            $this->fake->assertMessageTypeWas('text');
            $this->fake->assertMessageTypeWas('audio');
            $this->fake->assertMessageTypeWas('location');
            expect(true)->toBeTrue();
        });
    });

    describe('assertApiCalled', function () {
        it('passes when api was called', function () {
            $this->fake->createInstance(['instanceName' => 'test']);

            $this->fake->assertApiCalled('createInstance');
            expect(true)->toBeTrue();
        });

        it('executes callback for additional assertions', function () {
            $this->fake->createInstance(['instanceName' => 'my-instance']);

            $called = false;
            $this->fake->assertApiCalled('createInstance', function ($call) use (&$called) {
                $called = true;
                expect($call['data']['instanceName'])->toBe('my-instance');
            });

            expect($called)->toBeTrue();
        });
    });

    describe('assertApiNotCalled', function () {
        it('passes when api was not called', function () {
            $this->fake->createInstance([]);

            $this->fake->assertApiNotCalled('fetchInstances');
            expect(true)->toBeTrue();
        });
    });

    describe('getSentMessages', function () {
        it('returns all sent messages', function () {
            $this->fake->sendText('instance', '5511999999999', 'One');
            $this->fake->sendText('instance', '5511888888888', 'Two');

            $messages = $this->fake->getSentMessages();

            expect($messages)->toHaveCount(2);
        });
    });

    describe('getApiCalls', function () {
        it('returns all api calls', function () {
            $this->fake->createInstance([]);
            $this->fake->fetchInstances();
            $this->fake->connectionState('test');

            $calls = $this->fake->getApiCalls();

            expect($calls)->toHaveCount(3);
        });
    });

    describe('getLastMessage', function () {
        it('returns null when no messages', function () {
            expect($this->fake->getLastMessage())->toBeNull();
        });

        it('returns last sent message', function () {
            $this->fake->sendText('instance', '5511999999999', 'First');
            $this->fake->sendText('instance', '5511888888888', 'Last');

            $last = $this->fake->getLastMessage();

            expect($last['data']['text'])->toBe('Last');
        });
    });

    describe('getLastApiCall', function () {
        it('returns null when no api calls', function () {
            expect($this->fake->getLastApiCall())->toBeNull();
        });

        it('returns last api call', function () {
            $this->fake->createInstance([]);
            $this->fake->fetchInstances();

            $last = $this->fake->getLastApiCall();

            expect($last['operation'])->toBe('fetchInstances');
        });
    });

    describe('clear', function () {
        it('clears all recorded interactions', function () {
            $this->fake->sendText('instance', '5511999999999', 'Test');
            $this->fake->createInstance([]);

            $this->fake->clear();

            expect($this->fake->getSentMessages())->toBe([]);
            expect($this->fake->getApiCalls())->toBe([]);
        });

        it('allows chaining', function () {
            $result = $this->fake->clear();

            expect($result)->toBe($this->fake);
        });
    });

    describe('disableRecording', function () {
        it('stops recording messages', function () {
            $this->fake->disableRecording();
            $this->fake->sendText('instance', '5511999999999', 'Test');

            expect($this->fake->getSentMessages())->toBe([]);
        });

        it('stops recording api calls', function () {
            $this->fake->disableRecording();
            $this->fake->createInstance([]);

            expect($this->fake->getApiCalls())->toBe([]);
        });

        it('allows chaining', function () {
            $result = $this->fake->disableRecording();

            expect($result)->toBe($this->fake);
        });
    });

    describe('enableRecording', function () {
        it('resumes recording', function () {
            $this->fake->disableRecording();
            $this->fake->sendText('instance', '5511999999999', 'Not recorded');
            $this->fake->enableRecording();
            $this->fake->sendText('instance', '5511999999999', 'Recorded');

            expect($this->fake->getSentMessages())->toHaveCount(1);
            expect($this->fake->getSentMessages()[0]['data']['text'])->toBe('Recorded');
        });

        it('allows chaining', function () {
            $result = $this->fake->enableRecording();

            expect($result)->toBe($this->fake);
        });
    });

    describe('getMessageCountByType', function () {
        it('returns empty array when no messages', function () {
            expect($this->fake->getMessageCountByType())->toBe([]);
        });

        it('counts messages by type', function () {
            $this->fake->sendText('instance', '5511999999999', 'Test');
            $this->fake->sendText('instance', '5511888888888', 'Test');
            $this->fake->sendMedia('instance', '5511777777777', []);
            $this->fake->sendAudio('instance', '5511666666666', 'audio.mp3');
            $this->fake->sendLocation('instance', '5511555555555', 0.0, 0.0);

            $counts = $this->fake->getMessageCountByType();

            expect($counts)->toBe([
                'text' => 2,
                'media' => 1,
                'audio' => 1,
                'location' => 1,
            ]);
        });
    });

    describe('Fake Resources', function () {

        describe('instance()', function () {
            it('returns FakeInstanceResource', function () {
                $resource = $this->fake->instance('test-instance');

                expect($resource)->toBeInstanceOf(FakeInstanceResource::class);
            });

            it('uses current instance when not specified', function () {
                $this->fake->connection('my-instance');
                $resource = $this->fake->instance();

                expect($resource)->toBeInstanceOf(FakeInstanceResource::class);
            });
        });

        describe('message()', function () {
            it('returns FakeMessageResource', function () {
                $resource = $this->fake->message('test-instance');

                expect($resource)->toBeInstanceOf(FakeMessageResource::class);
            });
        });

        describe('chat()', function () {
            it('returns FakeChatResource', function () {
                $resource = $this->fake->chat('test-instance');

                expect($resource)->toBeInstanceOf(FakeChatResource::class);
            });
        });

        describe('group()', function () {
            it('returns FakeGroupResource', function () {
                $resource = $this->fake->group('test-instance');

                expect($resource)->toBeInstanceOf(FakeGroupResource::class);
            });
        });

        describe('profile()', function () {
            it('returns FakeProfileResource', function () {
                $resource = $this->fake->profile('test-instance');

                expect($resource)->toBeInstanceOf(FakeProfileResource::class);
            });
        });

        describe('webhook()', function () {
            it('returns FakeWebhookResource', function () {
                $resource = $this->fake->webhook('test-instance');

                expect($resource)->toBeInstanceOf(FakeWebhookResource::class);
            });
        });

        describe('settings()', function () {
            it('returns FakeSettingsResource', function () {
                $resource = $this->fake->settings('test-instance');

                expect($resource)->toBeInstanceOf(FakeSettingsResource::class);
            });
        });
    });

    describe('FakeInstanceResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->instance('test-instance');
        });

        it('can create instance', function () {
            $result = $this->resource->create(['instanceName' => 'new']);

            expect($result)->toHaveKey('instance');
            $this->fake->assertApiCalled('createInstance');
        });

        it('can fetch all instances', function () {
            $result = $this->resource->fetchAll();

            expect($result)->toBeArray();
            $this->fake->assertApiCalled('fetchInstances');
        });

        it('can fetch specific instance', function () {
            $result = $this->resource->fetch();

            expect($result)->toBeArray();
            $this->fake->assertApiCalled('fetchInstances');
        });

        it('can get QR code', function () {
            $result = $this->resource->getQrCode();

            expect($result)->toHaveKey('base64');
            expect($result)->toHaveKey('code');
            $this->fake->assertApiCalled('getQrCode');
        });

        it('can get connection state', function () {
            $result = $this->resource->connectionState();

            expect($result)->toHaveKey('instance');
            $this->fake->assertApiCalled('connectionState');
        });
    });

    describe('FakeMessageResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->message('test-instance');
        });

        it('can send text', function () {
            $this->resource->sendText('5511999999999', 'Hello');

            $this->fake->assertMessageSent('5511999999999');
        });

        it('can send media', function () {
            $this->resource->sendMedia('5511999999999', ['mediatype' => 'image']);

            $this->fake->assertMessageTypeWas('media');
        });

        it('can send audio', function () {
            $this->resource->sendAudio('5511999999999', 'audio.mp3');

            $this->fake->assertMessageTypeWas('audio');
        });

        it('can send location', function () {
            $this->resource->sendLocation('5511999999999', -23.0, -46.0);

            $this->fake->assertMessageTypeWas('location');
        });

        it('can send contact', function () {
            $this->resource->sendContact('5511999999999', ['fullName' => 'John']);

            $this->fake->assertMessageTypeWas('contact');
        });

        it('can send poll', function () {
            $this->resource->sendPoll('5511999999999', 'Question?', ['A', 'B']);

            $this->fake->assertMessageTypeWas('poll');
        });

        it('can send list', function () {
            $this->resource->sendList('5511999999999', ['title' => 'Menu']);

            $this->fake->assertMessageTypeWas('list');
        });
    });

    describe('FakeChatResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->chat('test-instance');
        });

        it('can check isWhatsApp', function () {
            $result = $this->resource->isWhatsApp('5511999999999');

            expect($result)->toBeTrue();
            $this->fake->assertApiCalled('isWhatsApp');
        });

        it('can find chats', function () {
            $result = $this->resource->findChats();

            expect($result)->toBeArray();
        });
    });

    describe('FakeGroupResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->group('test-instance');
        });

        it('can fetch all groups', function () {
            $result = $this->resource->fetchAll();

            expect($result)->toBeArray();
        });

        it('can create group', function () {
            $result = $this->resource->create('Group Name', ['5511999999999']);

            expect($result)->toHaveKey('id');
        });
    });

    describe('FakeProfileResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->profile('test-instance');
        });

        it('can fetch profile', function () {
            $result = $this->resource->fetchProfile();

            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('status');
        });

        it('can update name', function () {
            $result = $this->resource->updateName('New Name');

            expect($result)->toBe(['status' => 'success']);
        });

        it('can update status', function () {
            $result = $this->resource->updateStatus('New Status');

            expect($result)->toBe(['status' => 'success']);
        });
    });

    describe('FakeWebhookResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->webhook('test-instance');
        });

        it('can set webhook', function () {
            $result = $this->resource->set([
                'url' => 'https://example.com/webhook',
                'events' => ['MESSAGES_UPSERT'],
            ]);

            expect($result)->toBe(['status' => 'success']);
        });

        it('can get webhook config', function () {
            $result = $this->resource->get();

            expect($result)->toHaveKey('url');
            expect($result)->toHaveKey('events');
        });
    });

    describe('FakeSettingsResource', function () {
        beforeEach(function () {
            $this->resource = $this->fake->settings('test-instance');
        });

        it('can get settings', function () {
            $result = $this->resource->get();

            expect($result)->toHaveKey('reject_call');
            expect($result)->toHaveKey('msg_call');
            expect($result)->toHaveKey('groups_ignore');
        });

        it('can set settings', function () {
            $result = $this->resource->set(['reject_call' => true]);

            expect($result)->toBe(['status' => 'success']);
        });
    });

    describe('timestamp recording', function () {
        it('records timestamp for messages', function () {
            $before = microtime(true);
            $this->fake->sendText('instance', '5511999999999', 'Test');
            $after = microtime(true);

            $message = $this->fake->getLastMessage();

            expect($message['timestamp'])->toBeGreaterThanOrEqual($before);
            expect($message['timestamp'])->toBeLessThanOrEqual($after);
        });

        it('records timestamp for api calls', function () {
            $before = microtime(true);
            $this->fake->createInstance([]);
            $after = microtime(true);

            $call = $this->fake->getLastApiCall();

            expect($call['timestamp'])->toBeGreaterThanOrEqual($before);
            expect($call['timestamp'])->toBeLessThanOrEqual($after);
        });
    });

});
