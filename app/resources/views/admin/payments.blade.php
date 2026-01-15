@extends('admin.layout')

@section('title', 'Paiements')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Paiements</h1>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Total encaisse</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['total'], 2, ',', ' ') }} EUR</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Ce mois-ci</p>
        <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['this_month'], 2, ',', ' ') }} EUR</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Rembourse</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($stats['refunded'], 2, ',', ' ') }} EUR</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select name="status" class="border border-gray-300 rounded px-3 py-2">
                <option value="">Tous</option>
                <option value="succeeded" {{ request('status') === 'succeeded' ? 'selected' : '' }}>Reussi</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Echoue</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
            <select name="provider" class="border border-gray-300 rounded px-3 py-2">
                <option value="">Tous</option>
                <option value="stripe" {{ request('provider') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                <option value="paypal" {{ request('provider') === 'paypal' ? 'selected' : '' }}>PayPal</option>
            </select>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            Filtrer
        </button>
        <a href="{{ route('admin.payments') }}" class="text-gray-500 hover:text-gray-700 px-4 py-2">
            Reset
        </a>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Licence</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recu</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $payment->license?->buyer ?? 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $payment->license?->domain ?? 'Non configure' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}
                        </div>
                        @if($payment->refunded)
                            <div class="text-xs text-red-600">
                                Rembourse: {{ number_format($payment->refund_amount, 2, ',', ' ') }} {{ $payment->currency }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @switch($payment->status)
                                @case('succeeded') bg-green-100 text-green-800 @break
                                @case('pending') bg-yellow-100 text-yellow-800 @break
                                @default bg-red-100 text-red-800
                            @endswitch">
                            {{ $payment->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                        {{ $payment->provider }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono text-xs">
                        {{ Str::limit($payment->provider_payment_id, 20) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($payment->receipt_url)
                            <a href="{{ $payment->receipt_url }}" target="_blank" class="text-blue-500 hover:underline">
                                Voir
                            </a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        Aucun paiement trouve
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($payments->hasPages())
<div class="mt-4 flex justify-between items-center">
    <div class="text-sm text-gray-500">
        Affichage de {{ $payments->firstItem() }} à {{ $payments->lastItem() }} sur {{ $payments->total() }} résultats
    </div>
    <div class="flex gap-2">
        @if($payments->onFirstPage())
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Précédent</span>
        @else
            <a href="{{ $payments->previousPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Précédent</a>
        @endif
        @if($payments->hasMorePages())
            <a href="{{ $payments->nextPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Suivant</a>
        @else
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Suivant</span>
        @endif
    </div>
</div>
@endif
@endsection
