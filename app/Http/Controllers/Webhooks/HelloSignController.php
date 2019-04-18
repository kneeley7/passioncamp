<?php

namespace App\Http\Controllers\Webhooks;

use App\Waiver;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Waiver\FetchAndUpdateStatus;
use Illuminate\Support\Facades\Validator;

class HelloSignController extends Controller
{
    public function __invoke(Request $request)
    {
        logger($request->header('User-Agent'));
        logger($request->json('json'));
        if (! $request->header('User-Agent') != 'HelloSign API') {
            return response('not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'event' => 'required',
        ]);

        if ($validator->fails()) {
            return response('not found', 404);
        }

        logger($request->json('event.event_type'), ['signature_request_id' => $request->json('event.event_metadata.related_signature_id')]);

        switch ($request->json('event.event_type')) {
            case 'signature_request_signed':
            case 'signature_request_declined':
            case 'signature_request_reassigned':
            case 'signature_request_downloadable':
            case 'signature_request_all_signed':
                $waiver = Waiver::whereProvider('adobesign')
                    ->where('provider_agreement_id', $request->json('event.event_metadata.related_signature_id'))
                    ->firstOrFail();
                FetchAndUpdateStatus::dispatch($waiver);
                break;

            case 'signature_request_email_bounce':
                $waiver = Waiver::whereProvider('adobesign')
                    ->where('provider_agreement_id', $request->json('event.event_metadata.related_signature_id'))
                    ->firstOrFail();
                $waiver->update(['status' => 'bounced']);
                break;
        }

        return response('ok', 200);
    }
}