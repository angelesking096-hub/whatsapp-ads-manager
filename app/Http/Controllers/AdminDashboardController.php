<?php
namespace App\Http\Controllers;
use App\Models\Campaign;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->is_platform_admin, 403);
        return view('dashboard.admin', [
            'tenantCount' => Tenant::count(), 'userCount' => User::count(), 'campaignCount' => Campaign::count(),
            'tenants' => Tenant::withCount(['users','campaigns'])->latest()->limit(25)->get(),
        ]);
    }
}
