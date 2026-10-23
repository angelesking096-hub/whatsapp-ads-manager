<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppAccount extends Model
{
    protected $table = 'whatsapp_accounts';
    protected $fillable = ['tenant_id', 'display_phone_number', 'phone_number_id', 'business_account_id', 'access_token', 'status'];
    protected function casts(): array { return ['access_token' => 'encrypted']; }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
