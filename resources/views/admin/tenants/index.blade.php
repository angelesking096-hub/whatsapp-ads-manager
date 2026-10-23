@extends('layouts.app', ['title' => 'Entreprises clientes · Administration'])
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">SUPER ADMINISTRATION</div>
        <h1 class="page-title">Gestion des entreprises clientes</h1>
        <p class="subtitle">Visualisez, inspectez, activez ou suspendez les espaces clients créés sur la plateforme.</p>
    </div>
    <a class="btn btn-light" href="{{ route('admin.dashboard') }}">← Retour au tableau de bord</a>
</div>

<div class="panel">
    <form method="GET" action="{{ route('admin.tenants.index') }}" class="filters">
        <input class="field" style="max-width:320px" type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher nom, slug ou email...">
        <select class="select" style="max-width:180px" name="status">
            <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Tous les états</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs uniquement</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendus uniquement</option>
        </select>
        <button class="btn btn-primary" type="submit">Filtrer</button>
        @if(request()->hasAny(['search', 'status']))
            <a class="btn btn-light" href="{{ route('admin.tenants.index') }}">Réinitialiser</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Entreprise & Slug</th>
                    <th>Propriétaire</th>
                    <th>Membres</th>
                    <th>Campagnes</th>
                    <th>Contacts</th>
                    <th>WhatsApp API</th>
                    <th>État</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                <tr>
                    <td class="campaign-name">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" style="color:var(--green);font-weight:700">
                            {{ $tenant->name }}
                        </a>
                        <div class="campaign-meta">{{ $tenant->slug }} · Créée le {{ $tenant->created_at->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div>{{ $tenant->owner?->name ?? '—' }}</div>
                        <div class="campaign-meta">{{ $tenant->owner?->email }}</div>
                    </td>
                    <td>{{ $tenant->users_count }}</td>
                    <td>{{ $tenant->campaigns_count }}</td>
                    <td>{{ $tenant->contacts_count }}</td>
                    <td>
                        @php $hasWa = $tenant->whatsappAccounts->where('status','configured')->count() > 0; @endphp
                        <span class="pill {{ $hasWa ? 'pill-green' : 'pill-yellow' }}">
                            {{ $hasWa ? 'Configuré' : 'Non configuré' }}
                        </span>
                    </td>
                    <td>
                        <span class="pill {{ $tenant->status === 'active' ? 'pill-green' : 'pill-red' }}">
                            {{ $tenant->status === 'active' ? 'Actif' : 'Suspendu' }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div style="display:inline-flex;gap:6px">
                            <a class="btn btn-light" style="padding:5px 9px;font-size:11px" href="{{ route('admin.tenants.show', $tenant) }}">
                                Fiche
                            </a>
                            <form method="POST" action="{{ route('admin.tenants.switch', $tenant) }}" style="margin:0">
                                @csrf
                                <button class="btn btn-light" style="padding:5px 9px;font-size:11px;color:var(--green)" title="Ouvrir l'espace client">
                                    Ouvrir
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}" style="margin:0" onsubmit="return confirm('Modifier l’état de cet espace ?')">
                                @csrf
                                <button class="btn {{ $tenant->status === 'active' ? 'btn-danger' : 'btn-primary' }}" style="padding:5px 9px;font-size:11px">
                                    {{ $tenant->status === 'active' ? 'Suspendre' : 'Activer' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">
                        Aucune entreprise trouvée avec ces critères.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenants->hasPages())
        <div style="margin-top:20px">
            {{ $tenants->links() }}
        </div>
    @endif
</div>
@endsection
