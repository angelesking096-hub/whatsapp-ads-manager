@extends('layouts.app', ['title' => 'Mon Abonnement & Quotas'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">GESTION DU COMPTE SAAS</div>
        <h1 class="page-title">Mon Forfait & Quotas de Consommation</h1>
        <p class="subtitle">Gérez vos limites de numéros WhatsApp connectables, vos crédits de scraping IA et vos volumes de diffusion autorisés.</p>
    </div>
</div>

<!-- Quotas en cours -->
<div class="panel" style="margin-bottom:24px">
    <div class="panel-header">
        <div>
            <div class="panel-title">Consommation & Quotas de votre Espace Entreprise</div>
            <div class="panel-subtitle">Forfait actif : <strong style="color:var(--green);text-transform:uppercase">{{ $tenant->subscription_plan ?: 'Pro' }}</strong></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">
        <!-- Numéros WhatsApp -->
        <div style="background:#f8faf9;border:1px solid var(--line);border-radius:12px;padding:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <span style="font-weight:700;font-size:13px">📱 Numéros WhatsApp</span>
                <span class="pill pill-green">{{ $tenant->whatsappAccounts()->count() }} / {{ $tenant->max_whatsapp_numbers }} slots</span>
            </div>
            <div class="progress" style="height:8px;margin-bottom:10px">
                @php $numPct = $tenant->max_whatsapp_numbers > 0 ? min(100, round(($tenant->whatsappAccounts()->count() / $tenant->max_whatsapp_numbers) * 100)) : 0; @endphp
                <span style="width:{{ $numPct }}%"></span>
            </div>
            <div style="font-size:11px;color:var(--muted)">
                Il vous reste <strong>{{ max(0, $tenant->max_whatsapp_numbers - $tenant->whatsappAccounts()->count()) }} slot(s)</strong> de numéros connectables.
            </div>
        </div>

        <!-- Scraping IA -->
        <div style="background:#f8faf9;border:1px solid var(--line);border-radius:12px;padding:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <span style="font-weight:700;font-size:13px">🎯 Scraping IA Mensuel</span>
                <span class="pill pill-green">{{ $tenant->scraping_used_this_month }} / {{ $tenant->monthly_scraping_quota }} leads</span>
            </div>
            <div class="progress" style="height:8px;margin-bottom:10px">
                @php $scrapPct = $tenant->monthly_scraping_quota > 0 ? min(100, round(($tenant->scraping_used_this_month / $tenant->monthly_scraping_quota) * 100)) : 0; @endphp
                <span style="width:{{ $scrapPct }}%"></span>
            </div>
            <div style="font-size:11px;color:var(--muted)">
                Il vous reste <strong>{{ $tenant->remaining_scraping_quota }} prospect(s)</strong> qualifiables ce mois-ci.
            </div>
        </div>

        <!-- Diffusions Journalières -->
        <div style="background:#f8faf9;border:1px solid var(--line);border-radius:12px;padding:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <span style="font-weight:700;font-size:13px">🚀 Diffusions Journalières</span>
                <span class="pill pill-green">{{ $tenant->daily_broadcast_quota }} msgs/j max</span>
            </div>
            <div class="progress" style="height:8px;margin-bottom:10px">
                <span style="width:25%"></span>
            </div>
            <div style="font-size:11px;color:var(--muted)">
                Volume maximal garanti pour la sécurité anti-ban.
            </div>
        </div>
    </div>
</div>

<!-- Comparatif des Forfaits Disponibles -->
<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Changer de Formule d'Abonnement</div>
            <div class="panel-subtitle">Sélectionnez le plan adapté à vos ambitions de prospection et débloquez jusqu'à 10 numéros en simultané.</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">
        @foreach($plans as $key => $plan)
        @php $isCurrent = ($tenant->subscription_plan ?: 'pro') === $key; @endphp
        <div style="border:2px solid {{ $isCurrent ? 'var(--green)' : 'var(--line)' }};border-radius:16px;padding:24px;background:{{ $isCurrent ? '#f9fdfb' : '#fff' }};position:relative;display:flex;flex-direction:column;justify-content:space-between">
            @if($isCurrent)
                <div style="position:absolute;top:-12px;right:20px;background:var(--green);color:#fff;font-size:10px;font-weight:800;padding:4px 12px;border-radius:20px;letter-spacing:1px">
                    VOTRE FORFAIT ACTUEL
                </div>
            @endif

            <div>
                <h3 style="font-size:18px;margin:0 0 6px">{{ $plan['name'] }}</h3>
                <div style="font-family:'Manrope';font-size:24px;font-weight:800;color:var(--green);margin-bottom:16px">
                    {{ $plan['price'] }}
                </div>

                <div style="border-top:1px solid var(--line);padding-top:16px;margin-bottom:20px">
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:10px;font-size:12px;color:#3e5249">
                        @foreach($plan['features'] as $f)
                            <li style="display:flex;align-items:center;gap:8px">
                                <span style="color:#188151;font-weight:800">✓</span>
                                {{ $f }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div>
                @if($isCurrent)
                    <button class="btn btn-light" style="width:100%;background:#eaf7f0;border-color:#bce5d0;color:#188151;font-weight:700" disabled>
                        ✓ Forfait en cours
                    </button>
                @else
                    <form method="POST" action="{{ route('subscription.switch') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $key }}">
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            Passer à la formule {{ $plan['name'] }} →
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
