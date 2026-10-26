<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MultiNumberBroadcast extends Model
{
    protected $table = 'multi_number_broadcasts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'message_template',
        'selected_account_ids',
        'target_audience',
        'total_recipients',
        'sent_count',
        'failed_count',
        'anti_ban_delay_seconds',
        'enable_spintax',
        'enable_ai_customization',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_account_ids' => 'array',
            'enable_spintax' => 'boolean',
            'enable_ai_customization' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(BroadcastDispatch::class, 'broadcast_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'draft' => 'Brouillon',
            'running' => 'En cours d’envoi',
            'completed' => 'Terminé avec succès',
            'paused' => 'En pause',
            'failed' => 'Échec',
        ][$this->status] ?? $this->status;
    }
}
