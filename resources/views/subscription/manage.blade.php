<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mon abonnement - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Mon abonnement</h1>

            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                    {{ session('info') }}
                </div>
            @endif

            <!-- License info -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Licence</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Titulaire</p>
                        <p class="font-medium">{{ $license->buyer ?? $license->buyer_email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Statut</p>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            {{ $license->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $license->status === 'active' ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Domaine</p>
                        <p class="font-medium">{{ $license->domain ?? 'Non configuré' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Code d'achat</p>
                        <p class="font-mono text-sm">{{ $license->masked_code }}</p>
                    </div>
                </div>
            </div>

            <!-- Subscription info -->
            @if($subscription)
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Abonnement</h2>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                            @switch($subscription->status)
                                @case('active')
                                @case('trialing')
                                    bg-green-100 text-green-800
                                    @break
                                @case('past_due')
                                    bg-yellow-100 text-yellow-800
                                    @break
                                @case('canceled')
                                    bg-gray-100 text-gray-800
                                    @break
                                @default
                                    bg-red-100 text-red-800
                            @endswitch">
                            @switch($subscription->status)
                                @case('active') Actif @break
                                @case('trialing') Essai @break
                                @case('past_due') Paiement en retard @break
                                @case('canceled') Annulé @break
                                @default {{ $subscription->status }}
                            @endswitch
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500">Plan</p>
                            <p class="font-medium">{{ $subscription->plan?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Montant</p>
                            <p class="font-medium">{{ number_format($subscription->amount, 2, ',', ' ') }} {{ $subscription->currency }}/{{ $subscription->plan?->interval === 'month' ? 'mois' : 'an' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Prochaine facturation</p>
                            <p class="font-medium">{{ $subscription->current_period_end?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Mode de paiement</p>
                            <p class="font-medium capitalize">{{ $subscription->provider }}</p>
                        </div>
                    </div>

                    @if($subscription->isCanceled() && $subscription->hasGracePeriod())
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <p class="text-yellow-800 text-sm">
                                Votre abonnement a été annulé mais reste actif jusqu'au {{ $subscription->current_period_end->format('d/m/Y') }}.
                            </p>
                        </div>
                    @endif

                    <div class="flex gap-4">
                        @if($subscription->provider === 'stripe' && $subscription->isActive())
                            <a href="{{ route('subscription.portal', ['code' => $license->purchase_code]) }}"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                                Gérer la facturation
                            </a>
                        @endif

                        @if($subscription->isActive() && !$subscription->isCanceled())
                            <form action="{{ route('subscription.cancel') }}" method="POST"
                                onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                                @csrf
                                <input type="hidden" name="purchase_code" value="{{ $license->purchase_code }}">
                                <button type="submit"
                                    class="bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-2 px-4 rounded-lg transition">
                                    Annuler l'abonnement
                                </button>
                            </form>
                        @endif

                        @if($subscription->isCanceled() && $subscription->hasGracePeriod())
                            <form action="{{ route('subscription.resume') }}" method="POST">
                                @csrf
                                <input type="hidden" name="purchase_code" value="{{ $license->purchase_code }}">
                                <button type="submit"
                                    class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                                    Réactiver l'abonnement
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6 text-center">
                    <p class="text-gray-600 mb-4">Vous n'avez pas d'abonnement actif.</p>
                    <a href="{{ route('subscription.plans') }}?code={{ $license->purchase_code }}"
                        class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition">
                        Choisir un abonnement
                    </a>
                </div>
            @endif

            <!-- Payment history -->
            @if($payments->count() > 0)
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Historique des paiements</h2>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-sm text-gray-500 border-b">
                                    <th class="pb-3">Date</th>
                                    <th class="pb-3">Montant</th>
                                    <th class="pb-3">Statut</th>
                                    <th class="pb-3">Reçu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($payments as $payment)
                                    <tr>
                                        <td class="py-3">{{ $payment->paid_at?->format('d/m/Y') }}</td>
                                        <td class="py-3 font-medium">{{ $payment->formatted_amount }}</td>
                                        <td class="py-3">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $payment->status === 'succeeded' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $payment->status === 'succeeded' ? 'Payé' : $payment->status }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            @if($payment->receipt_url)
                                                <a href="{{ $payment->receipt_url }}" target="_blank" class="text-blue-500 hover:underline text-sm">
                                                    Voir
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
