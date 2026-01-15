<?php

namespace App\Http\Controllers\Api;

use App\Models\License;
use App\Models\LicenseLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PdfController
{
    public function generate(Request $request)
    {
        $code = $request->header('X-Purchase-Code');
        $domain = $request->header('X-Domain');

        if (empty($code) || empty($domain)) {
            return response()->json(['error' => 'missing_credentials', 'message' => 'Identifiants requis'], 400);
        }

        $license = License::where('purchase_code', $code)->first();

        if (!$license) {
            $this->logPdfFailed($code, $domain, 'not_found');
            return response()->json(['error' => 'not_found', 'message' => 'Licence introuvable'], 404);
        }

        if ($license->isRevoked()) {
            $this->logPdfFailed($code, $domain, $license->status, $license->id);
            return response()->json(['error' => $license->status, 'message' => 'Licence ' . $license->status], 403);
        }

        if (!$license->matchesDomain($domain)) {
            $this->logPdfFailed($code, $domain, 'domain_mismatch', $license->id);
            return response()->json(['error' => 'domain_mismatch', 'message' => 'Domaine non autorisé'], 403);
        }

        // Check subscription status
        if (!$license->hasActiveSubscription()) {
            $this->logPdfFailed($code, $domain, 'no_subscription', $license->id);
            return response()->json([
                'error' => 'no_subscription',
                'message' => 'Abonnement requis pour générer des PDF.',
                'subscription_url' => route('subscription.plans'),
            ], 402);
        }

        try {
            $type = $request->input('type', 'invoice');
            $data = $request->input('data', []);
            $template = $request->input('template', []);
            $logo = $request->input('logo');

            $view = $type === 'quote' ? 'pdf.quote' : 'pdf.invoice';

            $pdf = Pdf::loadView($view, [
                'data' => $data,
                'template' => $template,
                'logo' => $logo,
                'company' => $data['company'] ?? [],
                'client' => $data['client'] ?? [],
                'items' => $data['items'] ?? [],
                'payments' => $data['payments'] ?? [],
                'settings' => $data['settings'] ?? [],
            ]);

            $pdf->setPaper($template['page_size'] ?? 'A4', $template['orientation'] ?? 'portrait');

            $license->recordPdf();
            $license->log('pdf', 'success', null, ['type' => $type, 'number' => $data['number'] ?? null]);

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            $this->logPdfFailed($code, $domain, $e->getMessage(), $license->id);
            return response()->json(['error' => 'generation_failed', 'message' => 'Erreur de génération'], 500);
        }
    }

    protected function logPdfFailed(string $code, string $domain, string $reason, ?int $licenseId = null): void
    {
        LicenseLog::create([
            'license_id' => $licenseId,
            'purchase_code' => $code,
            'domain' => $domain,
            'ip_address' => request()->ip(),
            'action' => 'pdf',
            'status' => 'blocked',
            'failure_reason' => $reason,
        ]);
    }
}
