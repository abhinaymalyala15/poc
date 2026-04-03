<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SarvamService
{
    public function isConfigured(): bool
    {
        $key = config('services.sarvam.key');

        return is_string($key) && $key !== '';
    }

    public function transcribe(string $audioBinary, string $filename = 'recording.wav'): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('SARVAM_API_KEY is not configured.');
        }

        $key = config('services.sarvam.key');
        $model = config('services.sarvam.stt_model', 'saaras:v3');

        $response = Http::withHeaders(['api-subscription-key' => $key])
            ->timeout(120)
            ->attach('file', $audioBinary, $filename)
            ->post('https://api.sarvam.ai/speech-to-text', [
                'model' => $model,
                'mode' => 'transcribe',
            ]);

        $this->throwIfFailed($response, 'Sarvam speech-to-text');

        $text = $response->json('transcript');
        if (! is_string($text)) {
            throw new RuntimeException('Sarvam STT returned no transcript.');
        }

        return trim($text);
    }

    public function chat(string $userText): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('SARVAM_API_KEY is not configured.');
        }

        $key = config('services.sarvam.key');
        $model = config('services.sarvam.chat_model', 'sarvam-30b');

        $response = Http::withToken($key)
            ->timeout(90)
            ->acceptJson()
            ->post('https://api.sarvam.ai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful phone assistant. Keep answers concise and natural for spoken voice (one or two short sentences unless the caller asks for detail).',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userText,
                    ],
                ],
            ]);

        $this->throwIfFailed($response, 'Sarvam chat');

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Sarvam chat returned no message content.');
        }

        return trim($content);
    }

    /**
     * @return array{0: string, 1: string} Binary audio and file extension (wav or mp3)
     */
    public function textToSpeech(string $text): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('SARVAM_API_KEY is not configured.');
        }

        $key = config('services.sarvam.key');
        $model = config('services.sarvam.tts_model', 'bulbul:v3');
        $speaker = config('services.sarvam.tts_speaker', 'shubh');
        $lang = config('services.sarvam.tts_language', 'en-IN');

        $payload = [
            'text' => $text,
            'model' => $model,
            'speaker' => $speaker,
            'target_language_code' => $lang,
        ];

        $codec = config('services.sarvam.tts_audio_codec');
        if (is_string($codec) && $codec !== '') {
            $payload['output_audio_codec'] = $codec;
        }

        $response = Http::withHeaders([
            'api-subscription-key' => $key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(120)
            ->post('https://api.sarvam.ai/text-to-speech', $payload);

        $this->throwIfFailed($response, 'Sarvam TTS');

        $audios = $response->json('audios');
        if (! is_array($audios) || $audios === []) {
            throw new RuntimeException('Sarvam TTS returned no audio.');
        }

        $combined = implode('', array_map(static fn ($p) => is_string($p) ? $p : '', $audios));
        if ($combined === '') {
            throw new RuntimeException('Sarvam TTS audio payload empty.');
        }

        $binary = base64_decode($combined, true);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Sarvam TTS base64 decode failed.');
        }

        $ext = config('services.sarvam.tts_file_extension', 'wav');

        return [$binary, ltrim($ext, '.')];
    }

    private function throwIfFailed(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        Log::error($context.' failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new RuntimeException($context.' failed: HTTP '.$response->status());
    }
}
