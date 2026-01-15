@extends('admin.layout')

@section('title', 'Licences')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Licences</h1>
</div>

<!-- Filters -->
<div class="bg-white p-4 rounded-lg shadow mb-6">
    <form method="GET" class="flex gap-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..."
            class="flex-1 px-3 py-2 border rounded">
        <select name="status" class="px-3 py-2 border rounded">
            <option value="">Tous les statuts</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendue</option>
            <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Révoquée</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filtrer</button>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acheteur</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domaine</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vérifications</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PDFs</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($licenses as $license)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-mono text-xs">{{ $license->masked_code }}</td>
                <td class="px-6 py-4">{{ $license->buyer ?? '-' }}</td>
                <td class="px-6 py-4 font-mono text-sm">{{ $license->domain ?? '-' }}</td>
                <td class="px-6 py-4 text-sm">{{ $license->license_type }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded text-xs font-medium
                        {{ $license->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $license->status === 'suspended' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $license->status === 'revoked' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ $license->status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">{{ $license->verification_count }}</td>
                <td class="px-6 py-4 text-center">{{ $license->pdf_count }}</td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.licenses.show', $license) }}" class="text-blue-600 hover:underline text-sm">Voir</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-6 py-4 text-center text-gray-500">Aucune licence trouvée</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if($licenses->hasPages())
<div class="mt-4 flex justify-between items-center">
    <div class="text-sm text-gray-500">
        Affichage de {{ $licenses->firstItem() }} à {{ $licenses->lastItem() }} sur {{ $licenses->total() }} résultats
    </div>
    <div class="flex gap-2">
        @if($licenses->onFirstPage())
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Précédent</span>
        @else
            <a href="{{ $licenses->previousPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Précédent</a>
        @endif

        @if($licenses->hasMorePages())
            <a href="{{ $licenses->nextPageUrl() }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Suivant</a>
        @else
            <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded">Suivant</span>
        @endif
    </div>
</div>
@endif
@endsection
