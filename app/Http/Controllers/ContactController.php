<?php
namespace App\Http\Controllers;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ContactController extends Controller
{
    private function tenant(Request $request) { $tenant=$request->user()->currentTenant(); abort_unless($tenant,403); return $tenant; }
    public function index(Request $request) { $tenant=$this->tenant($request); $contacts=Contact::where('tenant_id',$tenant->id)->latest()->paginate(20); $stats=['all'=>Contact::where('tenant_id',$tenant->id)->count(),'consented'=>Contact::where('tenant_id',$tenant->id)->reachable()->count(),'unsubscribed'=>Contact::where('tenant_id',$tenant->id)->whereNotNull('unsubscribed_at')->count()]; return view('contacts.index',compact('tenant','contacts','stats')); }
    public function store(Request $request) {
        $tenant=$this->tenant($request);
        $data=$request->validate(['name'=>['nullable','string','max:120'],'phone'=>['required','string','max:32','regex:/^\+?[0-9 ()-]{7,32}$/'],'consent_given'=>['nullable','boolean'],'consent_source'=>['nullable','required_if:consent_given,1','string','max:160']]);
        $phone=preg_replace('/[ ()-]/','',$data['phone']);
        if (!str_starts_with($phone,'+')) $phone='+'.$phone;
        $exists=Contact::where('tenant_id',$tenant->id)->where('phone',$phone)->exists();
        if ($exists) return back()->withErrors(['phone'=>'Ce numéro existe déjà dans cet espace.'])->withInput();
        $consent=(bool)($data['consent_given']??false);
        Contact::create(['tenant_id'=>$tenant->id,'created_by'=>$request->user()->id,'name'=>$data['name']??null,'phone'=>$phone,'consent_given'=>$consent,'consent_source'=>$consent?($data['consent_source']??'Consentement déclaré lors de la saisie'):null,'consented_at'=>$consent?now():null]);
        return redirect()->route('contacts.index')->with('success',$consent?'Contact ajouté avec consentement déclaré. Conserve la preuve réelle du consentement.':'Contact ajouté sans autorisation d’envoi.');
    }
    public function import(Request $request)
    {
        $tenant=$this->tenant($request);
        $data=$request->validate(['contacts_file'=>['required','file','mimes:csv,txt','max:5120']]);
        $handle=fopen($data['contacts_file']->getRealPath(),'r');
        if (!$handle) return back()->withErrors(['contacts_file'=>'Impossible de lire le fichier.']);
        $header=fgetcsv($handle,0,',');
        if (!$header) { fclose($handle); return back()->withErrors(['contacts_file'=>'Le CSV est vide.']); }
        $header=array_map(fn($v)=>strtolower(trim((string)$v)), $header);
        if (!in_array('phone',$header,true)) { fclose($handle); return back()->withErrors(['contacts_file'=>'Le CSV doit contenir une colonne phone.']); }
        $created=0; $skipped=0; $rows=0;
        while (($row=fgetcsv($handle,0,','))!==false && $rows<1000) {
            $rows++; $item=[];
            foreach($header as $i=>$key) $item[$key]=trim((string)($row[$i]??''));
            $phone=preg_replace('/[ ()-]/','',$item['phone']??'');
            if (!$phone || !preg_match('/^\+?[0-9]{7,32}$/',$phone)) { $skipped++; continue; }
            if (!str_starts_with($phone,'+')) $phone='+'.$phone;
            if (Contact::where('tenant_id',$tenant->id)->where('phone',$phone)->exists()) { $skipped++; continue; }
            $consent=in_array(strtolower($item['consent_given']??''),['1','yes','oui','true'],true) && !empty($item['consent_source']);
            Contact::create(['tenant_id'=>$tenant->id,'created_by'=>$request->user()->id,'name'=>mb_substr($item['name']??'',0,120)?:null,'phone'=>$phone,'consent_given'=>$consent,'consent_source'=>$consent?mb_substr($item['consent_source'],0,160):null,'consented_at'=>$consent?now():null]);
            $created++;
        }
        fclose($handle);
        return redirect()->route('contacts.index')->with('success',"Import terminé : {$created} contact(s) ajouté(s), {$skipped} ligne(s) ignorée(s). Les lignes sans consentement actif ne pourront pas être ciblées.");
    }
    public function toggleConsent(Request $request, Contact $contact) {
        $tenant=$this->tenant($request); abort_unless($contact->tenant_id===$tenant->id,404);
        $data=$request->validate(['action'=>['required',Rule::in(['consent','unsubscribe'])],'consent_source'=>['nullable','string','max:160']]);
        if ($data['action']==='unsubscribe') { $contact->update(['consent_given'=>false,'unsubscribed_at'=>now()]); }
        else { $request->validate(['consent_source'=>['required','string','max:160']]); $contact->update(['consent_given'=>true,'consent_source'=>$data['consent_source'],'consented_at'=>now(),'unsubscribed_at'=>null]); }
        return back()->with('success',$data['action']==='unsubscribe'?'Désinscription enregistrée.':'Consentement mis à jour. Assure-toi de conserver la preuve correspondante.');
    }
    public function destroy(Request $request, Contact $contact) { abort_unless($contact->tenant_id===$this->tenant($request)->id,404); $contact->delete(); return back()->with('success','Contact supprimé.'); }
}
