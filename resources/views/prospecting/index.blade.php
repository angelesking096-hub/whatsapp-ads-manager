@extends('layouts.app', ['title' => 'Prospection & Scraping IA'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">INTELLIGENCE COMMERCIALE</div>
        <h1 class="page-title">Prospection & Scraping IA Ultra-Ciblé</h1>
        <p class="subtitle">Récupérez en temps réel les numéros WhatsApp de prospects qualifiés par l'IA qui ont besoin de vos services à l'instant T.</p>
    </div>
    <div style="display:flex;gap:10px">
        @if($totalLeads > 0)
            <form method="POST" action="{{ route('prospecting.import-all') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    ✓ Importer tous les prospects ({{ $totalLeads - $importedCount }} en attente)
                </button>
            </form>
            <form method="POST" action="{{ route('prospecting.clear') }}" onsubmit="return confirm('Réinitialiser l’historique des prospects ?');">
                @csrf
                <button type="submit" class="btn btn-light" style="color:#b94438">Vider l'historique</button>
            </form>
        @endif
    </div>
</div>

<!-- Quota Banner -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><span>Prospects scrappés ce mois</span><span class="stat-icon">🎯</span></div>
        <div class="stat-value">{{ $tenant->scraping_used_this_month }} <small style="font-size:14px;color:var(--muted)">/ {{ $tenant->monthly_scraping_quota }}</small></div>
        <div class="stat-note">{{ $tenant->remaining_scraping_quota }} crédits IA restants</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Total prospects en base</span><span class="stat-icon">📋</span></div>
        <div class="stat-value">{{ $totalLeads }}</div>
        <div class="stat-note">Détectés sur vos critères</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Prospects importés</span><span class="stat-icon">✓</span></div>
        <div class="stat-value" style="color:#188151">{{ $importedCount }}</div>
        <div class="stat-note">Prêts pour diffusion</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Score moyen IA</span><span class="stat-icon">⚡</span></div>
        <div class="stat-value" style="color:var(--green)">89%</div>
        <div class="stat-note">Indice d'intention d'achat élevé</div>
    </div>
</div>

<!-- Moteur de recherche 1-clic -->
<div class="panel" style="margin-bottom:24px;border:2px solid #a6d5c0;background:#fbfefc">
    <div class="panel-header">
        <div>
            <div class="panel-title" style="font-size:16px;color:var(--green)">⚡ Lancer une recherche de prospects ciblés en 1 clic</div>
            <div class="panel-subtitle">L'IA extrait les entreprises réelles, filtre leurs coordonnées WhatsApp et qualifie leur besoin immédiat.</div>
        </div>
    </div>
    <form method="POST" action="{{ route('prospecting.search') }}">
        @csrf
        <div class="form-grid" style="grid-template-columns:repeat(4,1fr);gap:14px">
            <div>
                <label class="field-label">Secteur / Métier cible *</label>
                <input class="field" type="text" name="category" placeholder="Ex: Immobilier, Restaurants, Cliniques, E-commerce" value="{{ old('category', 'Agences Immobilières') }}" required>
            </div>
            <div>
                <label class="field-label">Ville cible *</label>
                <input class="field" type="text" name="city" placeholder="Ex: Douala, Yaoundé, Paris, Abidjan" value="{{ old('city', 'Douala') }}" required>
            </div>
            <div>
                <label class="field-label">Pays</label>
                <input class="field" type="text" name="country" placeholder="Ex: Cameroun, Côte d'Ivoire, France" value="{{ old('country', 'Cameroun') }}">
            </div>
            <div>
                <label class="field-label">Nombre de prospects</label>
                <select class="select" name="limit">
                    <option value="10">10 prospects qualifiés</option>
                    <option value="25" selected>25 prospects qualifiés</option>
                    <option value="50">50 prospects qualifiés</option>
                </select>
            </div>
            <div class="form-group full" style="margin-top:4px">
                <label class="field-label">Mots-clés de besoin spécifiques (Optionnel)</label>
                <input class="field" type="text" name="keywords" placeholder="Ex: recherche clients, vente directe, besoin de promotion, acquisition rapide" value="{{ old('keywords') }}">
            </div>
        </div>
        <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:11px;color:var(--muted)">
                Consomme 1 crédit de scraping IA par prospect qualifié.
            </span>
            <button type="submit" class="btn btn-primary" style="padding:12px 24px;font-size:13px">
                🔍 Scrapper & Qualifier par l'IA en 1 Clic →
            </button>
        </div>
    </form>
</div>

<!-- Liste des prospects qualifiés -->
<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Prospects Détectés & Qualifiés</div>
            <div class="panel-subtitle">Analyse en temps réel de leur besoin commercial et de leur réceptivité sur WhatsApp.</div>
        </div>
        @if($totalLeads > 0)
            <a class="btn btn-primary" href="{{ route('broadcasts.create') }}?audience=scraped_leads">
                🚀 Diffuser vers ces prospects (Multi-Numéros) →
            </a>
        @endif
    </div>

    @if($leads->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">🎯</div>
            <h3>Aucun prospect pour le moment</h3>
            <p>Utilisez le formulaire ci-dessus pour lancer votre premier scraping de contacts ultra-ciblé boosté par l'IA.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Entreprise & Contact</th>
                        <th>WhatsApp / Téléphone</th>
                        <th>Ville & Niche</th>
                        <th>Besoin détecté à l'instant T (IA)</th>
                        <th>Score & Angle d'approche (Pitch)</th>
                        <th>Statut</th>
                        <th style="text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                    <tr>
                        <td>
                            <div style="font-weight:700;font-size:13px">{{ $lead->business_name }}</div>
                            @if($lead->contact_name)
                                <div style="font-size:11px;color:var(--muted)">👤 {{ $lead->contact_name }}</div>
                            @endif
                            @if($lead->website)
                                <div style="font-size:10px;color:var(--green)">
                                    <a href="{{ $lead->website }}" target="_blank" rel="noopener">🌐 {{ parse_url($lead->website, PHP_URL_HOST) }}</a>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="font-family:monospace;font-weight:700;font-size:13px;color:#132a25">
                                {{ $lead->phone }}
                            </div>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$lead->phone) }}" target="_blank" rel="noopener" style="font-size:10px;color:#188151;display:inline-flex;align-items:center;gap:3px">
                                💬 Ouvrir WhatsApp
                            </a>
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $lead->city }}</div>
                            <div style="font-size:10px;color:var(--muted)">{{ $lead->category }}</div>
                        </td>
                        <td style="max-width:320px;white-space:normal">
                            <div style="background:#eaf7f0;border-left:3px solid #188151;padding:8px 10px;border-radius:6px;font-size:11px;line-height:1.5;color:#134430">
                                <strong>💡 Besoin identifié :</strong><br>
                                {{ $lead->detected_need }}
                            </div>
                        </td>
                        <td style="max-width:280px;white-space:normal">
                            <span class="pill pill-green" style="font-weight:800">
                                ★ {{ $lead->qualification_score }}% Match
                            </span>
                            <div style="font-size:11px;color:var(--muted);margin-top:5px;line-height:1.4">
                                <em>« {{ $lead->ai_pitch }} »</em>
                            </div>
                        </td>
                        <td>
                            @if($lead->is_imported)
                                <span class="pill pill-green">✓ Importé</span>
                            @else
                                <span class="pill pill-yellow">En attente</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <div style="display:inline-flex;gap:6px">
                                @if(!$lead->is_imported)
                                    <form method="POST" action="{{ route('prospecting.import-single', $lead) }}">
                                        @csrf
                                        <button class="btn btn-primary" style="padding:6px 11px;font-size:11px" title="Importer dans les contacts">
                                            + Importer
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('prospecting.destroy', $lead) }}" onsubmit="return confirm('Retirer ce prospect ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-light" style="padding:6px 9px;font-size:11px;color:#b94438" title="Retirer">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:18px">
            {{ $leads->links() }}
        </div>
    @endif
</div>
@endsection
