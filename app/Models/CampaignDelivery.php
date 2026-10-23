<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CampaignDelivery extends Model
{
    protected $fillable=['campaign_id','contact_id','status','provider_message_id','error_message','sent_at'];
    protected function casts(): array { return ['sent_at'=>'datetime']; }
    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
}
