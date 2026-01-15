<?php

namespace App\Http\Controllers\Admin;

use App\Models\License;
use App\Models\LicenseLog;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;

class DashboardController
{
    public function index()
    {
        $stats = [
            'total' => License::count(),
            'active' => License::where('status', 'active')->count(),
            'suspended' => License::where('status', 'suspended')->count(),
            'revoked' => License::where('status', 'revoked')->count(),
            'verifications_today' => LicenseLog::where('action', 'verify')
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'pdfs_today' => LicenseLog::where('action', 'pdf')
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'blocked_today' => LicenseLog::where('status', 'blocked')
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'mrr' => Subscription::where('status', 'active')
                ->whereHas('plan', fn($q) => $q->where('interval', 'month'))
                ->sum('amount') +
                (Subscription::where('status', 'active')
                ->whereHas('plan', fn($q) => $q->where('interval', 'year'))
                ->sum('amount') / 12),
            'revenue_this_month' => Payment::where('status', 'succeeded')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
        ];

        $licenses = License::orderBy('created_at', 'desc')->paginate(20);

        $recentLogs = LicenseLog::with('license')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.dashboard', compact('stats', 'licenses', 'recentLogs'));
    }

    public function licenses(Request $request)
    {
        $query = License::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('buyer', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('purchase_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $licenses = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('admin.licenses', compact('licenses'));
    }

    public function show(License $license)
    {
        $logs = $license->logs()->orderBy('created_at', 'desc')->limit(100)->get();
        return view('admin.license-show', compact('license', 'logs'));
    }

    public function suspend(Request $request, License $license)
    {
        $license->suspend($request->input('reason'));
        return back()->with('success', 'Licence suspendue');
    }

    public function revoke(Request $request, License $license)
    {
        $license->revoke($request->input('reason'));
        return back()->with('success', 'Licence révoquée');
    }

    public function reactivate(License $license)
    {
        $license->reactivate();
        return back()->with('success', 'Licence réactivée');
    }

    public function resetDomain(License $license)
    {
        $license->resetDomain();
        return back()->with('success', 'Domaine réinitialisé');
    }

    public function logs(Request $request)
    {
        $query = LicenseLog::with('license');

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.logs', compact('logs'));
    }

    public function subscriptions(Request $request)
    {
        $query = Subscription::with(['license', 'plan']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(25);

        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'trialing' => Subscription::where('status', 'trialing')->count(),
            'past_due' => Subscription::where('status', 'past_due')->count(),
            'canceled' => Subscription::where('status', 'canceled')->count(),
        ];

        return view('admin.subscriptions', compact('subscriptions', 'stats'));
    }

    public function payments(Request $request)
    {
        $query = Payment::with(['license', 'subscription']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        $payments = $query->orderBy('paid_at', 'desc')->paginate(25);

        $stats = [
            'total' => Payment::sum('amount'),
            'this_month' => Payment::where('status', 'succeeded')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'refunded' => Payment::where('refunded', true)->sum('refund_amount'),
        ];

        return view('admin.payments', compact('payments', 'stats'));
    }
}
