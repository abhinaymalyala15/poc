<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

class ValidateTwilioSignature
{
    /**
     * Verify {@code X-Twilio-Signature} when TWILIO_VALIDATE_SIGNATURE=true.
     * Ensure APP_URL matches the public webhook URL Twilio calls (including https and path).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.twilio.validate_signature')) {
            return $next($request);
        }

        $token = config('services.twilio.token');
        if (! is_string($token) || $token === '') {
            Log::warning('Twilio signature validation enabled but TWILIO_AUTH_TOKEN is empty');

            return response('Twilio not configured', 500);
        }

        $signature = $request->header('X-Twilio-Signature');
        if (! is_string($signature) || $signature === '') {
            return response('Forbidden', 403);
        }

        $validator = new RequestValidator($token);
        $url = $request->fullUrl();
        $params = $request->request->all();

        if (! $validator->validate($signature, $url, $params)) {
            Log::warning('Invalid Twilio signature', ['url' => $url]);

            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
