<?php
namespace App\Console\Commands;
use App\Jobs\SendWhatsAppTemplateJob;
use App\Models\Campaign;
use App\Models\CampaignDelivery;
use App\Models\Contact;
use Illuminate\Console\Command;
class DispatchScheduledCampaigns extends Command
{
    protected $signature='campaigns:dispatch-scheduled';
    protected $description='Place dans la file les campagnes privées arrivées à échéance et correctement configurées.';
    public function handle(): int
    {
        $count=0;
        Campaign::where('status','scheduled')->where('channel','private_message')->whereNotNull('scheduled_at')->where('scheduled_at','<=',now())->orderBy('scheduled_at')->chunkById(50,function($campaigns) use (&$count) {
            foreach($campaigns as $campaign) {
                $account=$campaign->tenant->whatsappAccounts()->where('status','configured')->first();
                if (!$account || !$account->phone_number_id || !$account->access_token || !$campaign->template_name) continue;
                $contacts=Contact::where('tenant_id',$campaign->tenant_id)->reachable()->get(['id']);
                if ($contacts->isEmpty()) continue;
                $campaign->update(['status'=>'queued']); $delay=0;
                foreach($contacts as $contact) {
                    $delivery=CampaignDelivery::firstOrCreate(['campaign_id'=>$campaign->id,'contact_id'=>$contact->id],['status'=>'queued']);
                    if ($delivery->wasRecentlyCreated || $delivery->status==='failed') {
                        $delivery->update(['status'=>'queued','error_message'=>null]);
                        SendWhatsAppTemplateJob::dispatch($delivery->id)->delay(now()->addSeconds($delay)); $delay+=1; $count++;
                    }
                }
            }
        });
        $this->info("{$count} envoi(s) ajouté(s) à la file.");
        return self::SUCCESS;
    }
}
