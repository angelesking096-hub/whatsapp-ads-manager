<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $tenant = $request->user()->currentTenant();
        abort_unless($tenant, 403, 'Aucun espace entreprise associé à ce compte.');
        $base = Campaign::where('tenant_id', $tenant->id);
        $stats = [
            'total' => (clone $base)->count(),
            'scheduled' => (clone $base)->where('status', 'scheduled')->count(),
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
        ];
        $campaigns = (clone $base)->latest()->limit(6)->get();
        return view('dashboard.index', compact('tenant', 'stats', 'campaigns'));
    }
}
