<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    public function create() { return view('auth.register'); }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'], 'company' => ['required','string','max:160'],
            'email' => ['required','email','max:190','unique:users,email'], 'password' => ['required','confirmed','min:10'],
            'terms' => ['accepted'],
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $base = Str::slug($data['company']) ?: 'entreprise'; $slug = $base; $i = 2;
            while (Tenant::where('slug', $slug)->exists()) $slug = $base.'-'.$i++;
            $tenant = Tenant::create(['name' => $data['company'], 'slug' => $slug, 'status' => 'active', 'owner_id' => $user->id]);
            $tenant->users()->attach($user->id, ['role' => 'owner', 'is_current' => true]);
            return $user;
        });
        auth()->login($user); $request->session()->regenerate();
        return redirect()->route('dashboard')->with('success', 'Ton espace entreprise est prêt.');
    }
}
