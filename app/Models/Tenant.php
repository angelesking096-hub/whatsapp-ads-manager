<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'owner_id'];
    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class)->withPivot(['role', 'is_current'])->withTimestamps(); }
    public function campaigns(): HasMany { return $this->hasMany(Campaign::class); }
    public function contacts(): HasMany { return $this->hasMany(Contact::class); }
    public function mediaAssets(): HasMany { return $this->hasMany(MediaAsset::class); }
    public function whatsappAccounts(): HasMany { return $this->hasMany(WhatsAppAccount::class); }
}
