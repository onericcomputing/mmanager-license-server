<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MManager License Server</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">MManager License Server</h1>

        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="/admin/login" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Mot de passe</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                Connexion
            </button>
        </form>
    </div>
</body>
</html>
