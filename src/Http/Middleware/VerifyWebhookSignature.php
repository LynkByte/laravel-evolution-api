<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lynkbyte\EvolutionApi\Exceptions\WebhookException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify webhook signatures from Evolution API.
 */
class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification if disabled in config
        if (!config('evolution-api.webhook.verify_signature', true)) {
            return $next($request);
        }

        $secret = config('evolution-api.webhook.secret');

        // Skip if no secret is configured
        if (empty($secret)) {
            return $next($request);
        }

        // Get signature from header
        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Evolution-Signature')
            ?? $request->header('X-Signature');

        if (empty($signature)) {
            return $this->invalidSignatureResponse('Missing signature header');
        }

        // Verify signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return $this->invalidSignatureResponse('Invalid signature');
        }

        return $next($request);
    }

    /**
     * Return an invalid signature response.
     */
    protected function invalidSignatureResponse(string $message): Response
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 401);
    }
}
