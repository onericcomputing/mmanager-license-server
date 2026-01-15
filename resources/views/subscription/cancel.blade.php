<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement annulé - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement annulé</h1>
            <p class="text-gray-600 mb-6">
                Votre paiement a été annulé. Aucun montant n'a été débité.
            </p>

            <a href="{{ route('subscription.plans') }}"
                class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-8 rounded-lg transition">
                Réessayer
            </a>

            <p class="text-sm text-gray-500 mt-6">
                Un problème technique ? <a href="mailto:support@mmanager.fr" class="text-blue-500 hover:underline">Contactez-nous</a>
            </p>
        </div>
    </div>
</body>
</html>
