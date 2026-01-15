@extends('admin.layout')

@section('title', 'Abonnements')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Abonnements</h1>
</div>

<!-- Stats -->
<div class="grid grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Total</p>
        <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Actifs</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Essai</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['trialing'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">En retard</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['past_due'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">Annules</p>
        <p class="text-2xl font-bold text-gray-600">{{ $stats['canceled'] }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select name="status" class="border border-gray-300 rounded px-3 py-2">
                <option value="">Tous</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                <option value="trialing" {{ request('status') === 'trialing' ? 'selected' : '' }}>Essai</option>
                <option value="past_due" {{ request('status') === 'past_due' ? 'selected' : '' }}>En retard</option>
                <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Annule</option>
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
        <a href="{{ route('admin.subscriptions') }}" class="text-gray-500 hover:text-gray-700 px-4 py-2">
            Reset
        </a>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Licence</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prochaine facturation</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cree le</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($subscriptions as $subscription)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $subscription->license?->buyer ?? 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $subscription->license?->domain ?? 'Non configure' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $subscription->plan?->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @switch($subscription->status)
                                @case('active') bg-green-100 text-green-800 @break
                                @case('trialing') bg-blue-100 text-blue-800 @break
                                @case('past_due') bg-yellow-100 text-yellow-800 @break
                                @case('canceled') bg-gray-100 text-gray-800 @break
                                @default bg-red-100 text-red-800
                            @endswitch">
                            {{ $subscription->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                        {{ $subscription->provider }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ number_format($subscription->amount, 2, ',', ' ') }} {{ $subscription->currency }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $subscription->current_period_end?->format('d/m/Y') ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $subscription->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        Aucun abonnement trouve
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($subscriptions->hasPages())
<div class="mt-4 flex justify-between items-center">
    <div class="text-sm text-gray-500">
        Affichage de {{ $subscriptions->firstItem() }} à {{ $subscriptions->lastItem() }} sur {{ $subscriptions->total() }} résultats
    </div>
    <div class="flex gap-2">
        @if($subscriptions->onFirstPage())
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Précédent</span>
        @else
            <a href="{{ $subscriptions->previousPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Précédent</a>
        @endif
        @if($subscriptions->hasMorePages())
            <a href="{{ $subscriptions->nextPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Suivant</a>
        @else
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Suivant</span>
        @endif
    </div>
</div>
@endif
@endsection
