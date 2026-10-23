<?php
namespace App\Jobs;
use App\Models\CampaignDelivery;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\CloudApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
class SendWhatsAppTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=3;
    public array $backoff=[30,120,300];
    public function __construct(public int $deliveryId) {}
    public function handle(CloudApiClient $client): void
    {
        $delivery=CampaignDelivery::with(['campaign.tenant','contact'])->find($this->deliveryId);
        if (!$delivery || $delivery->status==='sent') return;
        $campaign=$delivery->campaign; $contact=$delivery->contact;
        if (!$contact->consent_given || $contact->unsubscribed_at || $contact->tenant_id!==$campaign->tenant_id) { $delivery->update(['status'=>'skipped','error_message'=>'Contact non autorisé ou désinscrit.']); $this->refreshCampaignStatus($delivery->campaign); return; }
        $account=WhatsAppAccount::where('tenant_id',$campaign->tenant_id)->where('status','configured')->latest()->first();
        if (!$account || !$account->phone_number_id || !$account->access_token || !$campaign->template_name) { $delivery->update(['status'=>'failed','error_message'=>'Configuration WhatsApp ou modèle approuvé manquant.']); $this->refreshCampaignStatus($campaign); return; }
        try {
            $result=$client->sendTemplate($account->phone_number_id,$account->access_token,preg_replace('/\\D/','',$contact->phone),$campaign->template_name);
            $messageId=$result['messages'][0]['id']??null;
            $delivery->update(['status'=>'sent','provider_message_id'=>$messageId,'sent_at'=>now(),'error_message'=>null]);
            $this->refreshCampaignStatus($campaign);
        } catch (Throwable $e) {
            $delivery->update(['error_message'=>mb_substr($e->getMessage(),0,2000)]);
            Log::warning('WhatsApp template send failed',['delivery_id'=>$delivery->id,'campaign_id'=>$campaign->id,'exception'=>$e::class]);
            throw $e;
        }
    }
    public function failed(Throwable $exception): void
    {
        $delivery=CampaignDelivery::with('campaign')->find($this->deliveryId);
        if (!$delivery) return;
        $delivery->update(['status'=>'failed','error_message'=>mb_substr($exception->getMessage(),0,2000)]);
        $this->refreshCampaignStatus($delivery->campaign);
    }
    private function refreshCampaignStatus($campaign): void
    {
        $deliveries=$campaign->deliveries();
        if ($deliveries->whereIn('status',['queued'])->exists()) return;
        if ($deliveries->where('status','failed')->exists()) $campaign->update(['status'=>'failed']);
        elseif ($deliveries->whereIn('status',['sent','delivered','read'])->exists()) $campaign->update(['status'=>'sent','sent_at'=>now()]);
        else $campaign->update(['status'=>'failed']);
    }
}
