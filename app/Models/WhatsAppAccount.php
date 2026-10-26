<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppAccount extends Model
{
    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'tenant_id',
        'account_name',
        'phone_number',
        'display_phone_number',
        'phone_number_id',
        'business_account_id',
        'access_token',
        'status',
        'connection_type',
        'daily_limit',
        'sent_today',
        'last_sent_at',
        'quality_rating',
        'is_active',
        'slot_number',
        'session_status',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(BroadcastDispatch::class, 'whatsapp_account_id');
    }

    public function inboxMessages(): HasMany
    {
        return $this->hasMany(WhatsAppInboxMessage::class, 'whatsapp_account_id');
    }

    public function getEffectivePhoneNumberAttribute(): string
    {
        return $this->display_phone_number ?: ($this->phone_number ?: 'Numéro inconnu');
    }

    public function getRemainingDailyQuotaAttribute(): int
    {
        return max(0, $this->daily_limit - $this->sent_today);
    }
}
