<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WhatsAppNumberController extends Controller
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
        $accounts = $tenant->whatsappAccounts()->orderBy('slot_number')->get();
        $maxNumbers = $tenant->max_whatsapp_numbers ?: 10;

        return view('whatsapp_numbers.index', compact('tenant', 'accounts', 'maxNumbers'));
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant($request);
        $currentCount = $tenant->whatsappAccounts()->count();
        $maxNumbers = $tenant->max_whatsapp_numbers ?: 10;

        if ($currentCount >= $maxNumbers) {
            return back()->with('warning', "Votre plan actuel est limité à {$maxNumbers} numéros WhatsApp. Veuillez passer à un forfait supérieur pour ajouter des slots supplémentaires.");
        }

        $data = $request->validate([
            'account_name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:40'],
            'connection_type' => ['required', Rule::in(['cloud_api', 'qr_session'])],
            'daily_limit' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'phone_number_id' => ['nullable', 'string', 'max:120'],
            'business_account_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['nullable', 'string', 'max:5000'],
        ]);

        $nextSlot = $tenant->whatsappAccounts()->max('slot_number') + 1;

        $tenant->whatsappAccounts()->create([
            'account_name' => $data['account_name'],
            'phone_number' => $data['phone_number'],
            'display_phone_number' => $data['phone_number'],
            'connection_type' => $data['connection_type'],
            'daily_limit' => $data['daily_limit'] ?? 250,
            'sent_today' => 0,
            'slot_number' => $nextSlot,
            'status' => $data['connection_type'] === 'cloud_api' ? (!empty($data['access_token']) ? 'configured' : 'not_connected') : 'connected',
            'session_status' => 'connected',
            'quality_rating' => 'GREEN',
            'is_active' => true,
            'phone_number_id' => $data['phone_number_id'] ?? null,
            'business_account_id' => $data['business_account_id'] ?? null,
            'access_token' => $data['access_token'] ?? null,
        ]);

        return back()->with('success', "Le numéro '{$data['account_name']}' a été enregistré avec succès dans le slot #{$nextSlot}.");
    }

    public function toggleActive(Request $request, WhatsAppAccount $account)
    {
        $tenant = $this->tenant($request);
        abort_unless($account->tenant_id === $tenant->id, 403);

        $account->update(['is_active' => !$account->is_active]);

        $statusText = $account->is_active ? 'activé' : 'mis en pause';
        return back()->with('success', "Le numéro '{$account->account_name}' est désormais {$statusText} dans la rotation d'envoi.");
    }

    public function update(Request $request, WhatsAppAccount $account)
    {
        $tenant = $this->tenant($request);
        abort_unless($account->tenant_id === $tenant->id, 403);

        $data = $request->validate([
            'account_name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:40'],
            'daily_limit' => ['required', 'integer', 'min:10', 'max:10000'],
            'connection_type' => ['required', Rule::in(['cloud_api', 'qr_session'])],
            'phone_number_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['nullable', 'string', 'max:5000'],
        ]);

        $account->update([
            'account_name' => $data['account_name'],
            'phone_number' => $data['phone_number'],
            'display_phone_number' => $data['phone_number'],
            'daily_limit' => $data['daily_limit'],
            'connection_type' => $data['connection_type'],
            'phone_number_id' => $data['phone_number_id'] ?? $account->phone_number_id,
            'access_token' => $data['access_token'] ? $data['access_token'] : $account->access_token,
        ]);

        return back()->with('success', "Paramètres du numéro '{$account->account_name}' mis à jour.");
    }

    public function destroy(Request $request, WhatsAppAccount $account)
    {
        $tenant = $this->tenant($request);
        abort_unless($account->tenant_id === $tenant->id, 403);

        $name = $account->account_name ?: $account->display_phone_number;
        $account->delete();

        return back()->with('success', "Le numéro '{$name}' a été supprimé du pool de diffusion.");
    }
}
