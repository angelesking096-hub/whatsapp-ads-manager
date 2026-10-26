<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionController extends Controller
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

        $plans = [
            'starter' => [
                'name' => 'Starter Commercial',
                'price' => '29 000 FCFA / mois',
                'max_numbers' => 2,
                'scraping_quota' => 300,
                'broadcast_quota' => 500,
                'features' => [
                    '2 numéros WhatsApp connectables',
                    '300 prospects scrappés & qualifiés par mois',
                    '500 messages de diffusion par jour',
                    'Rotation de numéros de base',
                    'Support par email',
                ],
            ],
            'pro' => [
                'name' => 'Pro Croissance',
                'price' => '69 000 FCFA / mois',
                'popular' => true,
                'max_numbers' => 5,
                'scraping_quota' => 1500,
                'broadcast_quota' => 2000,
                'features' => [
                    '5 numéros WhatsApp connectables',
                    '1 500 prospects scrappés & qualifiés par mois',
                    '2 000 messages de diffusion par jour',
                    'IA de qualification des besoins en temps réel',
                    'Protection anti-ban Spintax intelligente',
                    'Boîte de réception unifiée CRM',
                ],
            ],
            'agency' => [
                'name' => 'Agence & Élite (10 N°)',
                'price' => '149 000 FCFA / mois',
                'max_numbers' => 10,
                'scraping_quota' => 5000,
                'broadcast_quota' => 6000,
                'features' => [
                    'Jusqu’à 10 numéros WhatsApp connectés en simultané',
                    '5 000 prospects ultra-ciblés par mois',
                    '6 000 diffusions quotidiennes',
                    'Algorithme de clonage / rotation haute performance',
                    'Accès API & intégration Webhook',
                    'Support prioritaire dédié WhatsApp 7j/7',
                ],
            ],
        ];

        return view('subscription.index', compact('tenant', 'plans'));
    }

    public function switchPlan(Request $request)
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'plan' => ['required', 'in:starter,pro,agency'],
        ]);

        $planConfigs = [
            'starter' => ['max_numbers' => 2, 'scraping' => 300, 'broadcast' => 500],
            'pro' => ['max_numbers' => 5, 'scraping' => 1500, 'broadcast' => 2000],
            'agency' => ['max_numbers' => 10, 'scraping' => 5000, 'broadcast' => 6000],
        ];

        $cfg = $planConfigs[$data['plan']];

        $tenant->update([
            'subscription_plan' => $data['plan'],
            'max_whatsapp_numbers' => $cfg['max_numbers'],
            'monthly_scraping_quota' => $cfg['scraping'],
            'daily_broadcast_quota' => $cfg['broadcast'],
        ]);

        return back()->with('success', "Votre forfait a été mis à jour avec succès vers la formule " . strtoupper($data['plan']) . ". Vos quotas ont été ajustés immédiatement.");
    }
}
