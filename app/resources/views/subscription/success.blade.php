<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement réussi !</h1>
            <p class="text-gray-600 mb-6">
                Votre abonnement est maintenant actif. Vous pouvez utiliser MManager avec toutes ses fonctionnalités.
            </p>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 text-sm">
                    Votre licence a été activée automatiquement. Vous recevrez un email de confirmation.
                </p>
            </div>

            <a href="{{ config('license.client_app_url', 'https://app.mmanager.fr') }}"
                class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-8 rounded-lg transition">
                Accéder à MManager
            </a>

            <p class="text-sm text-gray-500 mt-6">
                Un problème ? <a href="mailto:support@mmanager.fr" class="text-blue-500 hover:underline">Contactez-nous</a>
            </p>
        </div>
    </div>
</body>
</html>
