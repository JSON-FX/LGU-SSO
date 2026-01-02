<?php

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->employee = Employee::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('can login with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'employee' => ['uuid', 'email', 'first_name'],
        ]);
});

it('cannot login with invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

it('cannot login with inactive account', function () {
    $this->employee->update(['is_active' => false]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertUnauthorized();
});

it('can get authenticated employee profile', function () {
    $response = $this->actingAs($this->employee, 'api')
        ->getJson('/api/v1/auth/me');

    $response->assertSuccessful()
        ->assertJsonPath('data.email', 'test@example.com');
});

it('can logout', function () {
    $response = $this->actingAs($this->employee, 'api')
        ->postJson('/api/v1/auth/logout');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Successfully logged out.');
});

it('can logout from all sessions', function () {
    $response = $this->actingAs($this->employee, 'api')
        ->postJson('/api/v1/auth/logout-all');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Successfully logged out from all sessions.');
});

it('requires authentication for protected routes', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});
