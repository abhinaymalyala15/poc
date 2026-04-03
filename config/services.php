<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'phone' => env('TWILIO_PHONE'),
        'validate_signature' => env('TWILIO_VALIDATE_SIGNATURE', false),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
        'whisper_model' => env('OPENAI_WHISPER_MODEL', 'whisper-1'),
        'tts_model' => env('OPENAI_TTS_MODEL', 'tts-1'),
        'tts_voice' => env('OPENAI_TTS_VOICE', 'alloy'),
    ],

    'sarvam' => [
        'key' => env('SARVAM_API_KEY'),
        'stt_model' => env('SARVAM_STT_MODEL', 'saaras:v3'),
        'chat_model' => env('SARVAM_CHAT_MODEL', 'sarvam-30b'),
        'tts_model' => env('SARVAM_TTS_MODEL', 'bulbul:v3'),
        'tts_speaker' => env('SARVAM_TTS_SPEAKER', 'shubh'),
        'tts_language' => env('SARVAM_TTS_LANGUAGE', 'en-IN'),
        'tts_audio_codec' => env('SARVAM_TTS_AUDIO_CODEC'),
        'tts_file_extension' => env('SARVAM_TTS_FILE_EXTENSION', 'wav'),
    ],

];
