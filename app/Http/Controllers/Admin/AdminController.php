<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $tenantCount = Tenant::count();
        $activeTenantCount = Tenant::where('status', 'active')->count();
        $suspendedTenantCount = Tenant::where('status', 'suspended')->count();
        $userCount = User::count();
        $adminCount = User::where('is_platform_admin', true)->count();
        $campaignCount = Campaign::count();
        $whatsappCount = WhatsAppAccount::where('status', 'configured')->count();
        $contactCount = Contact::count();

        $recentTenants = Tenant::with(['owner'])
            ->withCount(['users', 'campaigns', 'contacts'])
            ->latest()
            ->limit(10)
            ->get();

        $recentCampaigns = Campaign::with(['tenant', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        $recentUsers = User::with(['tenants'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'tenantCount',
            'activeTenantCount',
            'suspendedTenantCount',
            'userCount',
            'adminCount',
            'campaignCount',
            'whatsappCount',
            'contactCount',
            'recentTenants',
            'recentCampaigns',
            'recentUsers'
        ));
    }

    public function tenants(Request $request)
    {
        $query = Tenant::with(['owner', 'whatsappAccounts'])
            ->withCount(['users', 'campaigns', 'contacts']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function showTenant(Tenant $tenant)
    {
        $tenant->load([
            'owner',
            'users' => fn ($q) => $q->withPivot(['role', 'is_current']),
            'whatsappAccounts',
            'campaigns' => fn ($q) => $q->withCount('deliveries')->latest()->limit(20),
        ]);

        $contactsCount = $tenant->contacts()->count();
        $reachableContactsCount = $tenant->contacts()->reachable()->count();

        return view('admin.tenants.show', compact('tenant', 'contactsCount', 'reachableContactsCount'));
    }

    public function toggleTenantStatus(Tenant $tenant)
    {
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'réactivé' : 'suspendu';
        return back()->with('success', "L'espace entreprise « {$tenant->name} » a été {$label}.");
    }

    public function switchTenant(Request $request, Tenant $tenant)
    {
        $user = $request->user();

        // Ensure user is attached to tenant so currentTenant() resolution works
        if (! $tenant->users()->where('users.id', $user->id)->exists()) {
            $tenant->users()->attach($user->id, ['role' => 'admin', 'is_current' => true]);
        }

        // Reset other tenants is_current to false, set this one to true
        DB::table('tenant_user')->where('user_id', $user->id)->update(['is_current' => false]);
        DB::table('tenant_user')->where('user_id', $user->id)->where('tenant_id', $tenant->id)->update(['is_current' => true]);

        return redirect()->route('dashboard')->with('success', "Vous visualisez maintenant l'espace de « {$tenant->name} ».");
    }

    public function users(Request $request)
    {
        $query = User::with(['tenants']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            if ($role === 'super_admin') {
                $query->where('is_platform_admin', true);
            } elseif ($role === 'standard') {
                $query->where('is_platform_admin', false);
            }
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function toggleUserAdmin(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('warning', 'Vous ne pouvez pas révoquer vos propres privilèges de super administrateur.');
        }

        $user->is_platform_admin = ! $user->is_platform_admin;
        $user->save();

        $action = $user->is_platform_admin ? 'promu Super Administrateur' : 'rétrogradé utilisateur standard';
        return back()->with('success', "{$user->name} est désormais {$action}.");
    }

    public function deleteUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('warning', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "L'utilisateur {$name} a été supprimé.");
    }

    public function campaigns(Request $request)
    {
        $query = Campaign::with(['tenant', 'user'])->withCount('deliveries');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($channel = $request->input('channel')) {
            if ($channel !== 'all') {
                $query->where('channel', $channel);
            }
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $campaigns = $query->latest()->paginate(20)->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }
}
