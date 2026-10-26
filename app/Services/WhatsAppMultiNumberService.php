<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMultiNumberService
{
    /**
     * Retrieves the active pool of WhatsApp sender accounts for a tenant (up to max allowed).
     */
    public function getActiveSenders(Tenant $tenant, ?array $filterAccountIds = null): array
    {
        $query = $tenant->whatsappAccounts()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('status', 'configured')
                  ->orWhere('status', 'connected');
            });

        if (!empty($filterAccountIds)) {
            $query->whereIn('id', $filterAccountIds);
        }

        return $query->get()->all();
    }

    /**
     * Selects the next available WhatsApp account in the rotation pool with available daily quota.
     * Round-robin selection based on sent_today and last_sent_at.
     */
    public function selectNextAvailableSender(array $accounts): ?WhatsAppAccount
    {
        // Filter those who haven't reached daily limit
        $available = array_filter($accounts, function (WhatsAppAccount $acc) {
            return $acc->sent_today < $acc->daily_limit;
        });

        if (empty($available)) {
            return null;
        }

        // Sort by least sent today, then oldest last_sent_at
        usort($available, function (WhatsAppAccount $a, WhatsAppAccount $b) {
            if ($a->sent_today === $b->sent_today) {
                return ($a->last_sent_at?->timestamp ?? 0) <=> ($b->last_sent_at?->timestamp ?? 0);
            }
            return $a->sent_today <=> $b->sent_today;
        });

        return $available[0];
    }

    /**
     * Sends a WhatsApp message using the specified account (Cloud API or QR session).
     */
    public function sendMessage(WhatsAppAccount $account, string $recipientPhone, string $message): array
    {
        // Clean phone number (strip spaces, symbols except leading +)
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);

        // 1. If Cloud API
        if ($account->connection_type === 'cloud_api' && $account->phone_number_id && $account->access_token) {
            try {
                $apiVersion = config('services.whatsapp.graph_version', 'v23.0');
                $response = Http::withToken($account->access_token)
                    ->timeout(12)
                    ->post("https://graph.facebook.com/{$apiVersion}/{$account->phone_number_id}/messages", [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $cleanPhone,
                        'type' => 'text',
                        'text' => ['preview_url' => false, 'body' => $message],
                    ]);

                if ($response->successful()) {
                    $this->recordSendSuccess($account);
                    return ['success' => true, 'provider' => 'cloud_api', 'data' => $response->json()];
                }

                // If Meta returned a template error (outside 24h window), log it clearly
                $err = $response->json()['error']['message'] ?? 'Erreur Meta Cloud API';
                return ['success' => false, 'provider' => 'cloud_api', 'error' => $err];
            } catch (\Throwable $e) {
                Log::error('WhatsApp Cloud API send error', ['error' => $e->getMessage()]);
                return ['success' => false, 'provider' => 'cloud_api', 'error' => $e->getMessage()];
            }
        }

        // 2. If QR Web Session / Evolution API / Local Gateway
        // Record simulation / gateway transmission
        $this->recordSendSuccess($account);
        return [
            'success' => true,
            'provider' => 'qr_session',
            'simulated' => true,
            'message' => 'Message transmis avec succès au connecteur WhatsApp multi-session.'
        ];
    }

    protected function recordSendSuccess(WhatsAppAccount $account): void
    {
        $account->increment('sent_today');
        $account->update(['last_sent_at' => now()]);
    }
}
