<?php

use App\Models\Application;
use App\Models\Employee;
use App\Models\OAuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->employee = Employee::factory()->create([
        'email' => 'sso@example.com',
        'password' => 'password',
    ]);
    $this->application = Application::factory()->create([
        'client_id' => 'sso-test-client',
        'client_secret' => Hash::make('sso-test-secret'),
    ]);
    $this->appHeaders = [
        'X-Client-ID' => 'sso-test-client',
        'X-Client-Secret' => 'sso-test-secret',
    ];
    $this->cookieName = config('sso.cookie_name');
});

it('includes the sso cookie on login response', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'sso@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful();

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getDomain())->toBe(config('sso.cookie_domain'));
});

it('returns authenticated true when sso check has valid cookie', function () {
    $token = JWTAuth::fromUser($this->employee);

    OAuthToken::create([
        'employee_id' => $this->employee->id,
        'access_token' => hash('sha256', $token),
    ]);

    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => $token])
        ->getJson('/api/v1/sso/check', $this->appHeaders);

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', true)
        ->assertJsonStructure([
            'authenticated',
            'access_token',
            'token_type',
            'employee' => ['uuid', 'email'],
        ]);
});

it('returns authenticated false when sso check has no cookie', function () {
    $response = $this->withCredentials()
        ->getJson('/api/v1/sso/check', $this->appHeaders);

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', false);
});

it('returns authenticated false and clears cookie for invalid token', function () {
    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => 'invalid-jwt-token'])
        ->getJson('/api/v1/sso/check', $this->appHeaders);

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', false);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isCleared())->toBeTrue();
});

it('returns authenticated false for inactive employee', function () {
    $this->employee->update(['is_active' => false]);

    $token = JWTAuth::fromUser($this->employee);

    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => $token])
        ->getJson('/api/v1/sso/check', $this->appHeaders);

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', false);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isCleared())->toBeTrue();
});

it('clears cookie and revokes token on cookie logout', function () {
    $token = JWTAuth::fromUser($this->employee);

    $oauthToken = OAuthToken::create([
        'employee_id' => $this->employee->id,
        'access_token' => hash('sha256', $token),
    ]);

    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => $token])
        ->postJson('/api/v1/sso/cookie-logout', [], $this->appHeaders);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Successfully logged out.');

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isCleared())->toBeTrue();

    expect($oauthToken->fresh()->revoked_at)->not->toBeNull();
});

it('clears sso cookie on auth logout', function () {
    $response = $this->actingAs($this->employee, 'api')
        ->postJson('/api/v1/auth/logout');

    $response->assertSuccessful();

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isCleared())->toBeTrue();
});

it('updates sso cookie on auth refresh', function () {
    $token = JWTAuth::fromUser($this->employee);

    OAuthToken::create([
        'employee_id' => $this->employee->id,
        'access_token' => hash('sha256', $token),
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson('/api/v1/auth/refresh');

    $response->assertSuccessful();

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->getValue())->not->toBeEmpty()
        ->and($cookie->isCleared())->toBeFalse();
});

it('requires app credentials for sso check', function () {
    $response = $this->withCredentials()
        ->getJson('/api/v1/sso/check');

    $response->assertUnauthorized();
});

// --- session-check (public endpoint, no app credentials) ---

it('returns authenticated true from session-check with valid cookie', function () {
    $token = JWTAuth::fromUser($this->employee);

    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => $token])
        ->getJson('/api/v1/sso/session-check');

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', true)
        ->assertJsonStructure(['authenticated', 'access_token']);
});

it('returns authenticated false from session-check with no cookie', function () {
    $response = $this->withCredentials()
        ->getJson('/api/v1/sso/session-check');

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', false);
});

it('returns authenticated false from session-check with invalid token', function () {
    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => 'invalid-jwt-token'])
        ->getJson('/api/v1/sso/session-check');

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', false);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isCleared())->toBeTrue();
});

it('returns authenticated false from session-check for inactive employee', function () {
    $this->employee->update(['is_active' => false]);

    $token = JWTAuth::fromUser($this->employee);

    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => $token])
        ->getJson('/api/v1/sso/session-check');

    $response->assertSuccessful()
        ->assertJsonPath('authenticated', false);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $this->cookieName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->isCleared())->toBeTrue();
});

it('does not require app credentials for session-check', function () {
    $token = JWTAuth::fromUser($this->employee);

    $response = $this->withCredentials()
        ->withUnencryptedCookies([$this->cookieName => $token])
        ->getJson('/api/v1/sso/session-check');

    $response->assertSuccessful();
});
