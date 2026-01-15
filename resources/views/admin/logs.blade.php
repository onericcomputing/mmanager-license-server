@extends('admin.layout')

@section('title', 'Logs')

@section('content')
<h1 class="text-2xl font-bold mb-6">Logs d'activité</h1>

<!-- Filters -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
    <form method="GET" class="flex gap-4">
        <select name="action" class="px-3 py-2 border rounded">
            <option value="">Toutes les actions</option>
            <option value="verify" {{ request('action') === 'verify' ? 'selected' : '' }}>Vérification</option>
            <option value="pdf" {{ request('action') === 'pdf' ? 'selected' : '' }}>PDF</option>
            <option value="activate" {{ request('action') === 'activate' ? 'selected' : '' }}>Activation</option>
            <option value="revoke" {{ request('action') === 'revoke' ? 'selected' : '' }}>Révocation</option>
            <option value="suspend" {{ request('action') === 'suspend' ? 'selected' : '' }}>Suspension</option>
        </select>
        <select name="status" class="px-3 py-2 border rounded">
            <option value="">Tous les statuts</option>
            <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Succès</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échec</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Bloqué</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filtrer</button>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domaine</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raison</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td class="px-6 py-4 font-medium">{{ $log->action }}</td>
                <td class="px-6 py-4 font-mono text-sm">{{ $log->domain ?? '-' }}</td>
                <td class="px-6 py-4 font-mono text-xs">{{ $log->ip_address }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $log->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $log->status === 'blocked' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                        {{ $log->status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $log->failure_reason ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun log trouvé</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if($logs->hasPages())
<div class="mt-4 flex justify-between items-center">
    <div class="text-sm text-gray-500">
        Affichage de {{ $logs->firstItem() }} à {{ $logs->lastItem() }} sur {{ $logs->total() }} résultats
    </div>
    <div class="flex gap-2">
        @if($logs->onFirstPage())
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Précédent</span>
        @else
            <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Précédent</a>
        @endif
        @if($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Suivant</a>
        @else
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Suivant</span>
        @endif
    </div>
</div>
@endif
@endsection
