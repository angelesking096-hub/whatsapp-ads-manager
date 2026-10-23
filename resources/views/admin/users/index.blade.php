@extends('layouts.app', ['title' => 'Gestion des utilisateurs · Administration'])
@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">SUPER ADMINISTRATION</div>
        <h1 class="page-title">Tous les utilisateurs</h1>
        <p class="subtitle">Gérez les comptes inscrits et attribuez les privilèges Super Administrateur de la plateforme.</p>
    </div>
    <a class="btn btn-light" href="{{ route('admin.dashboard') }}">← Retour au tableau de bord</a>
</div>

<div class="panel">
    <form method="GET" action="{{ route('admin.users.index') }}" class="filters">
        <input class="field" style="max-width:320px" type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou email...">
        <select class="select" style="max-width:180px" name="role">
            <option value="all" {{ request('role') === 'all' || !request('role') ? 'selected' : '' }}>Tous les rôles</option>
            <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admins uniquement</option>
            <option value="standard" {{ request('role') === 'standard' ? 'selected' : '' }}>Utilisateurs standards</option>
        </select>
        <button class="btn btn-primary" type="submit">Filtrer</button>
        @if(request()->hasAny(['search', 'role']))
            <a class="btn btn-light" href="{{ route('admin.users.index') }}">Réinitialiser</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Espaces rattachés</th>
                    <th>Privilège</th>
                    <th>Inscrit le</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td class="campaign-name">
                        <div style="display:flex;align-items:center;gap:9px">
                            <div class="avatar" style="width:28px;height:28px;font-size:12px">{{ strtoupper(substr($u->name,0,1)) }}</div>
                            <strong>{{ $u->name }}</strong>
                        </div>
                    </td>
                    <td><code>{{ $u->email }}</code></td>
                    <td>
                        @forelse($u->tenants as $ten)
                            <a href="{{ route('admin.tenants.show', $ten) }}" style="color:var(--green);font-weight:600">
                                {{ $ten->name }}
                            </a>@if(!$loop->last), @endif
                        @empty
                            <span style="color:var(--muted)">Aucun</span>
                        @endforelse
                    </td>
                    <td>
                        @if($u->is_platform_admin)
                            <span class="pill pill-green">Super Admin</span>
                        @else
                            <span class="pill pill-gray">Standard</span>
                        @endif
                    </td>
                    <td>{{ $u->created_at->format('d/m/Y') }}</td>
                    <td style="text-align:right">
                        @if($u->id !== auth()->id())
                            <div style="display:inline-flex;gap:6px">
                                <form method="POST" action="{{ route('admin.users.toggle-admin', $u) }}" style="margin:0" onsubmit="return confirm('Modifier le rôle administrateur pour {{ $u->name }} ?')">
                                    @csrf
                                    <button class="btn btn-light" style="padding:5px 9px;font-size:11px">
                                        {{ $u->is_platform_admin ? 'Révoquer Admin' : 'Proumouvoir Admin' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.delete', $u) }}" style="margin:0" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" style="padding:5px 9px;font-size:11px">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="pill pill-green" style="font-size:9px">Votre compte actuel</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:30px;color:var(--muted)">
                        Aucun utilisateur trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div style="margin-top:20px">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
