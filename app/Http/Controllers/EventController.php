<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessEventJob;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{

    public function store(Request $request)
    {
        if (!$request->has('payload')) {
            return response()->json(['error' => 'Payload missing'], 400);
        }

        $decoded = json_decode(base64_decode($request->payload), true);

        if (!$decoded ||
            !isset(
                $decoded['tenant_id'],
                $decoded['session_id'],
                $decoded['event_type'],
                $decoded['timestamp']
            )) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        ProcessEventJob::dispatch([
            'tenant_id'   => $decoded['tenant_id'],
            'session_id'  => $decoded['session_id'],
            'event_type'  => $decoded['event_type'],
            'timestamp'   => $decoded['timestamp'],
            'event_hash'  => sha1($request->payload),
        ]);

        return response()->json(['status' => 'accepted']);
    }

    public function encode(Request $request){

        $validator = Validator::make($request->all(), [
            'tenant_id'  => 'required',
            'session_id' => 'required|string',
            'event_type' => 'required|string',
            'timestamp' => 'required|date_format:Y-m-d H:i:s',
            'event_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Payload missing or invalid',
                'details' => $validator->errors(),
            ], 400);
        }

        $data = $validator->validated();
        $payload = base64_encode(json_encode($data));
        return response()->json(['payload' => $payload]);

    }

}
