<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwilioWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_call_returns_twiml(): void
    {
        $response = $this->post('/incoming-call', [
            'From' => '+16624306108',
            'CallSid' => 'CAxxxxxxxx',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $response->assertSee('<Response>', false);
        $response->assertSee('process-recording', false);
    }

    public function test_guest_cannot_access_admin_call_logs(): void
    {
        $this->get('/admin/calls')->assertRedirect(route('login'));
    }

    public function test_admin_calls_data_requires_authentication(): void
    {
        $this->get('/admin/calls/data')->assertRedirect(route('login'));
    }

    public function test_admin_calls_data_returns_json_for_verified_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/admin/calls/data');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }
}
