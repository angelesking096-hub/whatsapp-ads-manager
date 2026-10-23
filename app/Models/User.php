<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'is_platform_admin'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_platform_admin' => 'boolean']; }

    public function tenants(): BelongsToMany { return $this->belongsToMany(Tenant::class)->withPivot('role')->withTimestamps(); }
    public function ownedTenants(): HasMany { return $this->hasMany(Tenant::class, 'owner_id'); }
    public function campaigns(): HasMany { return $this->hasMany(Campaign::class); }

    public function currentTenant(): ?Tenant
    {
        return $this->tenants()->wherePivot('is_current', true)->first() ?? $this->tenants()->first();
    }
}
