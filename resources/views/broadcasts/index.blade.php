@extends('layouts.app', ['title' => 'Diffusions Multi-Numéros'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">AUTOMATISATION D'ENVOI</div>
        <h1 class="page-title">Diffusions Multi-Numéros</h1>
        <p class="subtitle">Diffusez vos campagnes de prospection en répartissant automatiquement les messages sur vos 10 numéros WhatsApp avec rotation et protection anti-ban.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('broadcasts.create') }}">
        + Nouvelle Diffusion Multi-Numéros →
    </a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><span>Diffusions lancées</span><span class="stat-icon">◈</span></div>
        <div class="stat-value">{{ $broadcasts->total() }}</div>
        <div class="stat-note">Campagnes multi-expéditeurs</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Messages délivrés</span><span class="stat-icon">✉</span></div>
        <div class="stat-value" style="color:#188151">{{ number_format($totalSent, 0, ',', ' ') }}</div>
        <div class="stat-note">Via pool de numéros rotatifs</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Numéros actifs dans le pool</span><span class="stat-icon">📱</span></div>
        <div class="stat-value">{{ $activeNumbersCount }}</div>
        <div class="stat-note"><a href="{{ route('whatsapp-numbers.index') }}" style="color:var(--green);text-decoration:underline">Gérer les 10 slots</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Protection Anti-Ban</span><span class="stat-icon">🛡</span></div>
        <div class="stat-value" style="color:var(--green)">Active</div>
        <div class="stat-note">Rotation & Spintax dynamique</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Historique des Campagnes de Diffusion</div>
            <div class="panel-subtitle">Chaque campagne ventile la charge équitablement sur vos différents expéditeurs WhatsApp.</div>
        </div>
    </div>

    @if($broadcasts->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">🚀</div>
            <h3>Aucune diffusion enregistrée</h3>
            <p>Lancez votre première diffusion groupée en utilisant plusieurs numéros WhatsApp en simultané pour prospecter massivement sans risquer de bannissement.</p>
            <a class="btn btn-primary" href="{{ route('broadcasts.create') }}">+ Créer ma première diffusion</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titre de la diffusion</th>
                        <th>Audience ciblée</th>
                        <th>Numéros utilisés</th>
                        <th>Progression envois</th>
                        <th>Statut</th>
                        <th>Date de lancement</th>
                        <th style="text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($broadcasts as $b)
                    <tr>
                        <td>
                            <a href="{{ route('broadcasts.show', $b) }}" style="font-weight:700;color:var(--green);font-size:13px">
                                {{ $b->title }}
                            </a>
                            <div style="font-size:11px;color:var(--muted);margin-top:2px">
                                Délai anti-ban : {{ $b->anti_ban_delay_seconds }}s par message
                            </div>
                        </td>
                        <td>
                            @if($b->target_audience === 'scraped_leads')
                                <span class="pill pill-green">Prospects IA Scrappés</span>
                            @else
                                <span class="pill pill-yellow">Carnet de contacts</span>
                            @endif
                        </td>
                        <td>
                            <strong style="font-size:12px">{{ count($b->selected_account_ids ?? []) }} numéros</strong>
                            <div style="font-size:10px;color:var(--muted)">en rotation alternée</div>
                        </td>
                        <td>
                            <div style="font-weight:700;font-size:11px">
                                {{ $b->sent_count }} / {{ $b->total_recipients }} envoyés
                                @if($b->failed_count > 0)
                                    <span style="color:#b94438">({{ $b->failed_count }} échecs)</span>
                                @endif
                            </div>
                            <div class="progress" style="height:6px;width:120px;margin-top:4px">
                                @php $pct = $b->total_recipients > 0 ? min(100, round(($b->sent_count / $b->total_recipients) * 100)) : 0; @endphp
                                <span style="width:{{ $pct }}%"></span>
                            </div>
                        </td>
                        <td>
                            @if($b->status === 'completed')
                                <span class="pill pill-green">✓ Terminé</span>
                            @elseif($b->status === 'running')
                                <span class="pill pill-yellow">⚡ En cours</span>
                            @else
                                <span class="pill pill-gray">{{ $b->status_label }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:12px">{{ $b->created_at->format('d/m/Y') }}</div>
                            <div style="font-size:10px;color:var(--muted)">{{ $b->created_at->format('H:i') }}</div>
                        </td>
                        <td style="text-align:right">
                            <div style="display:inline-flex;gap:6px">
                                <a class="btn btn-light" href="{{ route('broadcasts.show', $b) }}" style="padding:6px 12px;font-size:11px">
                                    Détails & Logs →
                                </a>
                                <form method="POST" action="{{ route('broadcasts.destroy', $b) }}" onsubmit="return confirm('Supprimer cette diffusion ?');" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-light" style="padding:6px 9px;font-size:11px;color:#b94438">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:18px">
            {{ $broadcasts->links() }}
        </div>
    @endif
</div>
@endsection
