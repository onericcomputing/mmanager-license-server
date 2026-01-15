@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-3xl font-bold text-blue-600">{{ $stats['total'] }}</div>
        <div class="text-gray-500">Total Licences</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-3xl font-bold text-green-600">{{ $stats['active'] }}</div>
        <div class="text-gray-500">Actives</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-3xl font-bold text-yellow-600">{{ $stats['suspended'] }}</div>
        <div class="text-gray-500">Suspendues</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-3xl font-bold text-red-600">{{ $stats['revoked'] }}</div>
        <div class="text-gray-500">Révoquées</div>
    </div>
</div>

<!-- Subscription Stats -->
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 rounded-lg shadow text-white">
        <div class="text-3xl font-bold">{{ $stats['active_subscriptions'] ?? 0 }}</div>
        <div class="text-blue-100">Abonnements actifs</div>
    </div>
    <div class="bg-gradient-to-r from-green-500 to-green-600 p-4 rounded-lg shadow text-white">
        <div class="text-3xl font-bold">{{ number_format($stats['mrr'] ?? 0, 0, ',', ' ') }} EUR</div>
        <div class="text-green-100">MRR (Revenu mensuel)</div>
    </div>
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4 rounded-lg shadow text-white">
        <div class="text-3xl font-bold">{{ number_format($stats['revenue_this_month'] ?? 0, 0, ',', ' ') }} EUR</div>
        <div class="text-purple-100">Revenus ce mois</div>
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-2xl font-bold">{{ $stats['verifications_today'] }}</div>
        <div class="text-gray-500">Verifications aujourd'hui</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-2xl font-bold">{{ $stats['pdfs_today'] }}</div>
        <div class="text-gray-500">PDFs generes aujourd'hui</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="text-2xl font-bold text-red-600">{{ $stats['blocked_today'] }}</div>
        <div class="text-gray-500">Tentatives bloquees</div>
    </div>
</div>

<!-- Recent Licenses -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="font-bold">Licences récentes</h2>
        <a href="{{ route('admin.licenses') }}" class="text-blue-600 text-sm">Voir toutes</a>
    </div>
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acheteur</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domaine</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vérifié</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($licenses as $license)
            <tr>
                <td class="px-6 py-4">{{ $license->buyer ?? '-' }}</td>
                <td class="px-6 py-4 font-mono text-sm">{{ $license->domain ?? '-' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $license->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $license->status === 'suspended' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $license->status === 'revoked' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ $license->status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $license->last_verified_at?->diffForHumans() ?? 'Jamais' }}
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.licenses.show', $license) }}" class="text-blue-600 hover:underline text-sm">Détails</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Recent Logs -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="font-bold">Activité récente</h2>
        <a href="{{ route('admin.logs') }}" class="text-blue-600 text-sm">Voir tous</a>
    </div>
    <div class="divide-y max-h-96 overflow-y-auto">
        @foreach($recentLogs as $log)
        <div class="px-6 py-3 flex items-center justify-between text-sm">
            <div>
                <span class="font-medium">{{ $log->action }}</span>
                <span class="text-gray-500">- {{ $log->domain ?? 'N/A' }}</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="px-2 py-1 rounded text-xs
                    {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $log->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $log->status === 'blocked' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    {{ $log->status }}
                </span>
                <span class="text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
