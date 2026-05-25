<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.name', $user->name);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid credentials');

        $this->assertGuest();
    }

    public function test_public_register_endpoint_is_not_available(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_create_user_via_protected_endpoint(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.email', 'created@example.com');

        $this->assertDatabaseHas('users', ['email' => 'created@example.com']);
    }

    public function test_guest_cannot_create_user_via_protected_endpoint(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Created User',
            'email' => 'created@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertUnauthorized();
    }
}
