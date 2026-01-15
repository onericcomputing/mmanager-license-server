<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            <a href="{{ route('subscription.plans') }}" class="text-blue-500 hover:text-blue-600 mb-8 inline-block">
                ← Retour aux plans
            </a>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Finaliser votre abonnement</h1>
                    <p class="text-blue-100 mt-1">Plan {{ $plan->name }}</p>
                </div>

                <div class="p-8">
                    @if(session('error'))
                        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Order summary -->
                    <div class="border-b pb-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Récapitulatif</h2>

                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">{{ $plan->name }} ({{ $plan->interval_label }})</span>
                            <span class="font-medium">{{ number_format($plan->price, 2, ',', ' ') }} €</span>
                        </div>

                        @if($plan->features)
                            <ul class="mt-4 space-y-1">
                                @foreach($plan->features as $feature)
                                    <li class="text-sm text-gray-500 flex items-center">
                                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-4 pt-4 border-t flex justify-between text-lg font-bold">
                            <span>Total {{ $plan->interval === 'month' ? 'mensuel' : 'annuel' }}</span>
                            <span>{{ number_format($plan->price, 2, ',', ' ') }} €</span>
                        </div>
                    </div>

                    <!-- License info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Licence associée</h3>
                        <p class="text-sm text-gray-600">{{ $license->buyer ?? $license->buyer_email }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $license->masked_code }}</p>
                    </div>

                    <!-- Payment methods -->
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Méthode de paiement</h2>

                    <div class="space-y-4">
                        @if($stripeEnabled && $plan->stripe_price_id)
                            <form action="{{ route('subscription.stripe.create') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="purchase_code" value="{{ $license->purchase_code }}">

                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-3 bg-[#635BFF] hover:bg-[#5851DB] text-white py-4 px-6 rounded-lg font-semibold transition">
                                    <svg class="w-8 h-8" viewBox="0 0 60 25" fill="currentColor">
                                        <path d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a10.5 10.5 0 01-4.56 1c-4.01 0-6.83-2.5-6.83-7.28 0-4.19 2.39-7.36 6.3-7.36 3.87 0 5.96 2.98 5.96 7.28 0 .5-.04 1.14-.06 1.44zm-6.04-5.81c-1.28 0-2.12.96-2.23 2.54h4.27c-.05-1.48-.72-2.54-2.04-2.54z"/>
                                        <path d="M24.73 5.72h4.28l-5.37 18.78h-4.36l5.45-18.78z"/>
                                        <path d="M14.1 5.56c3.42 0 5.83 2.38 5.83 6.3v.49l-7.7 2.14c.48 1.48 1.68 2.14 3.2 2.14 1.36 0 2.61-.44 3.8-1.18v3.55c-1.3.85-2.9 1.3-4.6 1.3-4.43 0-6.98-2.76-6.98-7.05 0-4.24 2.68-7.69 6.45-7.69zm-2.35 6.76l4.04-1.13c-.19-1.48-1.04-2.24-2.12-2.24-1.4 0-2.31 1.34-1.92 3.37z"/>
                                        <path d="M0 5.72h4.38v2.12C5.3 6.32 6.6 5.56 8.44 5.56c2.84 0 4.23 1.92 4.23 5.05v9.89H8.45v-8.8c0-1.58-.54-2.42-1.84-2.42-1.26 0-2.23.96-2.23 2.84v8.38H0V5.72z"/>
                                        <path d="M36.49 5.72h4.38v14.78h-4.38V5.72zm2.19-5.72c1.48 0 2.54.96 2.54 2.34s-1.06 2.34-2.54 2.34-2.54-.96-2.54-2.34S37.2 0 38.68 0z"/>
                                    </svg>
                                    Payer avec Stripe
                                </button>
                            </form>
                        @endif

                        @if($paypalEnabled && $plan->paypal_plan_id)
                            <form action="{{ route('subscription.paypal.create') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="purchase_code" value="{{ $license->purchase_code }}">

                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-3 bg-[#FFC439] hover:bg-[#F0B72F] text-[#003087] py-4 px-6 rounded-lg font-semibold transition">
                                    <svg class="w-24 h-6" viewBox="0 0 100 24" fill="currentColor">
                                        <path d="M12.5 3h5.4c3.6 0 6.1 1.4 5.5 5.4-.6 4.3-3.2 6.4-6.6 6.4H14l-1 6.2H9.3l3.2-18zm3.4 8.5h1.4c1.6 0 3-.6 3.3-2.5.3-2-1-2.5-2.6-2.5h-1.2l-.9 5z"/>
                                        <path d="M27 14.5c.5-3.2 2.4-5 5-5 1.7 0 2.8 1 2.5 2.7-.4 2.3-2 3.7-4.2 3.7-1 0-1.5-.4-1.6-1-.2.8-.4 1.6-.6 2.3h-3.3l2.2-12.7H30l-1 5c.5-.8 1.5-1.2 2.6-1.2 2.5 0 4 1.8 3.5 4.7-.6 3.6-3.2 5.5-6.2 5.5-2.6 0-4-1.3-3.5-4z"/>
                                    </svg>
                                    Payer avec PayPal
                                </button>
                            </form>
                        @endif
                    </div>

                    <p class="text-center text-sm text-gray-500 mt-6">
                        En vous abonnant, vous acceptez nos
                        <a href="#" class="text-blue-500 hover:underline">conditions d'utilisation</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
