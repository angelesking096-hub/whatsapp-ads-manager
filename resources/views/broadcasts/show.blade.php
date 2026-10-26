@extends('layouts.app', ['title' => $broadcast->title])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">DÉTAIL DE LA DIFFUSION MULTI-NUMÉROS</div>
        <h1 class="page-title">{{ $broadcast->title }}</h1>
        <p class="subtitle">Lancée le {{ $broadcast->created_at->format('d/m/Y à H:i') }} · Audience : {{ $broadcast->target_audience === 'scraped_leads' ? 'Prospects IA Scrappés' : 'Contacts clients' }}</p>
    </div>
    <div style="display:flex;gap:10px">
        <a class="btn btn-light" href="{{ route('broadcasts.index') }}">← Retour à la liste</a>
        <a class="btn btn-primary" href="{{ route('inbox.index') }}">Voir les réponses (Boîte unifiée) →</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><span>Total destinataires</span><span class="stat-icon">👥</span></div>
        <div class="stat-value">{{ $broadcast->total_recipients }}</div>
        <div class="stat-note">Prévus pour cette campagne</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Envoyés avec succès</span><span class="stat-icon">✓</span></div>
        <div class="stat-value" style="color:#188151">{{ $broadcast->sent_count }}</div>
        <div class="stat-note">
            @php $pct = $broadcast->total_recipients > 0 ? round(($broadcast->sent_count / $broadcast->total_recipients) * 100) : 0; @endphp
            Taux de complétion : {{ $pct }}%
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Numéros WhatsApp mobilisés</span><span class="stat-icon">📱</span></div>
        <div class="stat-value">{{ $accounts->count() }}</div>
        <div class="stat-note">En rotation alternée</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Statut actuel</span><span class="stat-icon">⚡</span></div>
        <div class="stat-value" style="font-size:22px;color:var(--green)">{{ $broadcast->status_label }}</div>
        <div class="stat-note">Délai : {{ $broadcast->anti_ban_delay_seconds }}s entre messages</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Journal des Envois Individuels (Dispatches)</div>
            <div class="panel-subtitle">Chaque message est tracé avec le numéro expéditeur spécifique qui l'a transmis.</div>
        </div>
    </div>

    @if($dispatches->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">⏳</div>
            <h3>Envois en cours de traitement dans la file</h3>
            <p>Le worker d'arrière-plan traite les messages avec les délais anti-ban. Actualisez la page pour voir les dispatches apparaître.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Destinataire</th>
                        <th>Numéro Expéditeur Utilisé</th>
                        <th>Message Personnalisé Envoyé</th>
                        <th>Statut</th>
                        <th>Date & Heure</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dispatches as $d)
                    <tr>
                        <td>
                            <div style="font-weight:700">{{ $d->recipient_name ?: 'Contact' }}</div>
                            <div style="font-family:monospace;font-size:11px;color:var(--muted)">{{ $d->recipient_phone }}</div>
                        </td>
                        <td>
                            @if($d->whatsappAccount)
                                <div style="font-weight:700;font-size:12px;color:var(--green)">
                                    📱 {{ $d->whatsappAccount->account_name ?: 'Numéro #'.$d->whatsappAccount->slot_number }}
                                </div>
                                <div style="font-family:monospace;font-size:10px;color:var(--muted)">
                                    {{ $d->whatsappAccount->effective_phone_number }}
                                </div>
                            @else
                                <span style="color:var(--muted)">Numéro supprimé</span>
                            @endif
                        </td>
                        <td style="max-width:380px;white-space:normal">
                            <div style="font-size:11px;background:#f8faf9;border:1px solid var(--line);border-radius:6px;padding:8px;line-height:1.4">
                                {{ Str::limit($d->final_message, 140) }}
                            </div>
                        </td>
                        <td>
                            @if($d->status === 'sent')
                                <span class="pill pill-green">✓ Envoyé</span>
                            @elseif($d->status === 'queued')
                                <span class="pill pill-yellow">En attente</span>
                            @else
                                <span class="pill pill-red">Échec</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:11px">{{ $d->sent_at ? $d->sent_at->format('d/m H:i:s') : $d->created_at->format('d/m H:i:s') }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px">
            {{ $dispatches->links() }}
        </div>
    @endif
</div>
@endsection
