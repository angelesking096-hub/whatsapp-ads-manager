<?php
namespace App\Http\Controllers;
use App\Models\CampaignDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode=$request->query('hub.mode');
        $token=(string)$request->query('hub.verify_token','');
        $challenge=(string)$request->query('hub.challenge','');
        $expected=(string)config('services.whatsapp.webhook_verify_token','');
        if ($mode==='subscribe' && $expected!=='' && hash_equals($expected,$token)) return response($challenge,200)->header('Content-Type','text/plain');
        return response('Forbidden',403);
    }
    public function receive(Request $request)
    {
        $secret=(string)config('services.whatsapp.app_secret','');
        $signature=(string)$request->header('X-Hub-Signature-256','');
        $payload=$request->getContent();
        if ($secret==='' || $signature==='' || !hash_equals('sha256='.hash_hmac('sha256',$payload,$secret),$signature)) {
            Log::warning('WhatsApp webhook rejected: signature validation failed');
            return response()->json(['error'=>'Invalid signature'],403);
        }
        $data=json_decode($payload,true);
        if (!is_array($data)) return response()->json(['error'=>'Invalid payload'],400);
        foreach (($data['entry']??[]) as $entry) {
            foreach (($entry['changes']??[]) as $change) {
                $value=$change['value']??[];
                foreach (($value['statuses']??[]) as $statusEvent) {
                    $messageId=$statusEvent['id']??null; $status=$statusEvent['status']??null;
                    if (!$messageId || !in_array($status,['sent','delivered','read','failed'],true)) continue;
                    $delivery=CampaignDelivery::where('provider_message_id',$messageId)->first();
                    if (!$delivery) continue;
                    $updates=['status'=>$status];
                    if ($status==='failed') $updates['error_message']=mb_substr(json_encode($statusEvent['errors']??['Erreur de livraison'],JSON_UNESCAPED_UNICODE) ?: 'Erreur de livraison',0,2000);
                    $delivery->update($updates);
                    if ($status==='failed') $delivery->campaign()->update(['status'=>'failed']);
                }
            }
        }
        return response()->json(['received'=>true],200);
    }
}
