<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnements - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Choisissez votre abonnement</h1>
            <p class="text-xl text-gray-600">Accédez à toutes les fonctionnalités de MManager</p>
        </div>

        @if(session('error'))
            <div class="max-w-md mx-auto mb-8 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Purchase code input -->
        <div class="max-w-md mx-auto mb-8">
            <form id="codeForm" class="bg-white rounded-lg shadow p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Code d'achat Envato
                </label>
                <input type="text" name="code" id="purchaseCode"
                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    pattern="[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"
                    required>
                <p class="mt-2 text-sm text-gray-500">
                    Vous trouverez votre code d'achat sur CodeCanyon dans "Mes achats"
                </p>
            </form>
        </div>

        <!-- Plans -->
        <div class="grid md:grid-cols-{{ count($plans) > 2 ? '3' : '2' }} gap-8 max-w-5xl mx-auto">
            @foreach($plans as $plan)
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden {{ $plan->is_featured ? 'ring-2 ring-blue-500' : '' }}">
                    @if($plan->is_featured)
                        <div class="bg-blue-500 text-white text-center py-2 text-sm font-semibold">
                            Populaire
                        </div>
                    @endif

                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 mb-6">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold text-gray-900">{{ number_format($plan->price, 2, ',', ' ') }} €</span>
                            <span class="text-gray-500">/{{ $plan->interval === 'month' ? 'mois' : 'an' }}</span>
                        </div>

                        @if($plan->features)
                            <ul class="space-y-3 mb-8">
                                @foreach($plan->features as $feature)
                                    <li class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <button onclick="selectPlan({{ $plan->id }})"
                            class="w-full py-3 px-6 rounded-lg font-semibold transition
                            {{ $plan->is_featured
                                ? 'bg-blue-500 hover:bg-blue-600 text-white'
                                : 'bg-gray-100 hover:bg-gray-200 text-gray-800' }}">
                            Choisir ce plan
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-12 text-gray-500">
            <p>Paiement sécurisé par Stripe et PayPal</p>
            <p class="mt-2">Annulez à tout moment</p>
        </div>
    </div>

    <script>
        function selectPlan(planId) {
            const code = document.getElementById('purchaseCode').value;

            if (!code) {
                alert('Veuillez entrer votre code d\'achat Envato');
                document.getElementById('purchaseCode').focus();
                return;
            }

            // Validate UUID format
            const uuidRegex = /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
            if (!uuidRegex.test(code)) {
                alert('Format de code d\'achat invalide');
                return;
            }

            window.location.href = `/subscription/checkout/${planId}?code=${encodeURIComponent(code)}`;
        }
    </script>
</body>
</html>
