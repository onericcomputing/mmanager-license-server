<?php

namespace App\Http\Controllers\Api;

use App\Models\License;
use App\Models\LicenseLog;
use App\Services\EnvatoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController
{
    protected EnvatoService $envato;

    public function __construct(EnvatoService $envato)
    {
        $this->envato = $envato;
    }

    public function verify(Request $request): JsonResponse
    {
        $code = $request->header('X-Purchase-Code') ?? $request->input('purchase_code');
        $domain = $request->header('X-Domain') ?? $request->input('domain');
        $version = $request->input('version');
        $ip = $request->ip();

        if (empty($code) || empty($domain)) {
            return response()->json([
                'valid' => false,
                'error_code' => 'missing_params',
                'message' => 'Code d\'achat et domaine requis',
            ], 400);
        }

        // Validate format
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $code)) {
            return response()->json([
                'valid' => false,
                'error_code' => 'invalid_format',
                'message' => 'Format du code invalide',
            ], 400);
        }

        // Check existing license
        $license = License::where('purchase_code', $code)->first();

        if ($license) {
            return $this->verifyExisting($license, $domain, $version, $ip);
        }

        // New license - verify with Envato
        return $this->verifyNew($code, $domain, $version, $ip);
    }

    protected function verifyExisting(License $license, string $domain, ?string $version, string $ip): JsonResponse
    {
        // Check if revoked/suspended
        if ($license->isRevoked()) {
            $this->logBlocked($license, 'verify', $license->status);
            return response()->json([
                'valid' => false,
                'error_code' => $license->status,
                'message' => $license->status === 'revoked' ? 'Licence révoquée' : 'Licence suspendue',
            ], 403);
        }

        // Check subscription status
        if (!$license->hasActiveSubscription()) {
            $this->logBlocked($license, 'verify', 'no_subscription');
            return response()->json([
                'valid' => false,
                'error_code' => 'no_subscription',
                'message' => 'Abonnement requis. Veuillez souscrire un abonnement pour utiliser MManager.',
                'subscription_url' => route('subscription.plans'),
            ], 402);
        }

        // Check domain
        if (!empty($license->domain) && !$license->matchesDomain($domain)) {
            $this->logBlocked($license, 'verify', 'domain_mismatch');
            return response()->json([
                'valid' => false,
                'error_code' => 'domain_mismatch',
                'message' => 'Cette licence est liée à un autre domaine: ' . $license->domain,
            ], 403);
        }

        // Bind domain if not set
        if (empty($license->domain)) {
            $license->bindDomain($domain);
        }

        // Refresh Envato data periodically
        if (!$license->envato_checked_at || $license->envato_checked_at->diffInDays(now()) > 7) {
            $this->refreshEnvato($license);
        }

        $license->recordVerification($ip, $version);
        $license->log('verify', 'success');

        // Get subscription info for response
        $subscription = $license->activeSubscription;

        return response()->json([
            'valid' => true,
            'buyer' => $license->buyer,
            'license' => $license->license_type,
            'license_type' => $license->license_type,
            'purchase_date' => $license->purchase_date?->toDateString(),
            'support_until' => $license->support_until?->toDateString(),
            'subscription' => $subscription ? [
                'plan' => $subscription->plan?->name,
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end?->toDateString(),
            ] : null,
        ]);
    }

    protected function verifyNew(string $code, string $domain, ?string $version, string $ip): JsonResponse
    {
        $result = $this->envato->verify($code);

        if (!$result['valid']) {
            LicenseLog::create([
                'purchase_code' => $code,
                'domain' => $domain,
                'ip_address' => $ip,
                'action' => 'verify',
                'status' => 'failed',
                'failure_reason' => $result['error'],
                'version' => $version,
            ]);

            return response()->json([
                'valid' => false,
                'error_code' => $result['error'],
                'message' => $result['message'],
            ], $result['error'] === 'not_found' ? 404 : 400);
        }

        // Create license
        $normalized = preg_replace('/^www\./', '', strtolower(trim($domain)));
        $license = License::create([
            'purchase_code' => $code,
            'buyer' => $result['buyer'],
            'license_type' => $result['license'],
            'purchase_date' => isset($result['sold_at']) ? Carbon::parse($result['sold_at']) : null,
            'support_until' => isset($result['supported_until']) ? Carbon::parse($result['supported_until']) : null,
            'item_id' => $result['item_id'],
            'domain' => $normalized,
            'domain_hash' => hash('sha256', $normalized),
            'status' => 'active',
            'envato_data' => $result['raw'] ?? null,
            'envato_checked_at' => now(),
            'last_verified_at' => now(),
            'last_ip' => $ip,
            'last_version' => $version,
            'verification_count' => 1,
        ]);

        $license->log('activate', 'success');

        return response()->json([
            'valid' => true,
            'buyer' => $license->buyer,
            'license' => $license->license_type,
            'license_type' => $license->license_type,
            'purchase_date' => $license->purchase_date?->toDateString(),
            'support_until' => $license->support_until?->toDateString(),
        ]);
    }

    protected function refreshEnvato(License $license): void
    {
        $result = $this->envato->verify($license->purchase_code);
        if ($result['valid']) {
            $license->update([
                'support_until' => isset($result['supported_until']) ? Carbon::parse($result['supported_until']) : null,
                'envato_data' => $result['raw'] ?? null,
                'envato_checked_at' => now(),
            ]);
        }
    }

    protected function logBlocked(License $license, string $action, string $reason): void
    {
        LicenseLog::create([
            'license_id' => $license->id,
            'purchase_code' => $license->purchase_code,
            'domain' => request()->header('X-Domain'),
            'ip_address' => request()->ip(),
            'action' => $action,
            'status' => 'blocked',
            'failure_reason' => $reason,
        ]);
    }
}
