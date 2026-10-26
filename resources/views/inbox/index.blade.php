@extends('layouts.app', ['title' => 'Boîte de Réception Unifiée'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">CRM & CONVERSATIONS EN DIRECT</div>
        <h1 class="page-title">Boîte de Réception Unifiée Multi-Numéros</h1>
        <p class="subtitle">Centralisez et répondez aux messages reçus sur l'ensemble de vos 10 numéros WhatsApp au même endroit.</p>
    </div>
    <!-- Filter by Account -->
    <form method="GET" action="{{ route('inbox.index') }}" style="display:flex;gap:10px;align-items:center">
        <select class="select" name="account_id" onchange="this.form.submit()" style="min-width:220px">
            <option value="">📱 Tous les numéros confondus ({{ $accounts->count() }})</option>
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ $filterAccount == $acc->id ? 'selected' : '' }}>
                    Slot #{{ $acc->slot_number ?: $loop->iteration }} - {{ $acc->account_name }} ({{ $acc->effective_phone_number }})
                </option>
            @endforeach
        </select>
    </form>
</div>

<!-- Chat Interface Layout -->
<div style="display:grid;grid-template-columns:360px 1fr;background:#fff;border:1px solid var(--line);border-radius:16px;min-height:640px;overflow:hidden">
    <!-- Left Pane: Conversations List -->
    <div style="border-right:1px solid var(--line);display:flex;flex-direction:column;background:#fcfdfc">
        <div style="padding:16px;border-bottom:1px solid var(--line);background:#fff">
            <strong style="font-size:13px">Conversations Récentes</strong>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">{{ $threads->count() }} contact(s) actif(s)</div>
        </div>

        <div style="flex:1;overflow-y:auto">
            @if($threads->isEmpty())
                <div style="padding:40px 20px;text-align:center;color:var(--muted)">
                    <div style="font-size:28px;margin-bottom:8px">💬</div>
                    <div style="font-size:12px;font-weight:700;color:var(--ink)">Aucun message pour le moment</div>
                    <div style="font-size:11px;margin-top:4px;line-height:1.4">Les réponses de vos prospects à vos diffusions apparaîtront ici automatiquement.</div>
                </div>
            @else
                @foreach($threads as $thread)
                <a href="{{ route('inbox.index', ['phone' => $thread['phone'], 'account_id' => $filterAccount]) }}" 
                   style="display:block;padding:14px 16px;border-bottom:1px solid #f0f4f1;transition:.15s;text-decoration:none;color:inherit;{{ $selectedPhone === $thread['phone'] ? 'background:#eaf7f0;border-left:4px solid var(--green);' : '' }}">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                        <strong style="font-size:13px;color:#132a25">{{ $thread['name'] }}</strong>
                        <span style="font-size:10px;color:var(--muted)">{{ $thread['last_time']->format('H:i') }}</span>
                    </div>
                    <div style="font-size:11px;color:var(--muted);font-family:monospace;margin-bottom:4px">
                        {{ $thread['phone'] }}
                    </div>
                    <div style="font-size:12px;color:#52645b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        {{ $thread['last_message'] }}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                        @if($thread['account'])
                            <span class="pill pill-green" style="font-size:9px;padding:2px 7px">
                                📱 {{ $thread['account']->account_name ?: 'Numéro #'.$thread['account']->slot_number }}
                            </span>
                        @endif
                        @if($thread['unread_count'] > 0)
                            <span style="background:#188151;color:#fff;border-radius:10px;padding:2px 7px;font-size:10px;font-weight:800">
                                {{ $thread['unread_count'] }}
                            </span>
                        @endif
                    </div>
                </a>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Right Pane: Active Thread Chat -->
    <div style="display:flex;flex-direction:column;background:#f7f9f8">
        @if($selectedPhone)
            <!-- Chat Header -->
            <div style="padding:14px 20px;border-bottom:1px solid var(--line);background:#fff;display:flex;justify-content:space-between;align-items:center">
                <div>
                    <strong style="font-size:14px;color:#132a25">{{ $activeContact?->name ?: $selectedPhone }}</strong>
                    <div style="font-size:11px;color:var(--muted);font-family:monospace">{{ $selectedPhone }}</div>
                </div>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$selectedPhone) }}" target="_blank" rel="noopener" class="btn btn-light" style="padding:6px 12px;font-size:11px;color:#188151">
                    💬 Ouvrir dans WhatsApp
                </a>
            </div>

            <!-- Messages Stream -->
            <div style="flex:1;padding:20px;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
                @foreach($activeMessages as $msg)
                    @if($msg->direction === 'inbound')
                        <!-- Inbound message from prospect -->
                        <div style="align-self:flex-start;max-width:70%;background:#fff;border:1px solid var(--line);border-radius:14px 14px 14px 2px;padding:12px 16px;box-shadow:0 1px 2px rgba(0,0,0,0.04)">
                            <div style="font-size:10px;font-weight:700;color:var(--muted);margin-bottom:4px">
                                {{ $msg->contact_name ?: $msg->phone_number }}
                            </div>
                            <div style="font-size:13px;line-height:1.5;color:#132a25">{{ $msg->message }}</div>
                            <div style="font-size:9px;color:var(--muted);text-align:right;margin-top:6px">{{ $msg->created_at->format('H:i') }}</div>
                        </div>
                    @else
                        <!-- Outbound reply from our multi-number -->
                        <div style="align-self:flex-end;max-width:70%;background:#eaf7f0;border:1px solid #c8ebd8;border-radius:14px 14px 2px 14px;padding:12px 16px;box-shadow:0 1px 2px rgba(0,0,0,0.04)">
                            <div style="font-size:10px;font-weight:700;color:var(--green);margin-bottom:4px">
                                Envoyé via : {{ $msg->whatsappAccount?->account_name ?: 'Numéro #'.$msg->whatsappAccount?->slot_number }} ({{ $msg->whatsappAccount?->effective_phone_number }})
                            </div>
                            <div style="font-size:13px;line-height:1.5;color:#134430">{{ $msg->message }}</div>
                            <div style="font-size:9px;color:#188151;text-align:right;margin-top:6px">
                                {{ $msg->created_at->format('H:i') }} · ✓ Envoyé
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Chat Reply Form -->
            <div style="padding:16px 20px;border-top:1px solid var(--line);background:#fff">
                <form method="POST" action="{{ route('inbox.reply') }}">
                    @csrf
                    <input type="hidden" name="phone_number" value="{{ $selectedPhone }}">
                    <input type="hidden" name="contact_name" value="{{ $activeContact?->name }}">
                    <div style="display:flex;gap:12px;margin-bottom:10px;align-items:center">
                        <span style="font-size:11px;font-weight:700;color:var(--muted)">Répondre depuis le numéro :</span>
                        <select class="select" name="whatsapp_account_id" style="width:auto;padding:6px 12px;font-size:11px">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->account_name }} ({{ $acc->effective_phone_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex;gap:10px">
                        <input class="field" type="text" name="message" placeholder="Écrivez votre réponse ici..." required autocomplete="off" style="padding:12px">
                        <button type="submit" class="btn btn-primary" style="padding:0 22px">
                            Envoyer ➔
                        </button>
                    </div>
                </form>
            </div>
        @else
            <!-- No thread selected -->
            <div style="margin:auto;text-align:center;padding:40px;color:var(--muted)">
                <div style="font-size:42px;margin-bottom:12px">📥</div>
                <h3 style="font-size:16px;color:var(--ink);margin:0 0 6px">Sélectionnez une conversation</h3>
                <p style="font-size:12px;max-width:320px;line-height:1.5;margin:0 auto">
                    Cliquez sur un contact dans la liste à gauche pour consulter son historique et lui répondre directement avec le numéro de votre choix.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
