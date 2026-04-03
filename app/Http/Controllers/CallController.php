<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Services\AIService;
use App\Services\TTSService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class CallController extends Controller
{
    public function __construct(
        private readonly AIService $aiService,
        private readonly TTSService $ttsService,
    ) {}

    /**
     * Twilio Voice webhook: greet caller and record their message.
     */
    public function incomingCall(Request $request): Response
    {
        Log::info('Twilio incoming call', [
            'from' => $request->input('From'),
            'to' => $request->input('To'),
            'call_sid' => $request->input('CallSid'),
        ]);

        $actionUrl = route('twilio.process-recording');

        $twiml = $this->twiml(
            '<Say voice="Polly.Joanna">'
            .'Thanks for calling. Please speak after the tone, then stay on the line.'
            .'</Say>'
            .'<Record '
            .'action="'.e($actionUrl).'" '
            .'method="POST" '
            .'maxLength="120" '
            .'timeout="10" '
            .'playBeep="true" '
            .'/>'
        );

        return response($twiml, 200)->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Twilio callback after recording: STT → AI → TTS → Play.
     */
    public function processRecording(Request $request): Response
    {
        $payload = $request->only([
            'RecordingUrl',
            'RecordingSid',
            'CallSid',
            'From',
            'To',
            'RecordingDuration',
        ]);

        Log::info('Twilio process recording', $payload);

        $recordingUrl = $request->input('RecordingUrl');
        $from = $request->input('From');
        $callSid = $request->input('CallSid');

        if (! is_string($recordingUrl) || $recordingUrl === '') {
            Log::warning('Missing RecordingUrl from Twilio', $payload);

            return $this->twimlResponse(
                '<Say voice="Polly.Joanna">We did not receive a recording. Goodbye.</Say><Hangup/>'
            );
        }

        try {
            $result = $this->aiService->processRecording($recordingUrl);
            $audioUrl = $this->ttsService->synthesizeToPublicUrl($result['ai_response']);

            Call::query()->create([
                'call_sid' => is_string($callSid) ? $callSid : null,
                'phone_number' => is_string($from) ? $from : null,
                'recording_url' => $recordingUrl,
                'user_text' => $result['user_text'],
                'ai_response' => $result['ai_response'],
                'response_audio_url' => $audioUrl,
                'error_message' => null,
            ]);

            return $this->twimlResponse(
                '<Play>'.e($audioUrl).'</Play>'
                .'<Say voice="Polly.Joanna">Thank you. Goodbye.</Say>'
                .'<Hangup/>'
            );
        } catch (Throwable $e) {
            Log::error('processRecording failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            try {
                Call::query()->create([
                    'call_sid' => is_string($callSid) ? $callSid : null,
                    'phone_number' => is_string($from) ? $from : null,
                    'recording_url' => $recordingUrl,
                    'user_text' => null,
                    'ai_response' => null,
                    'response_audio_url' => null,
                    'error_message' => $e->getMessage(),
                ]);
            } catch (Throwable $inner) {
                Log::error('Failed to persist error call row', ['message' => $inner->getMessage()]);
            }

            return $this->twimlResponse(
                '<Say voice="Polly.Joanna">'
                .'Sorry, we could not process your call right now. Please try again later.'
                .'</Say>'
                .'<Hangup/>'
            );
        }
    }

    private function twiml(string $innerXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Response>'
            .$innerXml
            .'</Response>';
    }

    private function twimlResponse(string $innerXml): Response
    {
        return response($this->twiml($innerXml), 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
