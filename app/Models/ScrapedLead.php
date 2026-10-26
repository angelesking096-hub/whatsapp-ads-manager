<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedLead extends Model
{
    protected $table = 'scraped_leads';

    protected $fillable = [
        'tenant_id',
        'search_query',
        'business_name',
        'contact_name',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'country',
        'category',
        'detected_need',
        'qualification_score',
        'ai_pitch',
        'status',
        'is_imported',
        'imported_contact_id',
    ];

    protected function casts(): array
    {
        return [
            'is_imported' => 'boolean',
            'qualification_score' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function importedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'imported_contact_id');
    }
}
