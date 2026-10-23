@extends('layouts.app', ['title' => 'Super administration'])
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">ADMINISTRATION GLOBALE</div>
        <h1 class="page-title">Tableau de bord Super Admin</h1>
        <p class="subtitle">Supervisez l'ensemble des comptes entreprises, utilisateurs, connexions WhatsApp et campagnes de la plateforme.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <a class="btn btn-light" href="{{ route('admin.tenants.index') }}">Gérer les entreprises →</a>
        <span class="pill pill-green">Accès Super Admin</span>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><span>Entreprises</span><span class="stat-icon">▦</span></div>
        <div class="stat-value">{{ number_format($tenantCount) }}</div>
        <div class="stat-note"><span class="positive">{{ $activeTenantCount }} actives</span> · {{ $suspendedTenantCount }} suspendues</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Utilisateurs</span><span class="stat-icon">👥</span></div>
        <div class="stat-value">{{ number_format($userCount) }}</div>
        <div class="stat-note">{{ $adminCount }} super administrateur(s)</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Campagnes totales</span><span class="stat-icon">◈</span></div>
        <div class="stat-value">{{ number_format($campaignCount) }}</div>
        <div class="stat-note">Toutes entreprises confondues</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Comptes WhatsApp</span><span class="stat-icon">◉</span></div>
        <div class="stat-value">{{ number_format($whatsappCount) }}</div>
        <div class="stat-note">Numéros configurés en Cloud API</div>
    </div>
</div>

<div class="quick-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
    <a class="quick-action" href="{{ route('admin.tenants.index') }}">
        <div class="quick-icon">▦</div>
        <strong>Gérer les entreprises</strong>
        <span>Consulter, activer ou suspendre les espaces entreprises créés sur la plateforme.</span>
    </a>
    <a class="quick-action" href="{{ route('admin.users.index') }}">
        <div class="quick-icon">👥</div>
        <strong>Gestion des utilisateurs</strong>
        <span>Attribuer les droits super administrateurs et superviser les comptes inscrits.</span>
    </a>
    <a class="quick-action" href="{{ route('admin.campaigns.index') }}">
        <div class="quick-icon">◈</div>
        <strong>Toutes les campagnes</strong>
        <span>Suivre les messages envoyés, programmés ou en attente à l'échelle de la plateforme.</span>
    </a>
</div>

<div class="dashboard-grid">
    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Dernières entreprises inscrites</div>
                <div class="panel-subtitle">Les 10 plus récents espaces de travail créés</div>
            </div>
            <a class="btn btn-light" style="padding:6px 12px;font-size:11px" href="{{ route('admin.tenants.index') }}">Voir tout</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Entreprise</th>
                        <th>Propriétaire</th>
                        <th>Membres</th>
                        <th>Campagnes</th>
                        <th>État</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTenants as $tenant)
                    <tr>
                        <td class="campaign-name">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" style="color:var(--green);font-weight:700">
                                {{ $tenant->name }}
                            </a>
                            <div class="campaign-meta">{{ $tenant->slug }}</div>
                        </td>
                        <td>
                            <div>{{ $tenant->owner?->name ?? 'Non assigné' }}</div>
                            <div class="campaign-meta">{{ $tenant->owner?->email }}</div>
                        </td>
                        <td>{{ $tenant->users_count }}</td>
                        <td>{{ $tenant->campaigns_count }}</td>
                        <td>
                            <span class="pill {{ $tenant->status === 'active' ? 'pill-green' : 'pill-red' }}">
                                {{ $tenant->status === 'active' ? 'Actif' : 'Suspendu' }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a class="btn btn-light" style="padding:4px 8px;font-size:10px" href="{{ route('admin.tenants.show', $tenant) }}">Détails</a>
                                <form method="POST" action="{{ route('admin.tenants.switch', $tenant) }}" style="margin:0">
                                    @csrf
                                    <button class="btn btn-light" style="padding:4px 8px;font-size:10px;color:var(--green)" title="Ouvrir l'espace client">Visiter</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6">Aucune entreprise enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Dernières campagnes globales</div>
                <div class="panel-subtitle">Activité récente sur la plateforme</div>
            </div>
            <a class="btn btn-light" style="padding:6px 12px;font-size:11px" href="{{ route('admin.campaigns.index') }}">Voir tout</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campagne</th>
                        <th>Entreprise</th>
                        <th>État</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCampaigns as $c)
                    <tr>
                        <td class="campaign-name">
                            {{ $c->name }}
                            <div class="campaign-meta">{{ $c->channel_label }}</div>
                        </td>
                        <td>{{ $c->tenant?->name ?? '—' }}</td>
                        <td>
                            <span class="pill {{ in_array($c->status, ['sent', 'completed']) ? 'pill-green' : ($c->status === 'failed' ? 'pill-red' : 'pill-yellow') }}">
                                {{ $c->status_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3">Aucune campagne récente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
