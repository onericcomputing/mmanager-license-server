<?php

namespace App\Services;

use App\Models\License;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalSubscriptionService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $webhookId;
    protected string $apiUrl;
    protected bool $sandbox;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id') ?? '';
        $this->clientSecret = config('services.paypal.client_secret') ?? '';
        $this->webhookId = config('services.paypal.webhook_id') ?? '';
        $this->sandbox = config('services.paypal.sandbox', true);
        $this->apiUrl = $this->sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get OAuth access token
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'paypal_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->apiUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));

                return $token;
            }

            Log::error('PayPal token error', ['error' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('PayPal API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a subscription
     */
    public function createSubscription(License $license, Plan $plan, array $options = []): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/v1/billing/subscriptions", [
                    'plan_id' => $plan->paypal_plan_id,
                    'subscriber' => [
                        'name' => [
                            'given_name' => $license->buyer ?? 'Customer',
                        ],
                        'email_address' => $license->buyer_email,
                    ],
                    'custom_id' => (string) $license->id,
                    'application_context' => [
                        'brand_name' => config('app.name'),
                        'locale' => $options['locale'] ?? 'fr-FR',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'SUBSCRIBE_NOW',
                        'return_url' => $options['success_url'] ?? config('app.url') . '/subscription/success',
                        'cancel_url' => $options['cancel_url'] ?? config('app.url') . '/subscription/cancel',
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Find approval link
                $approvalUrl = collect($data['links'] ?? [])
                    ->firstWhere('rel', 'approve')['href'] ?? null;

                return [
                    'subscription_id' => $data['id'],
                    'url' => $approvalUrl,
                    'status' => $data['status'],
                ];
            }

            Log::error('PayPal subscription creation failed', ['error' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('PayPal API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Retrieve subscription details
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->get("{$this->apiUrl}/v1/billing/subscriptions/{$subscriptionId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('PayPal get subscription error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription, string $reason = null): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/v1/billing/subscriptions/{$subscription->provider_subscription_id}/cancel", [
                    'reason' => $reason ?? 'User requested cancellation',
                ]);

            return $response->status() === 204;
        } catch (\Exception $e) {
            Log::error('PayPal cancel error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Suspend subscription
     */
    public function suspendSubscription(Subscription $subscription, string $reason = null): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/v1/billing/subscriptions/{$subscription->provider_subscription_id}/suspend", [
                    'reason' => $reason ?? 'Suspended by admin',
                ]);

            return $response->status() === 204;
        } catch (\Exception $e) {
            Log::error('PayPal suspend error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Reactivate subscription
     */
    public function reactivateSubscription(Subscription $subscription, string $reason = null): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/v1/billing/subscriptions/{$subscription->provider_subscription_id}/activate", [
                    'reason' => $reason ?? 'Reactivated',
                ]);

            return $response->status() === 204;
        } catch (\Exception $e) {
            Log::error('PayPal reactivate error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(array $headers, string $payload): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->post("{$this->apiUrl}/v1/notifications/verify-webhook-signature", [
                    'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
                    'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
                    'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
                    'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
                    'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
                    'webhook_id' => $this->webhookId,
                    'webhook_event' => json_decode($payload, true),
                ]);

            if ($response->successful()) {
                return $response->json('verification_status') === 'SUCCESS';
            }

            return false;
        } catch (\Exception $e) {
            Log::error('PayPal webhook verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook(array $event): void
    {
        $type = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];

        Log::info('PayPal webhook received', ['type' => $type]);

        match ($type) {
            'BILLING.SUBSCRIPTION.ACTIVATED' => $this->handleSubscriptionActivated($resource),
            'BILLING.SUBSCRIPTION.CANCELLED' => $this->handleSubscriptionCancelled($resource),
            'BILLING.SUBSCRIPTION.SUSPENDED' => $this->handleSubscriptionSuspended($resource),
            'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => $this->handlePaymentFailed($resource),
            'PAYMENT.SALE.COMPLETED' => $this->handlePaymentCompleted($resource, $event),
            default => null,
        };
    }

    protected function handleSubscriptionActivated(array $data): void
    {
        $licenseId = $data['custom_id'] ?? null;

        if (!$licenseId) {
            Log::warning('PayPal subscription activated without license_id', $data);
            return;
        }

        $license = License::find($licenseId);
        if (!$license) {
            return;
        }

        // Find plan by PayPal plan ID
        $plan = Plan::where('paypal_plan_id', $data['plan_id'] ?? '')->first();

        // Parse billing info
        $billingInfo = $data['billing_info'] ?? [];
        $nextBillingTime = !empty($billingInfo['next_billing_time'])
            ? \Carbon\Carbon::parse($billingInfo['next_billing_time'])
            : null;

        // Create or update subscription
        Subscription::updateOrCreate(
            ['provider_subscription_id' => $data['id']],
            [
                'license_id' => $license->id,
                'plan_id' => $plan?->id,
                'provider' => 'paypal',
                'status' => 'active',
                'amount' => $billingInfo['last_payment']['amount']['value'] ?? $plan?->price ?? 0,
                'currency' => $billingInfo['last_payment']['amount']['currency_code'] ?? 'EUR',
                'current_period_start' => now(),
                'current_period_end' => $nextBillingTime,
            ]
        );

        // Activate the license
        if ($license->status !== 'active') {
            $license->reactivate();
        }

        Log::info('PayPal subscription activated', ['license_id' => $licenseId, 'subscription_id' => $data['id']]);
    }

    protected function handleSubscriptionCancelled(array $data): void
    {
        $subscription = Subscription::where('provider_subscription_id', $data['id'])->first();

        if (!$subscription) {
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ended_at' => now(),
        ]);

        // Suspend the license
        $subscription->license?->suspend('Abonnement PayPal annulé');

        Log::info('PayPal subscription cancelled', ['subscription_id' => $data['id']]);
    }

    protected function handleSubscriptionSuspended(array $data): void
    {
        $subscription = Subscription::where('provider_subscription_id', $data['id'])->first();

        if (!$subscription) {
            return;
        }

        $subscription->update(['status' => 'paused']);

        Log::info('PayPal subscription suspended', ['subscription_id' => $data['id']]);
    }

    protected function handlePaymentFailed(array $data): void
    {
        $subscription = Subscription::where('provider_subscription_id', $data['id'])->first();

        if ($subscription) {
            $subscription->markPastDue();
        }

        Log::warning('PayPal payment failed', ['subscription_id' => $data['id']]);
    }

    protected function handlePaymentCompleted(array $data, array $event): void
    {
        $subscriptionId = $data['billing_agreement_id'] ?? null;
        $subscription = $subscriptionId
            ? Subscription::where('provider_subscription_id', $subscriptionId)->first()
            : null;

        // Record payment
        Payment::updateOrCreate(
            ['provider_payment_id' => $data['id']],
            [
                'subscription_id' => $subscription?->id,
                'license_id' => $subscription?->license_id,
                'provider' => 'paypal',
                'amount' => $data['amount']['total'] ?? 0,
                'total' => $data['amount']['total'] ?? 0,
                'currency' => $data['amount']['currency'] ?? 'EUR',
                'status' => 'succeeded',
                'paid_at' => isset($data['create_time'])
                    ? \Carbon\Carbon::parse($data['create_time'])
                    : now(),
            ]
        );

        // Ensure subscription and license are active
        if ($subscription) {
            $subscription->markActive();
            if ($subscription->license && $subscription->license->status !== 'active') {
                $subscription->license->reactivate();
            }
        }

        Log::info('PayPal payment completed', ['payment_id' => $data['id']]);
    }
}
