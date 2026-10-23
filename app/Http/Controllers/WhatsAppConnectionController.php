<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WhatsAppConnectionController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->currentTenant(); abort_unless($tenant, 403);
        $accounts = $tenant->whatsappAccounts()->latest()->get();
        $configured = filled(config('services.whatsapp.app_id')) && filled(config('services.whatsapp.config_id'));
        return view('dashboard.channels', compact('tenant','accounts','configured'));
    }
    public function testConfiguration(Request $request)
    {
        $tenant = $request->user()->currentTenant(); abort_unless($tenant, 403);
        return back()->with('warning', 'Le flux OAuth Meta n’est pas encore activé. Configure les identifiants Meta et implémente le parcours Embedded Signup avant de connecter des numéros clients.');
    }
}
