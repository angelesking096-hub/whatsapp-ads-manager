<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Contact extends Model
{
    protected $fillable = ['tenant_id','created_by','name','phone','consent_given','consent_source','consented_at','unsubscribed_at'];
    protected function casts(): array { return ['consent_given'=>'boolean','consented_at'=>'datetime','unsubscribed_at'=>'datetime']; }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function scopeReachable($query) { return $query->where('consent_given',true)->whereNull('unsubscribed_at'); }
}
