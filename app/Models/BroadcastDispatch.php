<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastDispatch extends Model
{
    protected $table = 'broadcast_dispatches';

    protected $fillable = [
        'broadcast_id',
        'whatsapp_account_id',
        'recipient_phone',
        'recipient_name',
        'final_message',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(MultiNumberBroadcast::class, 'broadcast_id');
    }

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }
}
