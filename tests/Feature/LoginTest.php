<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function seedAdmin(): User
    {
        return User::create([
            'user_id' => 'ADMIN-001',
            'email' => 'admin@school.edu',
            'password' => 'secret123',
            'role' => 'admin',
            'first_name' => 'Admin',
            'middle_name' => null,
            'last_name' => 'User',
            'department_id' => null,
            'status' => 'active',
        ]);
    }

    public function test_login_with_user_id_succeeds(): void
    {
        $this->seedAdmin();

        $response = $this->post('/login', [
            'login' => 'ADMIN-001',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_with_email_succeeds(): void
    {
        $this->seedAdmin();

        $response = $this->post('/login', [
            'login' => 'admin@school.edu',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_with_email_is_case_insensitive(): void
    {
        $this->seedAdmin();

        $response = $this->post('/login', [
            'login' => 'ADMIN@SCHOOL.EDU',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $this->seedAdmin();

        $response = $this->post('/login', [
            'login' => 'ADMIN-001',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
