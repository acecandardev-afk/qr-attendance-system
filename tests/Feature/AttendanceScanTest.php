<?php

namespace Tests\Feature;

use Tests\TestCase;

class AttendanceScanTest extends TestCase
{
    public function test_login_routes_are_available_for_guests(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
    }

    public function test_health_endpoint_returns_json_shape(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_password_reset_request_page_is_available_for_guests(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_password_reset_form_renders_with_token(): void
    {
        $this->get('/reset-password/sample-token?email=test%40example.com')->assertOk();
    }
}
