@extends('admin.layout')

@section('title', 'Licence - ' . $license->buyer)

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Détails de la licence</h1>
    <a href="{{ route('admin.licenses') }}" class="text-blue-600 hover:underline">Retour à la liste</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Info -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="font-bold">Informations</h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-gray-500">Code d'achat</label>
                <p class="font-mono">{{ $license->masked_code }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Acheteur</label>
                <p>{{ $license->buyer ?? '-' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Domaine</label>
                <p class="font-mono">{{ $license->domain ?? 'Non lié' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Type de licence</label>
                <p>{{ $license->license_type }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Date d'achat</label>
                <p>{{ $license->purchase_date?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Support jusqu'au</label>
                <p class="{{ $license->support_until?->isPast() ? 'text-red-600' : '' }}">
                    {{ $license->support_until?->format('d/m/Y') ?? '-' }}
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Statut</label>
                <p>
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $license->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $license->status === 'suspended' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $license->status === 'revoked' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ $license->status }}
                    </span>
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Dernière version</label>
                <p>{{ $license->last_version ?? '-' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Vérifications</label>
                <p>{{ $license->verification_count }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">PDFs générés</label>
                <p>{{ $license->pdf_count }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Dernière vérification</label>
                <p>{{ $license->last_verified_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Dernière IP</label>
                <p class="font-mono text-sm">{{ $license->last_ip ?? '-' }}</p>
            </div>
        </div>

        @if($license->revoke_reason)
        <div class="px-6 py-4 border-t bg-red-50">
            <label class="text-sm text-red-600">Raison de révocation/suspension</label>
            <p class="text-red-700">{{ $license->revoke_reason }}</p>
            <p class="text-sm text-red-500 mt-1">Le {{ $license->revoked_at?->format('d/m/Y H:i') }}</p>
        </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="font-bold">Actions</h2>
        </div>
        <div class="p-6 space-y-4">
            @if($license->status === 'active')
                <form action="{{ route('admin.licenses.suspend', $license) }}" method="POST">
                    @csrf
                    <input type="text" name="reason" placeholder="Raison (optionnel)" class="w-full px-3 py-2 border rounded mb-2">
                    <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                        Suspendre
                    </button>
                </form>
                <form action="{{ route('admin.licenses.revoke', $license) }}" method="POST">
                    @csrf
                    <input type="text" name="reason" placeholder="Raison (optionnel)" class="w-full px-3 py-2 border rounded mb-2">
                    <button type="submit" class="w-full px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                        onclick="return confirm('Êtes-vous sûr de vouloir révoquer cette licence ?')">
                        Révoquer
                    </button>
                </form>
            @else
                <form action="{{ route('admin.licenses.reactivate', $license) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        Réactiver
                    </button>
                </form>
            @endif

            @if($license->domain)
                <form action="{{ route('admin.licenses.reset-domain', $license) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        onclick="return confirm('Cela permettra à l\'utilisateur de lier la licence à un nouveau domaine.')">
                        Réinitialiser le domaine
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Logs -->
<div class="mt-6 bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="font-bold">Historique d'activité</h2>
    </div>
    <div class="divide-y max-h-96 overflow-y-auto">
        @forelse($logs as $log)
        <div class="px-6 py-3 flex items-center justify-between text-sm">
            <div>
                <span class="font-medium">{{ $log->action }}</span>
                @if($log->failure_reason)
                    <span class="text-red-600">- {{ $log->failure_reason }}</span>
                @endif
            </div>
            <div class="flex items-center space-x-4">
                <span class="font-mono text-xs text-gray-500">{{ $log->ip_address }}</span>
                <span class="px-2 py-1 rounded text-xs
                    {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $log->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $log->status === 'blocked' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    {{ $log->status }}
                </span>
                <span class="text-gray-400">{{ $log->created_at->format('d/m H:i') }}</span>
            </div>
        </div>
        @empty
        <div class="px-6 py-4 text-gray-500 text-center">Aucune activité</div>
        @endforelse
    </div>
</div>
@endsection
