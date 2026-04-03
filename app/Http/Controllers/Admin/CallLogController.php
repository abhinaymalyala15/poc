<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CallLogController extends Controller
{
    public function index(): View
    {
        return view('admin.calls.index');
    }

    public function data(): JsonResponse
    {
        $rows = Call::query()
            ->orderByDesc('id')
            ->get()
            ->map(static function (Call $call) {
                return [
                    'id' => $call->id,
                    'call_sid' => $call->call_sid,
                    'phone_number' => $call->phone_number,
                    'recording_url' => $call->recording_url,
                    'user_text' => $call->user_text,
                    'ai_response' => $call->ai_response,
                    'response_audio_url' => $call->response_audio_url,
                    'error_message' => $call->error_message,
                    'created_at' => $call->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['data' => $rows]);
    }
}
