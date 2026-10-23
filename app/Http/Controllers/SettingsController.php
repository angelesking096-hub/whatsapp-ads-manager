<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class SettingsController extends Controller
{
    public function index(Request $request) { $tenant=$request->user()->currentTenant(); abort_unless($tenant,403); $account=$tenant->whatsappAccounts()->latest()->first(); return view('dashboard.settings',compact('tenant','account')); }
    public function updateCompany(Request $request) { $tenant=$request->user()->currentTenant(); abort_unless($tenant,403); $data=$request->validate(['name'=>['required','string','max:160']]); $tenant->update($data); return back()->with('success','Nom de l’espace entreprise mis à jour.'); }
    public function saveWhatsApp(Request $request) {
        $tenant=$request->user()->currentTenant(); abort_unless($tenant,403);
        $data=$request->validate(['display_phone_number'=>['required','string','max:40'],'phone_number_id'=>['required','string','max:120'],'business_account_id'=>['nullable','string','max:120'],'access_token'=>['required','string','min:20','max:5000']]);
        $account=$tenant->whatsappAccounts()->first();
        $payload=$data+['status'=>'configured'];
        if ($account) { $account->update($payload); } else { $tenant->whatsappAccounts()->create($payload); }
        return back()->with('success','Informations WhatsApp enregistrées. Le jeton est chiffré en base ; vérifie les permissions Meta avant tout envoi.');
    }
    public function deleteWhatsApp(Request $request) { $tenant=$request->user()->currentTenant(); abort_unless($tenant,403); $tenant->whatsappAccounts()->delete(); return back()->with('success','Connexion WhatsApp supprimée.'); }
}
