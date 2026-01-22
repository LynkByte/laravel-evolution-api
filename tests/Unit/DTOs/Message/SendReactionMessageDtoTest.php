<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\Message\SendReactionMessageDto;

describe('SendReactionMessageDto', function () {
    describe('constructor', function () {
        it('creates a reaction message DTO', function () {
            $key = [
                'remoteJid' => '5511999999999@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MESSAGE_ID_123'
            ];
            
            $dto = new SendReactionMessageDto(
                key: $key,
                reaction: '👍'
            );

            expect($dto->key)->toBe($key);
            expect($dto->reaction)->toBe('👍');
        });

        it('throws exception when key is empty', function () {
            new SendReactionMessageDto(key: [], reaction: '👍');
        })->throws(InvalidArgumentException::class, 'The key field is required.');

        it('throws exception when reaction is empty', function () {
            new SendReactionMessageDto(
                key: ['remoteJid' => 'test', 'fromMe' => false, 'id' => '123'],
                reaction: ''
            );
        })->throws(InvalidArgumentException::class, 'The reaction field is required.');
    });

    describe('react', function () {
        it('creates a reaction to a message', function () {
            $dto = SendReactionMessageDto::react(
                remoteJid: '5511999999999@s.whatsapp.net',
                messageId: 'MSG_123',
                fromMe: false,
                reaction: '❤️'
            );

            expect($dto->key)->toBe([
                'remoteJid' => '5511999999999@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG_123',
            ]);
            expect($dto->reaction)->toBe('❤️');
        });

        it('reacts to own message', function () {
            $dto = SendReactionMessageDto::react(
                remoteJid: '5511999999999@s.whatsapp.net',
                messageId: 'MSG_123',
                fromMe: true,
                reaction: '😂'
            );

            expect($dto->key['fromMe'])->toBeTrue();
        });
    });

    describe('remove', function () {
        it('removes a reaction from a message', function () {
            $dto = SendReactionMessageDto::remove(
                remoteJid: '5511999999999@s.whatsapp.net',
                messageId: 'MSG_123',
                fromMe: false
            );

            expect($dto->key)->toBe([
                'remoteJid' => '5511999999999@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG_123',
            ]);
            expect($dto->reaction)->toBe('');
        });
    });

    describe('toApiPayload', function () {
        it('returns correct payload structure', function () {
            $dto = SendReactionMessageDto::react(
                remoteJid: '5511999999999@s.whatsapp.net',
                messageId: 'MSG_123',
                fromMe: false,
                reaction: '👍'
            );

            $payload = $dto->toApiPayload();

            expect($payload)->toBe([
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'MSG_123',
                ],
                'reaction' => '👍',
            ]);
        });
    });

    describe('emoji reactions', function () {
        it('supports various emoji reactions', function () {
            $emojis = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🔥', '🎉', '💯', '👏'];
            
            foreach ($emojis as $emoji) {
                $dto = SendReactionMessageDto::react(
                    remoteJid: 'test@s.whatsapp.net',
                    messageId: 'msg-123',
                    fromMe: false,
                    reaction: $emoji
                );
                
                expect($dto->reaction)->toBe($emoji);
            }
        });
    });
});
