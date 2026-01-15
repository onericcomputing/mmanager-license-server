<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - MManager License Server</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-800 text-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-8">
                    <span class="font-bold text-xl">MManager License Server</span>
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-300">Dashboard</a>
                    <a href="{{ route('admin.licenses') }}" class="hover:text-gray-300">Licences</a>
                    <a href="{{ route('admin.subscriptions') }}" class="hover:text-gray-300">Abonnements</a>
                    <a href="{{ route('admin.payments') }}" class="hover:text-gray-300">Paiements</a>
                    <a href="{{ route('admin.logs') }}" class="hover:text-gray-300">Logs</a>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm hover:text-gray-300">Déconnexion</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
