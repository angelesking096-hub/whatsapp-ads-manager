<?php

namespace App\Http\Controllers;

use App\Models\ScrapedLead;
use App\Services\AiLeadScraperService;
use Illuminate\Http\Request;

class ProspectingController extends Controller
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
        $query = ScrapedLead::where('tenant_id', $tenant->id);

        if ($request->filled('category')) {
            $query->where('category', 'like', "%{$request->query('category')}%");
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->query('city')}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $leads = $query->latest()->paginate(15)->withQueryString();
        $totalLeads = ScrapedLead::where('tenant_id', $tenant->id)->count();
        $importedCount = ScrapedLead::where('tenant_id', $tenant->id)->where('is_imported', true)->count();

        return view('prospecting.index', compact('tenant', 'leads', 'totalLeads', 'importedCount'));
    }

    public function search(Request $request, AiLeadScraperService $scraperService)
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'keywords' => ['nullable', 'string', 'max:150'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $country = $data['country'] ?: 'Cameroun';
        $limit = $data['limit'] ?: 10;

        try {
            $newLeads = $scraperService->searchAndQualify(
                $tenant,
                $data['category'],
                $data['city'],
                $country,
                $data['keywords'] ?? null,
                $limit
            );

            $count = count($newLeads);
            return redirect()->route('prospecting.index')
                ->with('success', "🎯 {$count} prospects ciblés et qualifiés par l'IA ont été récupérés en temps réel ! Vous pouvez les importer ou les diffuser immédiatement.");
        } catch (\Exception $e) {
            return back()->with('warning', $e->getMessage())->withInput();
        }
    }

    public function importSingle(Request $request, ScrapedLead $lead, AiLeadScraperService $scraperService)
    {
        $tenant = $this->tenant($request);
        abort_unless($lead->tenant_id === $tenant->id, 403);

        $contact = $scraperService->importLeadToContacts($lead);

        return back()->with('success', "Le prospect '{$contact->name}' ({$contact->phone}) a été importé dans votre carnet de contacts.");
    }

    public function importAll(Request $request, AiLeadScraperService $scraperService)
    {
        $tenant = $this->tenant($request);
        $unimportedLeads = ScrapedLead::where('tenant_id', $tenant->id)
            ->where('is_imported', false)
            ->get();

        if ($unimportedLeads->isEmpty()) {
            return back()->with('warning', 'Aucun nouveau prospect en attente d’importation.');
        }

        $imported = 0;
        foreach ($unimportedLeads as $lead) {
            $scraperService->importLeadToContacts($lead);
            $imported++;
        }

        return back()->with('success', "✅ {$imported} prospect(s) importé(s) en 1 clic dans votre base de contacts avec consentement tagué.");
    }

    public function destroy(Request $request, ScrapedLead $lead)
    {
        $tenant = $this->tenant($request);
        abort_unless($lead->tenant_id === $tenant->id, 403);

        $lead->delete();
        return back()->with('success', 'Prospect retiré de la liste.');
    }

    public function clearAll(Request $request)
    {
        $tenant = $this->tenant($request);
        ScrapedLead::where('tenant_id', $tenant->id)->delete();

        return back()->with('success', 'Historique des prospects scrappés réinitialisé.');
    }
}
