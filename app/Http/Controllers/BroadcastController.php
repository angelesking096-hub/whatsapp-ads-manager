<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteMultiNumberBroadcastJob;
use App\Models\Contact;
use App\Models\MultiNumberBroadcast;
use App\Models\ScrapedLead;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    private function tenant(Request $request)
    {
        $tenant = $request->user()->currentTenant();
        abort_unless($tenant, 403);
        return $tenant;
    }

    public function index(Request $request)
    {
        $tenant = $this->tenant($request);
        $broadcasts = MultiNumberBroadcast::where('tenant_id', $tenant->id)
            ->withCount('dispatches')
            ->latest()
            ->paginate(10);

        $activeNumbersCount = $tenant->whatsappAccounts()->where('is_active', true)->count();
        $totalSent = MultiNumberBroadcast::where('tenant_id', $tenant->id)->sum('sent_count');

        return view('broadcasts.index', compact('tenant', 'broadcasts', 'activeNumbersCount', 'totalSent'));
    }

    public function create(Request $request)
    {
        $tenant = $this->tenant($request);
        $accounts = $tenant->whatsappAccounts()->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            return redirect()->route('whatsapp-numbers.index')
                ->with('warning', 'Ajoutez et activez au moins un numéro WhatsApp avant de pouvoir lancer une diffusion multi-numéros.');
        }

        $contactsCount = Contact::where('tenant_id', $tenant->id)->reachable()->count();
        $scrapedLeadsCount = ScrapedLead::where('tenant_id', $tenant->id)->count();

        return view('broadcasts.create', compact('tenant', 'accounts', 'contactsCount', 'scrapedLeadsCount'));
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'target_audience' => ['required', 'in:all_contacts,scraped_leads'],
            'message_template' => ['required', 'string', 'max:5000'],
            'selected_account_ids' => ['required', 'array', 'min:1'],
            'selected_account_ids.*' => ['integer', 'exists:whatsapp_accounts,id'],
            'anti_ban_delay_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'enable_spintax' => ['nullable', 'boolean'],
        ]);

        $recipientCount = $data['target_audience'] === 'scraped_leads'
            ? ScrapedLead::where('tenant_id', $tenant->id)->count()
            : Contact::where('tenant_id', $tenant->id)->reachable()->count();

        if ($recipientCount === 0) {
            return back()->with('warning', "Aucun destinataire trouvé dans l'audience sélectionnée.")->withInput();
        }

        $broadcast = MultiNumberBroadcast::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'message_template' => $data['message_template'],
            'selected_account_ids' => $data['selected_account_ids'],
            'target_audience' => $data['target_audience'],
            'total_recipients' => $recipientCount,
            'anti_ban_delay_seconds' => $data['anti_ban_delay_seconds'],
            'enable_spintax' => $request->boolean('enable_spintax', true),
            'status' => 'draft',
        ]);

        // Launch job immediately
        ExecuteMultiNumberBroadcastJob::dispatch($broadcast->id);

        $numbersUsed = count($data['selected_account_ids']);
        return redirect()->route('broadcasts.show', $broadcast)
            ->with('success', "🚀 Diffusion lancée ! Les {$recipientCount} messages sont en cours de distribution en rotation sur vos {$numbersUsed} numéros WhatsApp.");
    }

    public function show(Request $request, MultiNumberBroadcast $broadcast)
    {
        $tenant = $this->tenant($request);
        abort_unless($broadcast->tenant_id === $tenant->id, 403);

        $dispatches = $broadcast->dispatches()->with('whatsappAccount')->latest()->paginate(25);
        $accounts = $tenant->whatsappAccounts()->whereIn('id', $broadcast->selected_account_ids ?? [])->get();

        return view('broadcasts.show', compact('broadcast', 'dispatches', 'accounts', 'tenant'));
    }

    public function destroy(Request $request, MultiNumberBroadcast $broadcast)
    {
        $tenant = $this->tenant($request);
        abort_unless($broadcast->tenant_id === $tenant->id, 403);

        $broadcast->delete();
        return redirect()->route('broadcasts.index')->with('success', 'Diffusion supprimée.');
    }
}
