<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppInboxMessage;
use App\Services\WhatsAppMultiNumberService;
use Illuminate\Http\Request;

class InboxController extends Controller
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
        $accounts = $tenant->whatsappAccounts()->where('is_active', true)->get();

        $selectedPhone = $request->query('phone');
        $filterAccount = $request->query('account_id');

        // Group latest message per phone number
        $conversationsQuery = WhatsAppInboxMessage::where('tenant_id', $tenant->id);

        if ($filterAccount) {
            $conversationsQuery->where('whatsapp_account_id', $filterAccount);
        }

        $allMessages = (clone $conversationsQuery)->latest()->get();

        // Group by phone
        $threads = $allMessages->groupBy('phone_number')->map(function ($msgs) {
            return [
                'phone' => $msgs->first()->phone_number,
                'name' => $msgs->first()->contact_name ?: $msgs->first()->phone_number,
                'last_message' => $msgs->first()->message,
                'last_time' => $msgs->first()->created_at,
                'unread_count' => $msgs->where('direction', 'inbound')->where('is_read', false)->count(),
                'account' => $msgs->first()->whatsappAccount,
            ];
        })->sortByDesc('last_time');

        // Active thread messages
        $activeMessages = collect();
        $activeContact = null;
        if ($selectedPhone) {
            $activeMessages = WhatsAppInboxMessage::where('tenant_id', $tenant->id)
                ->where('phone_number', $selectedPhone)
                ->with('whatsappAccount')
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark inbound as read
            WhatsAppInboxMessage::where('tenant_id', $tenant->id)
                ->where('phone_number', $selectedPhone)
                ->where('direction', 'inbound')
                ->update(['is_read' => true]);

            $activeContact = Contact::where('tenant_id', $tenant->id)->where('phone', $selectedPhone)->first();
        }

        return view('inbox.index', compact('tenant', 'accounts', 'threads', 'selectedPhone', 'activeMessages', 'activeContact', 'filterAccount'));
    }

    public function reply(Request $request, WhatsAppMultiNumberService $multiNumberService)
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:40'],
            'whatsapp_account_id' => ['required', 'exists:whatsapp_accounts,id'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $account = WhatsAppAccount::where('tenant_id', $tenant->id)->findOrFail($data['whatsapp_account_id']);

        // Send message
        $result = $multiNumberService->sendMessage($account, $data['phone_number'], $data['message']);

        // Record message in inbox
        WhatsAppInboxMessage::create([
            'tenant_id' => $tenant->id,
            'whatsapp_account_id' => $account->id,
            'phone_number' => $data['phone_number'],
            'contact_name' => $request->input('contact_name'),
            'direction' => 'outbound',
            'message' => $data['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'is_read' => true,
        ]);

        return back()->with('success', 'Message envoyé au prospect.');
    }
}
