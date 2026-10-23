<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\CampaignDelivery;
use App\Jobs\SendWhatsAppTemplateJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    private function tenant(Request $request) { $tenant = $request->user()->currentTenant(); abort_unless($tenant, 403); return $tenant; }
    public function index(Request $request)
    {
        $tenant = $this->tenant($request);
        $query = Campaign::where('tenant_id', $tenant->id);
        if (in_array($request->query('channel'), ['private_message','status','group'], true)) $query->where('channel', $request->query('channel'));
        $campaigns = $query->latest()->paginate(12)->withQueryString();
        return view('campaigns.index', compact('campaigns', 'tenant'));
    }
    public function create(Request $request) { $tenant = $this->tenant($request); return view('campaigns.create', compact('tenant')); }
    public function store(Request $request)
    {
        $tenant = $this->tenant($request);
        $data = $request->validate([
            'name' => ['required','string','max:160'], 'channel' => ['required', Rule::in(['private_message','status','group'])],
            'content' => ['required','string','max:10000'], 'template_name' => ['nullable','string','max:160','regex:/^[a-z0-9_]+$/'], 'scheduled_at' => ['nullable','date','after_or_equal:now'],
        ]);
        if ($data['channel']==='private_message' && !empty($data['scheduled_at']) && empty($data['template_name'])) { return back()->withErrors(['template_name'=>'Le nom du modèle WhatsApp approuvé est requis pour programmer un envoi.'])->withInput(); }
        $channel = $data['channel'];
        // Status and group auto-publication are not assumed to be supported by the standard Cloud API.
        $status = in_array($channel, ['status','group'], true) ? 'manual_required' : (!empty($data['scheduled_at']) ? 'scheduled' : 'draft');
        $campaign = Campaign::create($data + ['tenant_id' => $tenant->id, 'user_id' => $request->user()->id, 'status' => $status]);
        return redirect()->route('campaigns.index')->with('success', $status === 'manual_required' ? 'Campagne enregistrée. La publication sur ce canal nécessite une vérification ou une action manuelle.' : 'Campagne enregistrée. L’envoi automatique sera activé après configuration du connecteur WhatsApp.');
    }
    public function show(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->tenant_id === $this->tenant($request)->id, 404);
        return view('campaigns.show', compact('campaign'));
    }
    public function edit(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->tenant_id === $this->tenant($request)->id, 404);
        return view('campaigns.edit', ['campaign' => $campaign, 'tenant' => $this->tenant($request)]);
    }
    public function update(Request $request, Campaign $campaign)
    {
        $tenant = $this->tenant($request); abort_unless($campaign->tenant_id === $tenant->id, 404);
        $data = $request->validate(['name'=>['required','string','max:160'],'channel'=>['required',Rule::in(['private_message','status','group'])],'content'=>['required','string','max:10000'],'template_name'=>['nullable','string','max:160','regex:/^[a-z0-9_]+$/'],'scheduled_at'=>['nullable','date']]);
        if ($data['channel']==='private_message' && !empty($data['scheduled_at']) && empty($data['template_name'])) { return back()->withErrors(['template_name'=>'Le nom du modèle WhatsApp approuvé est requis pour programmer un envoi.'])->withInput(); }
        $data['status'] = in_array($data['channel'], ['status','group'], true) ? 'manual_required' : (!empty($data['scheduled_at']) ? 'scheduled' : 'draft');
        $campaign->update($data);
        return redirect()->route('campaigns.index')->with('success', 'Campagne mise à jour.');
    }
    public function launch(Request $request, Campaign $campaign)
    {
        $tenant=$this->tenant($request); abort_unless($campaign->tenant_id===$tenant->id,404);
        if ($campaign->channel !== 'private_message') return back()->with('warning','Ce canal ne peut pas être lancé automatiquement par ce connecteur.');
        if (!$campaign->template_name || !preg_match('/^[a-z0-9_]+$/',$campaign->template_name)) return back()->withErrors(['template_name'=>'Renseigne le nom exact d’un modèle WhatsApp approuvé dans Meta.']);
        $account=$tenant->whatsappAccounts()->where('status','configured')->first();
        if (!$account || !$account->phone_number_id || !$account->access_token) return back()->with('warning','Configure d’abord la connexion WhatsApp dans Paramètres.');
        $contacts=Contact::where('tenant_id',$tenant->id)->reachable()->get(['id']);
        if ($contacts->isEmpty()) return back()->with('warning','Aucun contact avec consentement actif. Aucun message n’a été envoyé.');
        $queued=0;
        foreach ($contacts as $contact) {
            $delivery=CampaignDelivery::firstOrCreate(['campaign_id'=>$campaign->id,'contact_id'=>$contact->id],['status'=>'queued']);
            if ($delivery->wasRecentlyCreated || $delivery->status==='failed') { $delivery->update(['status'=>'queued','error_message'=>null]); SendWhatsAppTemplateJob::dispatch($delivery->id)->delay(now()->addSeconds($queued)); $queued++; }
        }
        $campaign->update(['status'=>'queued']);
        return back()->with('success',"{$queued} envoi(s) placé(s) dans la file pour des contacts autorisés. Le résultat dépend du worker, des modèles approuvés et de l’API Meta.");
    }
    public function destroy(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->tenant_id === $this->tenant($request)->id, 404); $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campagne supprimée.');
    }
}
