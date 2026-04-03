<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TTSService
{
    public function __construct(
        private readonly SarvamService $sarvam,
    ) {}

    /**
     * Synthesize speech to a file under public/audio/ and return an absolute URL for Twilio {@code Play}.
     */
    public function synthesizeToPublicUrl(string $text): string
    {
        try {
            return $this->synthesizeOpenAI($text);
        } catch (Throwable $e) {
            Log::warning('OpenAI TTS failed, trying Sarvam', ['error' => $e->getMessage()]);
        }

        if (! $this->sarvam->isConfigured()) {
            throw new RuntimeException('TTS failed and Sarvam is not configured.');
        }

        [$binary, $ext] = $this->sarvam->textToSpeech($text);

        return $this->storePublicAudio($binary, $ext);
    }

    private function synthesizeOpenAI(string $text): string
    {
        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = config('services.openai.tts_model', 'tts-1');
        $voice = config('services.openai.tts_voice', 'alloy');

        $response = Http::withToken($key)
            ->timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => $model,
                'input' => $text,
                'voice' => $voice,
                'response_format' => 'mp3',
            ]);

        $this->throwIfFailed($response, 'OpenAI TTS');

        $binary = $response->body();
        if ($binary === '') {
            throw new RuntimeException('OpenAI TTS returned empty audio.');
        }

        return $this->storePublicAudio($binary, 'mp3');
    }

    private function storePublicAudio(string $binary, string $extension): string
    {
        $dir = public_path('audio');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ext = ltrim($extension, '.');
        $filename = 'reply_'.Str::uuid()->toString().'.'.$ext;
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        File::put($path, $binary);

        return url('audio/'.$filename);
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
