<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Client\ConnectionManager;
use Lynkbyte\EvolutionApi\Client\EvolutionClient;
use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\DTOs\Message\SendTextMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendAudioMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendLocationMessageDto;
use Lynkbyte\EvolutionApi\DTOs\Message\SendPollMessageDto;
use Lynkbyte\EvolutionApi\Resources\Message;

describe('Message Resource', function () {

    beforeEach(function () {
        Http::preventStrayRequests();

        $config = [
            'connections' => [
                'default' => [
                    'server_url' => 'https://api.evolution.test',
                    'api_key' => 'test-api-key',
                ],
            ],
            'retry' => ['enabled' => false],
        ];

        $this->connectionManager = new ConnectionManager($config);
        $this->client = new EvolutionClient($this->connectionManager);
        $this->client->instance('test-instance');
        $this->resource = new Message($this->client);
    });

    describe('sendText', function () {
        it('sends text message with DTO', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'key' => ['id' => 'MSG123', 'remoteJid' => '5511999999999@s.whatsapp.net'],
                ], 200),
            ]);

            $dto = new SendTextMessageDto(
                number: '5511999999999',
                text: 'Hello World'
            );

            $response = $this->resource->sendText($dto);

            expect($response)->toBeInstanceOf(ApiResponse::class);
            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendText/test-instance') &&
                    $request['number'] === '5511999999999' &&
                    $request['text'] === 'Hello World';
            });
        });

        it('sends text message with array', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->sendText([
                'number' => '5511999999999',
                'text' => 'Hello',
            ]);

            Http::assertSent(function (Request $request) {
                return $request['number'] === '5511999999999' &&
                    $request['text'] === 'Hello';
            });
        });

        it('sends text with delay', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $dto = new SendTextMessageDto(
                number: '5511999999999',
                text: 'Delayed message',
                delay: 1000
            );

            $this->resource->sendText($dto);

            Http::assertSent(function (Request $request) {
                return $request['delay'] === 1000;
            });
        });
    });

    describe('text helper', function () {
        it('sends text using simple helper', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->text('5511999999999', 'Quick message', 500);

            Http::assertSent(function (Request $request) {
                return $request['number'] === '5511999999999' &&
                    $request['text'] === 'Quick message' &&
                    $request['delay'] === 500;
            });
        });
    });

    describe('sendMedia', function () {
        it('sends media message with DTO', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => ['id' => 'MEDIA123']], 200),
            ]);

            $dto = new SendMediaMessageDto(
                number: '5511999999999',
                mediatype: 'image',
                media: 'https://example.com/image.jpg',
                caption: 'My photo'
            );

            $response = $this->resource->sendMedia($dto);

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendMedia/test-instance') &&
                    $request['mediatype'] === 'image' &&
                    $request['media'] === 'https://example.com/image.jpg' &&
                    $request['caption'] === 'My photo';
            });
        });

        it('sends media with array', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->sendMedia([
                'number' => '5511999999999',
                'mediatype' => 'video',
                'media' => 'https://example.com/video.mp4',
            ]);

            Http::assertSent(function (Request $request) {
                return $request['mediatype'] === 'video';
            });
        });
    });

    describe('image helper', function () {
        it('sends image', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->image(
                '5511999999999',
                'https://example.com/photo.jpg',
                'My vacation photo',
                'photo.jpg'
            );

            Http::assertSent(function (Request $request) {
                return $request['mediatype'] === 'image' &&
                    $request['caption'] === 'My vacation photo' &&
                    $request['fileName'] === 'photo.jpg';
            });
        });
    });

    describe('video helper', function () {
        it('sends video', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->video(
                '5511999999999',
                'https://example.com/video.mp4',
                'Check this out!'
            );

            Http::assertSent(function (Request $request) {
                return $request['mediatype'] === 'video' &&
                    $request['caption'] === 'Check this out!';
            });
        });
    });

    describe('document helper', function () {
        it('sends document', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->document(
                '5511999999999',
                'https://example.com/doc.pdf',
                'Important document',
                'contract.pdf',
                'application/pdf'
            );

            Http::assertSent(function (Request $request) {
                return $request['mediatype'] === 'document' &&
                    $request['fileName'] === 'contract.pdf' &&
                    $request['mimetype'] === 'application/pdf';
            });
        });
    });

    describe('sendAudio', function () {
        it('sends audio message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $dto = new SendAudioMessageDto(
                number: '5511999999999',
                audio: 'https://example.com/audio.mp3'
            );

            $this->resource->sendAudio($dto);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendWhatsAppAudio/test-instance') &&
                    $request['audio'] === 'https://example.com/audio.mp3';
            });
        });
    });

    describe('audio helper', function () {
        it('sends audio using helper', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->audio('5511999999999', 'https://example.com/voice.ogg', 500);

            Http::assertSent(function (Request $request) {
                return $request['number'] === '5511999999999' &&
                    $request['audio'] === 'https://example.com/voice.ogg' &&
                    $request['delay'] === 500;
            });
        });
    });

    describe('sendLocation', function () {
        it('sends location message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $dto = new SendLocationMessageDto(
                number: '5511999999999',
                latitude: -23.5505,
                longitude: -46.6333,
                name: 'São Paulo',
                address: 'Centro, São Paulo'
            );

            $this->resource->sendLocation($dto);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendLocation/test-instance') &&
                    $request['latitude'] === -23.5505 &&
                    $request['longitude'] === -46.6333;
            });
        });
    });

    describe('location helper', function () {
        it('sends location using helper', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->location(
                '5511999999999',
                -23.5505,
                -46.6333,
                'My Location',
                '123 Main St'
            );

            Http::assertSent(function (Request $request) {
                return $request['name'] === 'My Location' &&
                    $request['address'] === '123 Main St';
            });
        });
    });

    describe('sendPoll', function () {
        it('sends poll message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $dto = new SendPollMessageDto(
                number: '5511999999999',
                name: 'What is your favorite color?',
                values: ['Red', 'Blue', 'Green'],
                selectableCount: 1
            );

            $this->resource->sendPoll($dto);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendPoll/test-instance') &&
                    $request['name'] === 'What is your favorite color?' &&
                    $request['values'] === ['Red', 'Blue', 'Green'];
            });
        });
    });

    describe('poll helper', function () {
        it('sends poll using helper', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->poll(
                '5511999999999',
                'Do you like pizza?',
                ['Yes', 'No', 'Maybe'],
                2
            );

            Http::assertSent(function (Request $request) {
                return $request['name'] === 'Do you like pizza?' &&
                    $request['selectableCount'] === 2;
            });
        });
    });

    describe('sendReaction', function () {
        it('sends reaction to message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->react(
                '5511999999999@s.whatsapp.net',
                'MSG123',
                '👍',
                false
            );

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendReaction/test-instance') &&
                    $request['key']['id'] === 'MSG123' &&
                    $request['reaction'] === '👍';
            });
        });
    });

    describe('unreact', function () {
        it('removes reaction', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unreact('5511999999999@s.whatsapp.net', 'MSG123');

            Http::assertSent(function (Request $request) {
                return $request['reaction'] === '';
            });
        });
    });

    describe('sendSticker', function () {
        it('sends sticker using helper', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->sticker('5511999999999', 'https://example.com/sticker.webp');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendSticker/test-instance') &&
                    $request['sticker'] === 'https://example.com/sticker.webp';
            });
        });
    });

    describe('markAsRead', function () {
        it('marks message as read', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->markAsRead('5511999999999@s.whatsapp.net', 'MSG123');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/readMessage/test-instance') &&
                    $request['readMessages'][0]['id'] === 'MSG123';
            });
        });
    });

    describe('markMultipleAsRead', function () {
        it('marks multiple messages as read', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->markMultipleAsRead([
                ['remoteJid' => '5511999999999', 'id' => 'MSG1'],
                ['remoteJid' => '5511888888888', 'id' => 'MSG2'],
            ]);

            Http::assertSent(function (Request $request) {
                return count($request['readMessages']) === 2 &&
                    $request['readMessages'][0]['id'] === 'MSG1' &&
                    $request['readMessages'][1]['id'] === 'MSG2';
            });
        });
    });

    describe('archiveChat', function () {
        it('archives chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->archiveChat('5511999999999@s.whatsapp.net', true);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/archiveChat/test-instance') &&
                    $request['archive'] === true;
            });
        });
    });

    describe('unarchiveChat', function () {
        it('unarchives chat', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unarchiveChat('5511999999999@s.whatsapp.net');

            Http::assertSent(function (Request $request) {
                return $request['archive'] === false;
            });
        });
    });

    describe('deleteMessage', function () {
        it('deletes message for everyone', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'deleted'], 200),
            ]);

            $this->resource->deleteMessage('5511999999999@s.whatsapp.net', 'MSG123', false);

            Http::assertSent(function (Request $request) {
                return $request->method() === 'DELETE' &&
                    str_contains($request->url(), 'message/delete/test-instance') &&
                    $request['onlyMe'] === false;
            });
        });
    });

    describe('deleteForMe', function () {
        it('deletes message only for myself', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'deleted'], 200),
            ]);

            $this->resource->deleteForMe('5511999999999@s.whatsapp.net', 'MSG123');

            Http::assertSent(function (Request $request) {
                return $request['onlyMe'] === true;
            });
        });
    });

    describe('updateMessage', function () {
        it('updates message text', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'updated'], 200),
            ]);

            $this->resource->updateMessage(
                '5511999999999@s.whatsapp.net',
                'MSG123',
                'Updated text'
            );

            Http::assertSent(function (Request $request) {
                return $request->method() === 'PUT' &&
                    str_contains($request->url(), 'message/update/test-instance') &&
                    $request['text'] === 'Updated text';
            });
        });
    });

    describe('starMessage', function () {
        it('stars a message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->starMessage('5511999999999@s.whatsapp.net', 'MSG123', true);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/star/test-instance') &&
                    $request['star'] === true;
            });
        });
    });

    describe('unstarMessage', function () {
        it('unstars a message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->unstarMessage('5511999999999@s.whatsapp.net', 'MSG123');

            Http::assertSent(function (Request $request) {
                return $request['star'] === false;
            });
        });
    });

    describe('getMessageById', function () {
        it('retrieves message by ID', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response([
                    'message' => ['text' => 'Hello'],
                ], 200),
            ]);

            $response = $this->resource->getMessageById('5511999999999@s.whatsapp.net', 'MSG123');

            expect($response->isSuccessful())->toBeTrue();

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/getMessageById/test-instance') &&
                    $request['key']['id'] === 'MSG123';
            });
        });
    });

    describe('sendWithTyping', function () {
        it('sends message with typing indicator delay', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->sendWithTyping('5511999999999', 'Hello', 2000);

            Http::assertSent(function (Request $request) {
                return $request['text'] === 'Hello' &&
                    $request['options']['delay'] === 2000;
            });
        });
    });

    describe('reply', function () {
        it('replies to a message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->reply('5511999999999', 'This is a reply', 'QUOTED_MSG_123');

            Http::assertSent(function (Request $request) {
                return $request['text'] === 'This is a reply' &&
                    $request['options']['quoted']['key']['id'] === 'QUOTED_MSG_123';
            });
        });
    });

    describe('forward', function () {
        it('forwards a message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->forward(
                '5511888888888',
                'MSG123',
                '5511999999999@s.whatsapp.net'
            );

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/forwardMessage/test-instance') &&
                    $request['number'] === '5511888888888' &&
                    $request['message']['key']['id'] === 'MSG123';
            });
        });
    });

    describe('presence methods', function () {
        it('sends typing indicator', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->typing('5511999999999');

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendPresence/test-instance') &&
                    $request['presence'] === 'composing';
            });
        });

        it('sends recording indicator', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->recording('5511999999999');

            Http::assertSent(function (Request $request) {
                return $request['presence'] === 'recording';
            });
        });

        it('stops presence indicator', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['status' => 'success'], 200),
            ]);

            $this->resource->stopPresence('5511999999999');

            Http::assertSent(function (Request $request) {
                return $request['presence'] === 'paused';
            });
        });
    });

    describe('sendButtons', function () {
        it('sends buttons message', function () {
            Http::fake([
                'api.evolution.test/*' => Http::response(['key' => []], 200),
            ]);

            $this->resource->sendButtons(
                '5511999999999',
                'Choose an option',
                'Please select one of the following options',
                [
                    ['buttonId' => 'btn1', 'buttonText' => 'Option 1'],
                    ['buttonId' => 'btn2', 'buttonText' => 'Option 2'],
                ],
                'Thank you!'
            );

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'message/sendButtons/test-instance') &&
                    $request['title'] === 'Choose an option' &&
                    count($request['buttons']) === 2 &&
                    $request['footer'] === 'Thank you!';
            });
        });
    });

});
