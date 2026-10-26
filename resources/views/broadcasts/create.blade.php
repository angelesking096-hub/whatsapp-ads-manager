@extends('layouts.app', ['title' => 'Nouvelle Diffusion Multi-Numéros'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">DIFFUSION SÉCURISÉE MULTI-EXPÉDITEURS</div>
        <h1 class="page-title">Nouvelle Diffusion Multi-Numéros</h1>
        <p class="subtitle">Répartissez automatiquement vos envois sur vos différents numéros WhatsApp pour multiplier vos volumes de prospection sans blocage.</p>
    </div>
    <a class="btn btn-light" href="{{ route('broadcasts.index') }}">← Retour aux diffusions</a>
</div>

<form method="POST" action="{{ route('broadcasts.store') }}">
    @csrf
    <div class="dashboard-grid">
        <!-- Main Form -->
        <div style="display:grid;gap:20px">
            <!-- Informations de base & Audience -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">1. Titre & Audience Cible</div>
                </div>
                <div style="display:grid;gap:16px">
                    <div>
                        <label class="field-label">Titre de la diffusion *</label>
                        <input class="field" type="text" name="title" placeholder="Ex: Prospection Agences Immobilières Douala - Offre Partenariat" value="{{ old('title') }}" required>
                    </div>

                    <div>
                        <label class="field-label">Sélectionner l'audience cible *</label>
                        <div class="form-grid" style="grid-template-columns:1fr 1fr">
                            <label style="border:2px solid var(--line);border-radius:12px;padding:16px;cursor:pointer;display:flex;gap:12px;align-items:flex-start" id="audience_scraped_label">
                                <input type="radio" name="target_audience" value="scraped_leads" style="margin-top:3px" {{ request('audience') === 'scraped_leads' ? 'checked' : '' }} onchange="updateAudienceSelection(this.value)">
                                <div>
                                    <strong style="font-size:13px;display:block">🎯 Prospects Scrappés par l'IA</strong>
                                    <span style="font-size:11px;color:var(--muted);display:block;margin-top:3px">
                                        {{ $scrapedLeadsCount }} prospect(s) qualifié(s) actuellement en base
                                    </span>
                                </div>
                            </label>

                            <label style="border:2px solid var(--line);border-radius:12px;padding:16px;cursor:pointer;display:flex;gap:12px;align-items:flex-start" id="audience_contacts_label">
                                <input type="radio" name="target_audience" value="all_contacts" style="margin-top:3px" {{ request('audience') !== 'scraped_leads' ? 'checked' : '' }} onchange="updateAudienceSelection(this.value)">
                                <div>
                                    <strong style="font-size:13px;display:block">👥 Contacts & Clients consentants</strong>
                                    <span style="font-size:11px;color:var(--muted);display:block;margin-top:3px">
                                        {{ $contactsCount }} contact(s) autorisés dans le carnet
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pool de Numéros Expéditeurs (Multi-WhatsApp) -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">2. Pool de Numéros Expéditeurs (Rotation Multi-WhatsApp)</div>
                        <div class="panel-subtitle">Cochez les numéros qui participeront à l'envoi de cette campagne.</div>
                    </div>
                    <button type="button" class="btn btn-light" style="padding:6px 12px;font-size:11px" onclick="selectAllAccounts()">
                        Tout cocher ({{ $accounts->count() }} numéros)
                    </button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px" id="accounts_grid">
                    @foreach($accounts as $acc)
                    <label style="border:1px solid var(--line);border-radius:10px;padding:12px;display:flex;align-items:center;gap:12px;cursor:pointer;background:#fff" class="account-item">
                        <input type="checkbox" name="selected_account_ids[]" value="{{ $acc->id }}" checked class="acc-checkbox" onchange="calculateDistribution()">
                        <div style="flex:1">
                            <div style="font-weight:700;font-size:12px">{{ $acc->account_name ?: 'Numéro #'.$loop->iteration }}</div>
                            <div style="font-family:monospace;font-size:11px;color:#188151">{{ $acc->effective_phone_number }}</div>
                            <div style="font-size:10px;color:var(--muted)">Quota restant: {{ $acc->remaining_daily_quota }} msgs</div>
                        </div>
                        <span class="pill pill-green" style="font-size:9px">Slot #{{ $acc->slot_number ?: $loop->iteration }}</span>
                    </label>
                    @endforeach
                </div>

                <div style="background:#eaf7f0;border-radius:10px;padding:12px 16px;margin-top:16px;display:flex;align-items:center;gap:12px" id="math-preview">
                    <span style="font-size:20px">⚖</span>
                    <div style="font-size:12px;color:#134430" id="distribution-text">
                        Sélectionnez vos numéros pour visualiser la répartition.
                    </div>
                </div>
            </div>

            <!-- Contenu du message avec Spintax & Variables -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">3. Message avec Spintax & Personnalisation IA</div>
                        <div class="panel-subtitle">Utilisez des balises dynamiques et la syntaxe Spintax {A|B|C} pour que chaque message envoyé soit 100% unique.</div>
                    </div>
                </div>

                <div style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);align-self:center">Variables :</span>
                    <button type="button" class="btn btn-light" style="padding:4px 9px;font-size:11px" onclick="insertTag('{nom}')">+ {nom}</button>
                    <button type="button" class="btn btn-light" style="padding:4px 9px;font-size:11px" onclick="insertTag('{entreprise}')">+ {entreprise}</button>
                    <button type="button" class="btn btn-light" style="padding:4px 9px;font-size:11px" onclick="insertTag('{ville}')">+ {ville}</button>
                    <button type="button" class="btn btn-light" style="padding:4px 9px;font-size:11px" onclick="insertTag('{besoin}')">+ {besoin (détecté par IA)}</button>
                    <button type="button" class="btn btn-light" style="padding:4px 9px;font-size:11px;color:var(--green)" onclick="insertSpintax()">+ Ajouter Spintax {Bonjour|Salut|Hello}</button>
                </div>

                <textarea class="field" name="message_template" id="message_template" rows="8" style="font-family:inherit;font-size:13px;line-height:1.6" required placeholder="{Bonjour|Salut|Hello} {nom},

J'ai découvert votre activité {entreprise} à {ville}. En analysant votre présence, j'ai remarqué que vous pourriez grandement bénéficier de : {besoin}.

Auriez-vous 5 minutes cette semaine pour en discuter ?

Bien cordialement,">{Bonjour|Hello|Salut} {nom},

J'ai remarqué votre activité {entreprise} à {ville}. En étudiant vos services, nous avons identifié une forte opportunité pour : {besoin}.

Seriez-vous ouvert à échanger 5 minutes sur WhatsApp à ce sujet ?

Bien cordialement,</textarea>
            </div>
        </div>

        <!-- Sidebar / Anti-ban Settings & Submit -->
        <div style="display:grid;gap:20px;align-content:start">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">🛡 Sécurité & Anti-Ban</div>
                </div>
                <div style="display:grid;gap:16px">
                    <div>
                        <label class="field-label">Délai aléatoire entre chaque envoi *</label>
                        <select class="select" name="anti_ban_delay_seconds">
                            <option value="15">15 à 25 secondes (Rapide)</option>
                            <option value="25" selected>25 à 45 secondes (Recommandé)</option>
                            <option value="45">45 à 60 secondes (Sécurité maximale)</option>
                        </select>
                        <div class="form-help">Ralentit le rythme d'émission pour imiter un comportement humain naturel.</div>
                    </div>

                    <div>
                        <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                            <input type="checkbox" name="enable_spintax" value="1" checked>
                            <span style="font-size:12px;font-weight:700">Activer le moteur Spintax</span>
                        </label>
                        <div class="form-help">Permet de varier aléatoirement chaque mot entre {accolades|séparées|par barres}.</div>
                    </div>

                    <div style="background:#fffbef;border:1px solid #f0dfb8;border-radius:10px;padding:12px;font-size:11px;color:#745b1c;line-height:1.5">
                        <strong>Protection garantie :</strong> En répartissant vos messages sur vos différents numéros avec des délais et des variations de texte, vous réduisez de 95% le risque de blocage par WhatsApp.
                    </div>
                </div>
            </div>

            <div class="panel" style="background:#fcfefd;border-color:#b4decb">
                <div class="panel-title" style="margin-bottom:12px">Récapitulatif & Lancement</div>
                <p style="font-size:11px;color:var(--muted);line-height:1.5;margin-bottom:16px">
                    Une fois lancée, la campagne s'exécute en arrière-plan via la file de traitement. Vous pourrez suivre la progression en temps réel.
                </p>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:14px">
                    🚀 Lancer la Diffusion Multi-Numéros
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function insertTag(tag) {
    var txt = document.getElementById('message_template');
    var start = txt.selectionStart;
    var end = txt.selectionEnd;
    txt.value = txt.value.substring(0, start) + tag + txt.value.substring(end);
    txt.focus();
}

function insertSpintax() {
    insertTag('{Bonjour|Salut|Hello|Bonjour à vous}');
}

function selectAllAccounts() {
    var boxes = document.querySelectorAll('.acc-checkbox');
    boxes.forEach(function(b) { b.checked = true; });
    calculateDistribution();
}

function calculateDistribution() {
    var checkedCount = document.querySelectorAll('.acc-checkbox:checked').length;
    var audienceRadio = document.querySelector('input[name="target_audience"]:checked');
    var isScraped = audienceRadio ? audienceRadio.value === 'scraped_leads' : false;
    var totalAudience = isScraped ? {{ $scrapedLeadsCount }} : {{ $contactsCount }};

    var text = document.getElementById('distribution-text');
    if (checkedCount === 0) {
        text.innerHTML = '<strong style="color:#b94438">Attention : veuillez sélectionner au moins 1 numéro expéditeur.</strong>';
    } else {
        var perNumber = totalAudience > 0 ? Math.ceil(totalAudience / checkedCount) : 0;
        text.innerHTML = '<strong>' + totalAudience + ' destinataires</strong> répartis sur <strong>' + checkedCount + ' numéro(s) WhatsApp</strong> ≈ environ <strong>' + perNumber + ' messages par numéro</strong>.';
    }
}

function updateAudienceSelection() {
    calculateDistribution();
}

document.addEventListener('DOMContentLoaded', function() {
    calculateDistribution();
});
</script>
@endsection
