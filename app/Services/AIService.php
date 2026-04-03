<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AIService
{
    public function __construct(
        private readonly SarvamService $sarvam,
    ) {}

    /**
     * Download recording from Twilio, transcribe, and get AI reply text.
     *
     * @return array{user_text: string, ai_response: string}
     */
    public function processRecording(string $recordingUrl): array
    {
        $audio = $this->downloadTwilioRecording($recordingUrl);

        $userText = $this->transcribeWithFallback($audio);
        if (trim($userText) === '') {
            $userText = '(empty or unclear audio)';
        }

        $aiResponse = $this->chatWithFallback($userText);

        return [
            'user_text' => $userText,
            'ai_response' => $aiResponse,
        ];
    }

    private function transcribeWithFallback(string $audioBinary): string
    {
        try {
            return $this->transcribeWithOpenAI($audioBinary);
        } catch (Throwable $e) {
            Log::warning('OpenAI transcription failed, trying Sarvam', ['error' => $e->getMessage()]);
        }

        if (! $this->sarvam->isConfigured()) {
            throw new RuntimeException('Speech-to-text failed and Sarvam is not configured.');
        }

        return $this->sarvam->transcribe($audioBinary);
    }

    private function chatWithFallback(string $userText): string
    {
        try {
            return $this->chatWithOpenAI($userText);
        } catch (Throwable $e) {
            Log::warning('OpenAI chat failed, trying Sarvam', ['error' => $e->getMessage()]);
        }

        if (! $this->sarvam->isConfigured()) {
            throw new RuntimeException('Chat failed and Sarvam is not configured.');
        }

        return $this->sarvam->chat($userText);
    }

    private function downloadTwilioRecording(string $url): string
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (! is_string($sid) || $sid === '' || ! is_string($token) || $token === '') {
            throw new RuntimeException('Twilio credentials are not configured.');
        }

        $response = Http::withBasicAuth($sid, $token)
            ->timeout(120)
            ->get($url);

        $this->throwIfFailed($response, 'Twilio recording download');

        $body = $response->body();
        if ($body === '') {
            throw new RuntimeException('Downloaded recording is empty.');
        }

        return $body;
    }

    private function transcribeWithOpenAI(string $audioBinary): string
    {
        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = config('services.openai.whisper_model', 'whisper-1');

        $response = Http::withToken($key)
            ->timeout(120)
            ->attach('file', $audioBinary, 'recording.wav')
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => $model,
            ]);

        $this->throwIfFailed($response, 'OpenAI transcription');

        $text = $response->json('text');
        if (! is_string($text)) {
            throw new RuntimeException('OpenAI transcription returned no text.');
        }

        return trim($text);
    }

    private function chatWithOpenAI(string $userText): string
    {
        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = config('services.openai.chat_model', 'gpt-4o-mini');

        $response = Http::withToken($key)
            ->timeout(60)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
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

        $this->throwIfFailed($response, 'OpenAI chat');

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI chat returned no message content.');
        }

        return trim($content);
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
