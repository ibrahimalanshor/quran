<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExerciseTrackerService;
use Illuminate\Http\Request;
use Netflie\WhatsAppCloudApi\WebHook;
use Netflie\WhatsAppCloudApi\WebHook\Notification\Text;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class WhatsappWebhookController extends Controller
{
        
    /**
     * verify
     *
     * @param  mixed $request
     * @return void
     */
    public function verify(Request $request) {
        $mode = $request->query('hub_mode') ?? null;
        $token = $request->query('hub_verify_token') ?? null;
        $challenge = $request->query('hub_challenge') ?? '';

        if ('subscribe' !== $mode || $token !== config('services.whatsapp.webhook_verify_token')) {
            return response($challenge, 403);
        }

        return response($challenge, 200);
    }
    
    /**
     * handle
     *
     * @param  mixed $request
     * @param  mixed $webHook
     * @param  mixed $exerciseTracker
     * @return void
     */
    public function handle(Request $request, WebHook $webHook, ExerciseTrackerService $exerciseTracker) {
        $cloudApi = app(WhatsAppCloudApi::class);

        $data = $request->all();
        $notification = $webHook->read($data);

        if (!$notification) {
            return response('OK');
        }

        if (!$notification instanceof Text) {
            return response('OK');
        }

        $customer = $notification->customer();

        $information = $exerciseTracker->askAi($notification->message());

        $exerciseTracker->saveInformation($exerciseTracker->parseInformation($information), [
            'phone_number' => $customer->phoneNumber(),
            'customer_name' => $customer->name(),
            'wam_id' => $notification->id()
        ]);

        $cloudApi->replyTo($notification->id())
            ->sendTextMessage($customer->phoneNumber(), $information->reply_message);

        return response('OK');
    }
    
}
