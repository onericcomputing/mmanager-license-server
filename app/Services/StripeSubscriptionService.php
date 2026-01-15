<?php

namespace App\Services;

use App\Models\License;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeSubscriptionService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.stripe.com/v1';
    protected string $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret') ?? '';
        $this->webhookSecret = config('services.stripe.webhook_secret') ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Create a Stripe customer
     */
    public function createCustomer(License $license): ?string
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->asForm()
                ->post("{$this->apiUrl}/customers", [
                    'email' => $license->buyer_email,
                    'name' => $license->buyer,
                    'metadata' => [
                        'license_id' => $license->id,
                        'purchase_code' => substr($license->purchase_code, 0, 8) . '...',
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::error('Stripe customer creation failed', ['error' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Stripe API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a checkout session for subscription
     */
    public function createCheckoutSession(License $license, Plan $plan, array $options = []): ?array
    {
        try {
            $params = [
                'mode' => 'subscription',
                'success_url' => $options['success_url'] ?? config('app.url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $options['cancel_url'] ?? config('app.url') . '/subscription/cancel',
                'customer_email' => $license->buyer_email,
                'client_reference_id' => (string) $license->id,
                'metadata' => [
                    'license_id' => $license->id,
                    'plan_id' => $plan->id,
                ],
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'subscription_data' => [
                    'metadata' => [
                        'license_id' => $license->id,
                        'plan_id' => $plan->id,
                    ],
                ],
                'locale' => $options['locale'] ?? 'fr',
                'allow_promotion_codes' => true,
            ];

            // Add trial period if configured
            if (!empty($options['trial_days'])) {
                $params['subscription_data']['trial_period_days'] = $options['trial_days'];
            }

            $response = Http::withBasicAuth($this->apiKey, '')
                ->asForm()
                ->post("{$this->apiUrl}/checkout/sessions", $this->flattenParams($params));

            if ($response->successful()) {
                $session = $response->json();
                return [
                    'session_id' => $session['id'],
                    'url' => $session['url'],
                ];
            }

            Log::error('Stripe checkout session failed', ['error' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Stripe API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a billing portal session
     */
    public function createPortalSession(Subscription $subscription, string $returnUrl): ?string
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->asForm()
                ->post("{$this->apiUrl}/billing_portal/sessions", [
                    'customer' => $subscription->provider_customer_id,
                    'return_url' => $returnUrl,
                ]);

            if ($response->successful()) {
                return $response->json('url');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Stripe portal error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): bool
    {
        try {
            if ($immediately) {
                $response = Http::withBasicAuth($this->apiKey, '')
                    ->delete("{$this->apiUrl}/subscriptions/{$subscription->provider_subscription_id}");
            } else {
                $response = Http::withBasicAuth($this->apiKey, '')
                    ->asForm()
                    ->post("{$this->apiUrl}/subscriptions/{$subscription->provider_subscription_id}", [
                        'cancel_at_period_end' => 'true',
                    ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Stripe cancel error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(Subscription $subscription): bool
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->asForm()
                ->post("{$this->apiUrl}/subscriptions/{$subscription->provider_subscription_id}", [
                    'cancel_at_period_end' => 'false',
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Stripe resume error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update subscription plan
     */
    public function changePlan(Subscription $subscription, Plan $newPlan): bool
    {
        try {
            // Get current subscription to find item ID
            $subResponse = Http::withBasicAuth($this->apiKey, '')
                ->get("{$this->apiUrl}/subscriptions/{$subscription->provider_subscription_id}");

            if (!$subResponse->successful()) {
                return false;
            }

            $stripeSubscription = $subResponse->json();
            $itemId = $stripeSubscription['items']['data'][0]['id'] ?? null;

            if (!$itemId) {
                return false;
            }

            // Update the subscription item with new price
            $response = Http::withBasicAuth($this->apiKey, '')
                ->asForm()
                ->post("{$this->apiUrl}/subscriptions/{$subscription->provider_subscription_id}", [
                    "items[0][id]" => $itemId,
                    "items[0][price]" => $newPlan->stripe_price_id,
                    'proration_behavior' => 'create_prorations',
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Stripe change plan error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Retrieve subscription from Stripe
     */
    public function retrieveSubscription(string $subscriptionId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->get("{$this->apiUrl}/subscriptions/{$subscriptionId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook(array $event): void
    {
        $type = $event['type'] ?? '';
        $data = $event['data']['object'] ?? [];

        Log::info('Stripe webhook received', ['type' => $type]);

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($data),
            'customer.subscription.created' => $this->handleSubscriptionCreated($data),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($data),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($data),
            'invoice.paid' => $this->handleInvoicePaid($data),
            'invoice.payment_failed' => $this->handlePaymentFailed($data),
            default => null,
        };
    }

    protected function handleCheckoutCompleted(array $data): void
    {
        $licenseId = $data['metadata']['license_id'] ?? ($data['client_reference_id'] ?? null);
        $planId = $data['metadata']['plan_id'] ?? null;

        if (!$licenseId) {
            Log::warning('Checkout completed without license_id', $data);
            return;
        }

        $license = License::find($licenseId);
        $plan = Plan::find($planId);

        if (!$license || !$plan) {
            Log::warning('License or plan not found', ['license_id' => $licenseId, 'plan_id' => $planId]);
            return;
        }

        // Subscription will be created via customer.subscription.created event
        // Store customer ID for later use
        if (!empty($data['customer'])) {
            Log::info('Checkout completed', [
                'license_id' => $licenseId,
                'customer' => $data['customer'],
                'subscription' => $data['subscription'] ?? null,
            ]);
        }
    }

    protected function handleSubscriptionCreated(array $data): void
    {
        $licenseId = $data['metadata']['license_id'] ?? null;
        $planId = $data['metadata']['plan_id'] ?? null;

        if (!$licenseId) {
            Log::warning('Subscription created without license_id', $data);
            return;
        }

        $license = License::find($licenseId);
        $plan = Plan::find($planId);

        if (!$license) {
            return;
        }

        // Create local subscription record
        Subscription::updateOrCreate(
            ['provider_subscription_id' => $data['id']],
            [
                'license_id' => $license->id,
                'plan_id' => $plan?->id,
                'provider' => 'stripe',
                'provider_customer_id' => $data['customer'],
                'status' => $this->mapStripeStatus($data['status']),
                'amount' => ($data['items']['data'][0]['price']['unit_amount'] ?? 0) / 100,
                'currency' => strtoupper($data['currency']),
                'current_period_start' => isset($data['current_period_start'])
                    ? \Carbon\Carbon::createFromTimestamp($data['current_period_start'])
                    : null,
                'current_period_end' => isset($data['current_period_end'])
                    ? \Carbon\Carbon::createFromTimestamp($data['current_period_end'])
                    : null,
                'trial_ends_at' => isset($data['trial_end'])
                    ? \Carbon\Carbon::createFromTimestamp($data['trial_end'])
                    : null,
            ]
        );

        // Activate the license
        if ($license->status !== 'active') {
            $license->reactivate();
        }

        Log::info('Subscription created', ['license_id' => $licenseId, 'subscription_id' => $data['id']]);
    }

    protected function handleSubscriptionUpdated(array $data): void
    {
        $subscription = Subscription::where('provider_subscription_id', $data['id'])->first();

        if (!$subscription) {
            // Try to create it
            $this->handleSubscriptionCreated($data);
            return;
        }

        $subscription->update([
            'status' => $this->mapStripeStatus($data['status']),
            'current_period_start' => isset($data['current_period_start'])
                ? \Carbon\Carbon::createFromTimestamp($data['current_period_start'])
                : $subscription->current_period_start,
            'current_period_end' => isset($data['current_period_end'])
                ? \Carbon\Carbon::createFromTimestamp($data['current_period_end'])
                : $subscription->current_period_end,
            'canceled_at' => !empty($data['canceled_at'])
                ? \Carbon\Carbon::createFromTimestamp($data['canceled_at'])
                : null,
        ]);

        // Update license status based on subscription
        $this->syncLicenseStatus($subscription);

        Log::info('Subscription updated', ['subscription_id' => $data['id'], 'status' => $data['status']]);
    }

    protected function handleSubscriptionDeleted(array $data): void
    {
        $subscription = Subscription::where('provider_subscription_id', $data['id'])->first();

        if (!$subscription) {
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'ended_at' => now(),
        ]);

        // Suspend the license
        $subscription->license?->suspend('Abonnement terminé');

        Log::info('Subscription deleted', ['subscription_id' => $data['id']]);
    }

    protected function handleInvoicePaid(array $data): void
    {
        $subscriptionId = $data['subscription'] ?? null;
        $subscription = $subscriptionId
            ? Subscription::where('provider_subscription_id', $subscriptionId)->first()
            : null;

        // Record payment
        Payment::updateOrCreate(
            ['provider_payment_id' => $data['payment_intent'] ?? $data['id']],
            [
                'subscription_id' => $subscription?->id,
                'license_id' => $subscription?->license_id,
                'provider' => 'stripe',
                'provider_invoice_id' => $data['id'],
                'amount' => ($data['amount_paid'] ?? 0) / 100,
                'tax' => ($data['tax'] ?? 0) / 100,
                'total' => ($data['total'] ?? $data['amount_paid'] ?? 0) / 100,
                'currency' => strtoupper($data['currency']),
                'status' => 'succeeded',
                'receipt_url' => $data['hosted_invoice_url'] ?? null,
                'invoice_pdf' => $data['invoice_pdf'] ?? null,
                'paid_at' => now(),
            ]
        );

        // Reactivate license if needed
        if ($subscription && $subscription->license) {
            if ($subscription->license->status !== 'active') {
                $subscription->license->reactivate();
            }
            $subscription->markActive();
        }

        Log::info('Invoice paid', ['invoice_id' => $data['id']]);
    }

    protected function handlePaymentFailed(array $data): void
    {
        $subscriptionId = $data['subscription'] ?? null;
        $subscription = $subscriptionId
            ? Subscription::where('provider_subscription_id', $subscriptionId)->first()
            : null;

        if ($subscription) {
            $subscription->markPastDue();
        }

        Log::warning('Payment failed', ['invoice_id' => $data['id'], 'subscription_id' => $subscriptionId]);
    }

    protected function mapStripeStatus(string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            'unpaid' => 'unpaid',
            'trialing' => 'trialing',
            'paused' => 'paused',
            default => $status,
        };
    }

    protected function syncLicenseStatus(Subscription $subscription): void
    {
        $license = $subscription->license;
        if (!$license) {
            return;
        }

        if ($subscription->isActive() || $subscription->isTrialing()) {
            if ($license->status !== 'active') {
                $license->reactivate();
            }
        } elseif ($subscription->status === 'canceled' && !$subscription->hasGracePeriod()) {
            $license->suspend('Abonnement annulé');
        } elseif ($subscription->isPastDue()) {
            // Keep active but log warning
            Log::warning('License has past due subscription', ['license_id' => $license->id]);
        }
    }

    /**
     * Flatten nested params for Stripe API
     */
    protected function flattenParams(array $params, string $prefix = ''): array
    {
        $result = [];

        foreach ($params as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenParams($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
