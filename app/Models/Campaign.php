<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'name', 'channel', 'content', 'template_name', 'media_path', 'status', 'scheduled_at', 'sent_at', 'notes'];
    protected function casts(): array { return ['scheduled_at' => 'datetime', 'sent_at' => 'datetime']; }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function deliveries(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(CampaignDelivery::class); }
    public function getChannelLabelAttribute(): string { return ['private_message' => 'Messages privés', 'status' => 'Statut WhatsApp', 'group' => 'Groupe WhatsApp'][$this->channel] ?? $this->channel; }
    public function getStatusLabelAttribute(): string { return ['draft' => 'Brouillon', 'scheduled' => 'Programmée', 'queued' => 'En attente', 'sent' => 'Acceptée par API', 'failed' => 'Échec', 'manual_required' => 'Action manuelle'][$this->status] ?? $this->status; }
}
