@extends('layouts.app', ['title' => 'Fiche entreprise · ' . $tenant->name])
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">SUPER ADMINISTRATION / ENTREPRISES</div>
        <h1 class="page-title">{{ $tenant->name }}</h1>
        <p class="subtitle">Identifiant unique : <code>{{ $tenant->slug }}</code> · Créée le {{ $tenant->created_at->format('d/m/Y à H:i') }}</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <form method="POST" action="{{ route('admin.tenants.switch', $tenant) }}" style="margin:0">
            @csrf
            <button class="btn btn-primary" type="submit">Visualiser cet espace →</button>
        </form>
        <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}" style="margin:0">
            @csrf
            <button class="btn {{ $tenant->status === 'active' ? 'btn-danger' : 'btn-primary' }}" type="submit">
                {{ $tenant->status === 'active' ? 'Suspendre l’espace' : 'Activer l’espace' }}
            </button>
        </form>
        <a class="btn btn-light" href="{{ route('admin.tenants.index') }}">← Retour à la liste</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><span>État actuel</span><span class="stat-icon">▦</span></div>
        <div class="stat-value" style="font-size:20px">
            <span class="pill {{ $tenant->status === 'active' ? 'pill-green' : 'pill-red' }}">
                {{ $tenant->status === 'active' ? 'Actif' : 'Suspendu' }}
            </span>
        </div>
        <div class="stat-note">Accès aux fonctionnalités</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Membres</span><span class="stat-icon">👥</span></div>
        <div class="stat-value">{{ $tenant->users->count() }}</div>
        <div class="stat-note">Utilisateurs rattachés</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Campagnes</span><span class="stat-icon">◈</span></div>
        <div class="stat-value">{{ $tenant->campaigns->count() }}</div>
        <div class="stat-note">Créées dans cet espace</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Contacts</span><span class="stat-icon">♧</span></div>
        <div class="stat-value">{{ number_format($contactsCount) }}</div>
        <div class="stat-note"><span class="positive">{{ $reachableContactsCount }} joignables</span></div>
    </div>
</div>

<div class="dashboard-grid" style="margin-top:20px">
    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Configuration WhatsApp Cloud API</div>
                <div class="panel-subtitle">Intégration du numéro de l'entreprise</div>
            </div>
        </div>
        @php $wa = $tenant->whatsappAccounts->first(); @endphp
        @if($wa)
            <div class="progress-row">
                <span>Numéro affiché</span>
                <strong>{{ $wa->display_phone_number ?? '—' }}</strong>
            </div>
            <div class="progress-row">
                <span>Phone Number ID</span>
                <code>{{ $wa->phone_number_id ?? '—' }}</code>
            </div>
            <div class="progress-row">
                <span>Business Account ID</span>
                <code>{{ $wa->business_account_id ?? 'Non renseigné' }}</code>
            </div>
            <div class="progress-row">
                <span>Statut de connexion</span>
                <span class="pill {{ $wa->status === 'configured' ? 'pill-green' : 'pill-yellow' }}">
                    {{ $wa->status === 'configured' ? 'Configuré & Chiffré' : $wa->status }}
                </span>
            </div>
        @else
            <div class="notice">
                <span class="notice-icon">ⓘ</span>
                <div>Cette entreprise n'a pas encore renseigné de numéro WhatsApp ou de jeton Cloud API.</div>
            </div>
        @endif

        <div class="panel-header" style="margin-top:26px">
            <div>
                <div class="panel-title">Membres de l'équipe</div>
                <div class="panel-subtitle">Utilisateurs ayant accès à cet espace</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenant->users as $u)
                    <tr>
                        <td><strong>{{ $u->name }}</strong> @if($u->id === $tenant->owner_id) <span class="pill pill-green" style="font-size:9px">Propriétaire</span> @endif</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ ucfirst($u->pivot->role ?? 'membre') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Campagnes de l'entreprise</div>
                <div class="panel-subtitle">Historique des 20 dernières campagnes</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campagne</th>
                        <th>Canal</th>
                        <th>État</th>
                        <th>Créée le</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenant->campaigns as $camp)
                    <tr>
                        <td class="campaign-name">{{ $camp->name }}</td>
                        <td>{{ $camp->channel_label }}</td>
                        <td>
                            <span class="pill {{ in_array($camp->status, ['sent', 'completed']) ? 'pill-green' : ($camp->status === 'failed' ? 'pill-red' : 'pill-yellow') }}">
                                {{ $camp->status_label }}
                            </span>
                        </td>
                        <td>{{ $camp->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4">Aucune campagne pour cette entreprise.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
