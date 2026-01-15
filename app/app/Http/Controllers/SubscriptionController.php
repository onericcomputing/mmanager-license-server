<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PayPalSubscriptionService;
use App\Services\StripeSubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected StripeSubscriptionService $stripe,
        protected PayPalSubscriptionService $paypal
    ) {}

    /**
     * Show available plans
     */
    public function plans()
    {
        $plans = Plan::active()->ordered()->get();

        return view('subscription.plans', compact('plans'));
    }

    /**
     * Show checkout page for a plan
     */
    public function checkout(Request $request, Plan $plan)
    {
        $purchaseCode = $request->query('code');

        if (!$purchaseCode) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Code d\'achat requis');
        }

        // Find or validate license
        $license = License::where('purchase_code', $purchaseCode)->first();

        if (!$license) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Code d\'achat invalide');
        }

        // Check if already has active subscription
        $existingSubscription = Subscription::where('license_id', $license->id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            return redirect()->route('subscription.manage', ['code' => $purchaseCode])
                ->with('info', 'Vous avez déjà un abonnement actif');
        }

        return view('subscription.checkout', [
            'plan' => $plan,
            'license' => $license,
            'stripeEnabled' => $this->stripe->isConfigured(),
            'paypalEnabled' => $this->paypal->isConfigured(),
        ]);
    }

    /**
     * Create Stripe checkout session
     */
    public function createStripeSession(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'purchase_code' => 'required|string',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $license = License::where('purchase_code', $request->purchase_code)->firstOrFail();

        if (!$plan->stripe_price_id) {
            return back()->with('error', 'Plan non configuré pour Stripe');
        }

        $session = $this->stripe->createCheckoutSession($license, $plan, [
            'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscription.cancel'),
        ]);

        if (!$session) {
            return back()->with('error', 'Erreur lors de la création de la session de paiement');
        }

        return redirect()->away($session['url']);
    }

    /**
     * Create PayPal subscription
     */
    public function createPayPalSubscription(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'purchase_code' => 'required|string',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $license = License::where('purchase_code', $request->purchase_code)->firstOrFail();

        if (!$plan->paypal_plan_id) {
            return back()->with('error', 'Plan non configuré pour PayPal');
        }

        $result = $this->paypal->createSubscription($license, $plan, [
            'success_url' => route('subscription.success'),
            'cancel_url' => route('subscription.cancel'),
        ]);

        if (!$result || !$result['url']) {
            return back()->with('error', 'Erreur lors de la création de l\'abonnement PayPal');
        }

        return redirect()->away($result['url']);
    }

    /**
     * Handle successful subscription
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        // For Stripe, the webhook will create the subscription
        // Just show success message

        return view('subscription.success');
    }

    /**
     * Handle cancelled checkout
     */
    public function cancel()
    {
        return view('subscription.cancel');
    }

    /**
     * Manage subscription
     */
    public function manage(Request $request)
    {
        $purchaseCode = $request->query('code');

        if (!$purchaseCode) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Code d\'achat requis');
        }

        $license = License::where('purchase_code', $purchaseCode)->first();

        if (!$license) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Code d\'achat invalide');
        }

        $subscription = Subscription::where('license_id', $license->id)
            ->with('plan')
            ->latest()
            ->first();

        $payments = $license->payments()
            ->orderBy('paid_at', 'desc')
            ->limit(10)
            ->get();

        return view('subscription.manage', compact('license', 'subscription', 'payments'));
    }

    /**
     * Open Stripe billing portal
     */
    public function portal(Request $request)
    {
        $purchaseCode = $request->query('code');
        $license = License::where('purchase_code', $purchaseCode)->firstOrFail();

        $subscription = Subscription::where('license_id', $license->id)
            ->where('provider', 'stripe')
            ->whereNotNull('provider_customer_id')
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Aucun abonnement Stripe trouvé');
        }

        $portalUrl = $this->stripe->createPortalSession(
            $subscription,
            route('subscription.manage', ['code' => $purchaseCode])
        );

        if (!$portalUrl) {
            return back()->with('error', 'Impossible d\'accéder au portail de facturation');
        }

        return redirect()->away($portalUrl);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        $purchaseCode = $request->input('purchase_code');
        $license = License::where('purchase_code', $purchaseCode)->firstOrFail();

        $subscription = Subscription::where('license_id', $license->id)
            ->whereIn('status', ['active', 'trialing'])
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Aucun abonnement actif');
        }

        $success = match ($subscription->provider) {
            'stripe' => $this->stripe->cancelSubscription($subscription),
            'paypal' => $this->paypal->cancelSubscription($subscription),
            default => false,
        };

        if ($success) {
            $subscription->cancel();
            return back()->with('success', 'Abonnement annulé. Il restera actif jusqu\'à la fin de la période en cours.');
        }

        return back()->with('error', 'Erreur lors de l\'annulation');
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(Request $request)
    {
        $purchaseCode = $request->input('purchase_code');
        $license = License::where('purchase_code', $purchaseCode)->firstOrFail();

        $subscription = Subscription::where('license_id', $license->id)
            ->where('status', 'canceled')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '>', now())
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Aucun abonnement annulé à réactiver');
        }

        $success = match ($subscription->provider) {
            'stripe' => $this->stripe->resumeSubscription($subscription),
            'paypal' => $this->paypal->reactivateSubscription($subscription),
            default => false,
        };

        if ($success) {
            $subscription->resume();
            return back()->with('success', 'Abonnement réactivé');
        }

        return back()->with('error', 'Erreur lors de la réactivation');
    }
}
