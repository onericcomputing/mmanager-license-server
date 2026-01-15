<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayPalSubscriptionService;
use App\Services\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected StripeSubscriptionService $stripe,
        protected PayPalSubscriptionService $paypal
    ) {}

    /**
     * Handle Stripe webhook
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        // Verify signature
        if (!$this->stripe->verifyWebhook($payload, $signature)) {
            Log::warning('Stripe webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $this->stripe->handleWebhook($event);
            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
                'event_type' => $event['type'] ?? 'unknown',
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhook
     */
    public function paypal(Request $request)
    {
        $payload = $request->getContent();
        $headers = [
            'PAYPAL-AUTH-ALGO' => $request->header('PAYPAL-AUTH-ALGO'),
            'PAYPAL-CERT-URL' => $request->header('PAYPAL-CERT-URL'),
            'PAYPAL-TRANSMISSION-ID' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'PAYPAL-TRANSMISSION-SIG' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'PAYPAL-TRANSMISSION-TIME' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];

        // Verify signature (optional in sandbox, required in production)
        if (!config('services.paypal.sandbox')) {
            if (!$this->paypal->verifyWebhook($headers, $payload)) {
                Log::warning('PayPal webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $event = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $this->paypal->handleWebhook($event);
            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('PayPal webhook processing error', [
                'error' => $e->getMessage(),
                'event_type' => $event['event_type'] ?? 'unknown',
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
