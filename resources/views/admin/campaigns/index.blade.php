@extends('layouts.app', ['title' => 'Toutes les campagnes · Administration'])
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">SUPER ADMINISTRATION</div>
        <h1 class="page-title">Supervision de toutes les campagnes</h1>
        <p class="subtitle">Consultez l'ensemble des campagnes créées par les entreprises clientes sur la plateforme.</p>
    </div>
    <a class="btn btn-light" href="{{ route('admin.dashboard') }}">← Retour au tableau de bord</a>
</div>

<div class="panel">
    <form method="GET" action="{{ route('admin.campaigns.index') }}" class="filters">
        <input class="field" style="max-width:300px" type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par titre de campagne...">
        <select class="select" style="max-width:180px" name="channel">
            <option value="all" {{ request('channel') === 'all' || !request('channel') ? 'selected' : '' }}>Tous les canaux</option>
            <option value="private_message" {{ request('channel') === 'private_message' ? 'selected' : '' }}>Messages privés</option>
            <option value="status" {{ request('channel') === 'status' ? 'selected' : '' }}>Statuts WhatsApp</option>
            <option value="group" {{ request('channel') === 'group' ? 'selected' : '' }}>Groupes WhatsApp</option>
        </select>
        <select class="select" style="max-width:180px" name="status">
            <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Tous les états</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillons</option>
            <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Programmées</option>
            <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>En attente</option>
            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Acceptées API</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échecs</option>
        </select>
        <button class="btn btn-primary" type="submit">Filtrer</button>
        @if(request()->hasAny(['search', 'channel', 'status']))
            <a class="btn btn-light" href="{{ route('admin.campaigns.index') }}">Réinitialiser</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Campagne</th>
                    <th>Entreprise cliente</th>
                    <th>Canal</th>
                    <th>Modèle</th>
                    <th>Destinataires</th>
                    <th>État</th>
                    <th>Créée le</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $camp)
                <tr>
                    <td class="campaign-name">
                        {{ $camp->name }}
                        @if($camp->scheduled_at)
                            <div class="campaign-meta">Planifiée pour le {{ $camp->scheduled_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </td>
                    <td>
                        @if($camp->tenant)
                            <a href="{{ route('admin.tenants.show', $camp->tenant) }}" style="color:var(--green);font-weight:600">
                                {{ $camp->tenant->name }}
                            </a>
                        @else
                            <span style="color:var(--muted)">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="channel">
                            <span class="mini-dot"></span>
                            {{ $camp->channel_label }}
                        </span>
                    </td>
                    <td>
                        @if($camp->template_name)
                            <code>{{ $camp->template_name }}</code>
                        @else
                            <span style="color:var(--muted)">Standard</span>
                        @endif
                    </td>
                    <td>{{ $camp->deliveries_count }} destinataire(s)</td>
                    <td>
                        <span class="pill {{ in_array($camp->status, ['sent', 'completed']) ? 'pill-green' : ($camp->status === 'failed' ? 'pill-red' : 'pill-yellow') }}">
                            {{ $camp->status_label }}
                        </span>
                    </td>
                    <td>{{ $camp->created_at->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:30px;color:var(--muted)">
                        Aucune campagne trouvée.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($campaigns->hasPages())
        <div style="margin-top:20px">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>
@endsection
