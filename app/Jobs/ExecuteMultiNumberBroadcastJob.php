<?php

namespace App\Jobs;

use App\Models\BroadcastDispatch;
use App\Models\Contact;
use App\Models\MultiNumberBroadcast;
use App\Models\ScrapedLead;
use App\Models\WhatsAppInboxMessage;
use App\Services\SpintaxService;
use App\Services\WhatsAppMultiNumberService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteMultiNumberBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public int $broadcastId) {}

    public function handle(WhatsAppMultiNumberService $multiNumberService): void
    {
        $broadcast = MultiNumberBroadcast::with('tenant')->find($this->broadcastId);
        if (!$broadcast || $broadcast->status === 'completed') {
            return;
        }

        $broadcast->update(['status' => 'running', 'started_at' => now()]);
        $tenant = $broadcast->tenant;

        // Retrieve available senders in pool
        $senders = $multiNumberService->getActiveSenders($tenant, $broadcast->selected_account_ids);
        if (empty($senders)) {
            $broadcast->update(['status' => 'failed']);
            return;
        }

        // Retrieve recipients
        $recipients = [];
        if ($broadcast->target_audience === 'scraped_leads') {
            $leads = ScrapedLead::where('tenant_id', $tenant->id)->latest()->take(200)->get();
            foreach ($leads as $lead) {
                $recipients[] = [
                    'phone' => $lead->phone,
                    'name' => $lead->contact_name ?: $lead->business_name,
                    'business_name' => $lead->business_name,
                    'city' => $lead->city,
                    'detected_need' => $lead->detected_need,
                    'category' => $lead->category,
                ];
            }
        } else {
            $contacts = Contact::where('tenant_id', $tenant->id)->reachable()->take(200)->get();
            foreach ($contacts as $c) {
                $recipients[] = [
                    'phone' => $c->phone,
                    'name' => $c->name ?: 'Client',
                    'business_name' => $c->name ?: 'Entreprise',
                    'city' => '',
                    'detected_need' => 'vos besoins',
                    'category' => '',
                ];
            }
        }

        $broadcast->update(['total_recipients' => count($recipients)]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            // Pick next sender via round-robin
            $sender = $multiNumberService->selectNextAvailableSender($senders);
            if (!$sender) {
                // All senders reached daily limit
                break;
            }

            // Generate unique personalized text with spintax
            $finalMessage = SpintaxService::personalize($broadcast->message_template, $recipient);

            // Create dispatch entry
            $dispatch = BroadcastDispatch::create([
                'broadcast_id' => $broadcast->id,
                'whatsapp_account_id' => $sender->id,
                'recipient_phone' => $recipient['phone'],
                'recipient_name' => $recipient['name'],
                'final_message' => $finalMessage,
                'status' => 'queued',
            ]);

            // Send via multi-number service
            $result = $multiNumberService->sendMessage($sender, $recipient['phone'], $finalMessage);

            if ($result['success']) {
                $dispatch->update(['status' => 'sent', 'sent_at' => now()]);
                $sentCount++;

                // Record in unified inbox as outbound message
                WhatsAppInboxMessage::create([
                    'tenant_id' => $tenant->id,
                    'whatsapp_account_id' => $sender->id,
                    'phone_number' => $recipient['phone'],
                    'contact_name' => $recipient['name'],
                    'direction' => 'outbound',
                    'message' => $finalMessage,
                    'status' => 'sent',
                    'is_read' => true,
                ]);
            } else {
                $dispatch->update(['status' => 'failed', 'error_message' => $result['error'] ?? 'Échec envoi']);
                $failedCount++;
            }

            $broadcast->update([
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);
        }

        $broadcast->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
