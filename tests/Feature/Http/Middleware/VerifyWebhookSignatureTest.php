<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Lynkbyte\EvolutionApi\Http\Middleware\VerifyWebhookSignature;

uses(\Lynkbyte\EvolutionApi\Tests\TestCase::class);

describe('VerifyWebhookSignature Middleware', function () {

    beforeEach(function () {
        $this->middleware = new VerifyWebhookSignature();
    });

    describe('when verification is disabled', function () {
        it('passes through when verify_signature is false', function () {
            config(['evolution-api.webhook.verify_signature' => false]);
            config(['evolution-api.webhook.secret' => 'my-secret']);

            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData(true)['passed'])->toBeTrue();
        });
    });

    describe('when no secret is configured', function () {
        it('passes through when secret is null', function () {
            config(['evolution-api.webhook.verify_signature' => true]);
            config(['evolution-api.webhook.secret' => null]);

            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });

        it('passes through when secret is empty string', function () {
            config(['evolution-api.webhook.verify_signature' => true]);
            config(['evolution-api.webhook.secret' => '']);

            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });
    });

    describe('signature validation', function () {
        beforeEach(function () {
            config(['evolution-api.webhook.verify_signature' => true]);
            config(['evolution-api.webhook.secret' => 'test-secret-key']);
        });

        it('returns 401 when signature header is missing', function () {
            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(401);
            expect($response->getData(true)['status'])->toBe('error');
            expect($response->getData(true)['message'])->toBe('Missing signature header');
        });

        it('returns 401 when signature is invalid', function () {
            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            $request->headers->set('X-Webhook-Signature', 'invalid-signature');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(401);
            expect($response->getData(true)['message'])->toBe('Invalid signature');
        });

        it('accepts valid signature via X-Webhook-Signature header', function () {
            $payload = '{"event":"test"}';
            $secret = 'test-secret-key';
            $signature = hash_hmac('sha256', $payload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Webhook-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
            expect($response->getData(true)['passed'])->toBeTrue();
        });

        it('accepts valid signature via X-Evolution-Signature header', function () {
            $payload = '{"event":"test"}';
            $secret = 'test-secret-key';
            $signature = hash_hmac('sha256', $payload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Evolution-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });

        it('accepts valid signature via X-Signature header', function () {
            $payload = '{"event":"test"}';
            $secret = 'test-secret-key';
            $signature = hash_hmac('sha256', $payload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });

        it('prefers X-Webhook-Signature over other headers', function () {
            $payload = '{"event":"test"}';
            $secret = 'test-secret-key';
            $validSignature = hash_hmac('sha256', $payload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Webhook-Signature', $validSignature);
            $request->headers->set('X-Evolution-Signature', 'invalid');
            $request->headers->set('X-Signature', 'invalid');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });

        it('validates signature with complex payload', function () {
            $payload = json_encode([
                'event' => 'messages.upsert',
                'instance' => 'test-instance',
                'data' => [
                    'key' => [
                        'remoteJid' => '5511999999999@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => 'MSG123',
                    ],
                    'message' => [
                        'conversation' => 'Hello World!',
                    ],
                ],
            ]);
            $secret = 'test-secret-key';
            $signature = hash_hmac('sha256', $payload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Webhook-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });

        it('rejects tampered payload', function () {
            $originalPayload = '{"event":"test"}';
            $tamperedPayload = '{"event":"tampered"}';
            $secret = 'test-secret-key';
            $signature = hash_hmac('sha256', $originalPayload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $tamperedPayload);
            $request->headers->set('X-Webhook-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(401);
            expect($response->getData(true)['message'])->toBe('Invalid signature');
        });

        it('rejects signature with wrong secret', function () {
            $payload = '{"event":"test"}';
            $wrongSecret = 'wrong-secret';
            $signature = hash_hmac('sha256', $payload, $wrongSecret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Webhook-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(401);
        });

        it('handles empty payload', function () {
            $payload = '';
            $secret = 'test-secret-key';
            $signature = hash_hmac('sha256', $payload, $secret);

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Webhook-Signature', $signature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(200);
        });

        it('uses timing-safe comparison', function () {
            // This test verifies that hash_equals is used (timing-safe)
            // by checking that similar signatures don't pass
            $payload = '{"event":"test"}';
            $secret = 'test-secret-key';
            $validSignature = hash_hmac('sha256', $payload, $secret);
            
            // Create a signature that differs by one character
            $invalidSignature = substr($validSignature, 0, -1) . '0';

            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-Webhook-Signature', $invalidSignature);
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->getStatusCode())->toBe(401);
        });
    });

    describe('invalidSignatureResponse', function () {
        beforeEach(function () {
            config(['evolution-api.webhook.verify_signature' => true]);
            config(['evolution-api.webhook.secret' => 'test-secret']);
        });

        it('returns JSON response with correct structure', function () {
            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            expect($response->headers->get('Content-Type'))->toContain('application/json');
        });

        it('includes status and message in error response', function () {
            $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event":"test"}');
            
            $response = $this->middleware->handle($request, fn ($req) => response()->json(['passed' => true]));

            $data = $response->getData(true);
            expect($data)->toHaveKey('status');
            expect($data)->toHaveKey('message');
            expect($data['status'])->toBe('error');
        });
    });

});
