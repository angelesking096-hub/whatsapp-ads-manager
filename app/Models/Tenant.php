<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
        'owner_id',
        'subscription_plan',
        'max_whatsapp_numbers',
        'monthly_scraping_quota',
        'scraping_used_this_month',
        'daily_broadcast_quota',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['role', 'is_current'])->withTimestamps();
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsAppAccount::class);
    }

    public function activeWhatsAppAccounts(): HasMany
    {
        return $this->hasMany(WhatsAppAccount::class)->where('is_active', true);
    }

    public function scrapedLeads(): HasMany
    {
        return $this->hasMany(ScrapedLead::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(MultiNumberBroadcast::class);
    }

    public function inboxMessages(): HasMany
    {
        return $this->hasMany(WhatsAppInboxMessage::class);
    }

    public function getRemainingScrapingQuotaAttribute(): int
    {
        return max(0, $this->monthly_scraping_quota - $this->scraping_used_this_month);
    }

    public function getActiveAccountsCountAttribute(): int
    {
        return $this->whatsappAccounts()->where('is_active', true)->count();
    }
}
