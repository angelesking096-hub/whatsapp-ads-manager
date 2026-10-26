@extends('layouts.app', ['title' => 'Pool Multi-Numéros WhatsApp'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">INFRASTRUCTURE MULTI-COMPTES</div>
        <h1 class="page-title">Pool Multi-Numéros WhatsApp</h1>
        <p class="subtitle">Enregistrez jusqu'à {{ $maxNumbers }} numéros WhatsApp pour répartir vos envois en rotation automatique et éviter les bannissements.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modal-add-number').style.display='flex'">
        + Ajouter un numéro (Slot {{ $accounts->count() + 1 }}/{{ $maxNumbers }})
    </button>
</div>

<!-- Quota & slots overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top"><span>Numéros enregistrés</span><span class="stat-icon">📱</span></div>
        <div class="stat-value">{{ $accounts->count() }} <small style="font-size:14px;color:var(--muted)">/ {{ $maxNumbers }}</small></div>
        <div class="stat-note">{{ $maxNumbers - $accounts->count() }} slots encore disponibles</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Numéros actifs en rotation</span><span class="stat-icon">⚡</span></div>
        <div class="stat-value" style="color:#188151">{{ $accounts->where('is_active', true)->count() }}</div>
        <div class="stat-note">Prêts pour la diffusion groupée</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Capacité d'envoi journalière</span><span class="stat-icon">🛡</span></div>
        <div class="stat-value">{{ number_format($accounts->sum('daily_limit'), 0, ',', ' ') }}</div>
        <div class="stat-note">Messages max répartis par jour</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Envoyés aujourd'hui</span><span class="stat-icon">✉</span></div>
        <div class="stat-value">{{ number_format($accounts->sum('sent_today'), 0, ',', ' ') }}</div>
        <div class="stat-note">Consommation du jour</div>
    </div>
</div>

<!-- Slots list -->
<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Slots WhatsApp Connectés</div>
            <div class="panel-subtitle">Chaque numéro agit comme un expéditeur autonome dans vos campagnes de prospection en rotation.</div>
        </div>
        <a class="btn btn-light" href="{{ route('broadcasts.create') }}">Lancer une diffusion multi-numéros →</a>
    </div>

    @if($accounts->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">📱</div>
            <h3>Aucun numéro connecté</h3>
            <p>Connectez votre premier numéro pour démarrer vos diffusions multi-expéditeurs et sécuriser vos campagnes.</p>
            <button class="btn btn-primary" onclick="document.getElementById('modal-add-number').style.display='flex'">
                + Enregistrer mon premier numéro
            </button>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Slot</th>
                        <th>Nom & Libellé</th>
                        <th>Numéro de téléphone</th>
                        <th>Type de connexion</th>
                        <th>Quota journalier</th>
                        <th>Qualité réputation</th>
                        <th>Statut rotation</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $acc)
                    <tr>
                        <td>
                            <strong style="color:var(--green);font-size:13px">#{{ $acc->slot_number ?: $loop->iteration }}</strong>
                        </td>
                        <td>
                            <div style="font-weight:700">{{ $acc->account_name ?: 'Numéro #'.$loop->iteration }}</div>
                            <div style="font-size:10px;color:var(--muted)">Ajouté le {{ $acc->created_at->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <span style="font-family:monospace;font-weight:700;font-size:13px;color:#132a25">
                                {{ $acc->effective_phone_number }}
                            </span>
                        </td>
                        <td>
                            @if($acc->connection_type === 'cloud_api')
                                <span class="pill pill-green">Meta Cloud API</span>
                            @else
                                <span class="pill pill-yellow">Multi-Session Web</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:700;font-size:11px">
                                {{ $acc->sent_today }} / {{ $acc->daily_limit }} msgs
                            </div>
                            <div class="progress" style="height:5px;width:110px;margin-top:4px">
                                @php $pct = $acc->daily_limit > 0 ? min(100, round(($acc->sent_today / $acc->daily_limit) * 100)) : 0; @endphp
                                <span style="width:{{ $pct }}%;background:{{ $pct > 80 ? '#b94438' : '#21966d' }}"></span>
                            </div>
                        </td>
                        <td>
                            @if($acc->quality_rating === 'GREEN')
                                <span class="pill pill-green">● Excellente</span>
                            @elseif($acc->quality_rating === 'YELLOW')
                                <span class="pill pill-yellow">● Moyenne</span>
                            @else
                                <span class="pill pill-red">● Surveillance</span>
                            @endif
                        </td>
                        <td>
                            @if($acc->is_active)
                                <span class="pill pill-green">✓ Actif en rotation</span>
                            @else
                                <span class="pill pill-gray">⏸ En pause</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <div style="display:inline-flex;gap:6px">
                                <form method="POST" action="{{ route('whatsapp-numbers.toggle', $acc) }}" style="display:inline">
                                    @csrf
                                    <button class="btn btn-light" style="padding:6px 10px;font-size:11px" title="{{ $acc->is_active ? 'Mettre en pause' : 'Réactiver' }}">
                                        {{ $acc->is_active ? 'Pause' : 'Activer' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('whatsapp-numbers.destroy', $acc) }}" onsubmit="return confirm('Supprimer ce numéro du pool ?');" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" style="padding:6px 10px;font-size:11px" title="Supprimer">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Modal Ajout de Numéro -->
<div id="modal-add-number" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:100;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:16px;max-width:550px;width:100%;padding:26px;box-shadow:0 20px 40px rgba(0,0,0,0.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h3 style="margin:0;font-size:18px">Ajouter un numéro WhatsApp au pool</h3>
            <button onclick="document.getElementById('modal-add-number').style.display='none'" style="border:0;background:transparent;font-size:20px;cursor:pointer">×</button>
        </div>
        <form method="POST" action="{{ route('whatsapp-numbers.store') }}">
            @csrf
            <div style="display:grid;gap:14px">
                <div>
                    <label class="field-label">Nom / Libellé interne du numéro *</label>
                    <input class="field" type="text" name="account_name" placeholder="Ex: Ligne Commerciale 1, Support, Ligne Prospection" required>
                </div>
                <div>
                    <label class="field-label">Numéro de téléphone (avec indicatif pays) *</label>
                    <input class="field" type="text" name="phone_number" placeholder="Ex: +237699000000 ou +33612345678" required>
                    <div class="form-help">Utilisez le format international (+ suivi de l'indicatif sans espace).</div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="field-label">Type de connexion *</label>
                        <select class="select" name="connection_type" id="conn_type" onchange="toggleApiFields(this.value)">
                            <option value="qr_session">Multi-Session Web (QR Code / SIM)</option>
                            <option value="cloud_api">Meta Cloud API Officielle</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Limite journalière anti-ban</label>
                        <input class="field" type="number" name="daily_limit" value="250" min="10" max="2000">
                    </div>
                </div>

                <!-- Champs conditionnels Cloud API -->
                <div id="cloud-api-fields" style="display:none;background:#f8faf9;border:1px solid var(--line);border-radius:10px;padding:14px">
                    <div style="margin-bottom:10px">
                        <label class="field-label">Phone Number ID (Meta)</label>
                        <input class="field" type="text" name="phone_number_id" placeholder="Ex: 104928374829102">
                    </div>
                    <div>
                        <label class="field-label">Jeton d'accès permanent (Access Token)</label>
                        <input class="field" type="password" name="access_token" placeholder="EAABw...">
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px">
                    <button type="button" class="btn btn-light" onclick="document.getElementById('modal-add-number').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer dans le pool →</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleApiFields(val) {
    var el = document.getElementById('cloud-api-fields');
    if (el) el.style.display = (val === 'cloud_api') ? 'block' : 'none';
}
</script>
@endsection
