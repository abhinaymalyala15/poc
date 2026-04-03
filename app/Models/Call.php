<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = [
        'call_sid',
        'phone_number',
        'recording_url',
        'user_text',
        'ai_response',
        'response_audio_url',
        'error_message',
    ];
}
