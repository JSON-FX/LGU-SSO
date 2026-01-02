<?php

use App\Models\Application;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Employee::factory()->create();
});

it('can list applications', function () {
    Application::factory()->count(3)->create();

    $response = $this->actingAs($this->admin, 'api')
        ->getJson('/api/v1/applications');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('can create an application', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson('/api/v1/applications', [
            'name' => 'Test Application',
            'description' => 'A test application',
            'redirect_uris' => ['http://test.com/callback'],
            'rate_limit_per_minute' => 60,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Test Application')
        ->assertJsonStructure(['client_secret']);
});

it('can show an application', function () {
    $application = Application::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->getJson("/api/v1/applications/{$application->uuid}");

    $response->assertSuccessful()
        ->assertJsonPath('data.uuid', $application->uuid);
});

it('can update an application', function () {
    $application = Application::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->putJson("/api/v1/applications/{$application->uuid}", [
            'name' => 'Updated Name',
            'rate_limit_per_minute' => 100,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.rate_limit_per_minute', 100);
});

it('can delete an application', function () {
    $application = Application::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson("/api/v1/applications/{$application->uuid}");

    $response->assertSuccessful();

    $this->assertSoftDeleted('applications', ['id' => $application->id]);
});

it('can regenerate client secret', function () {
    $application = Application::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->postJson("/api/v1/applications/{$application->uuid}/regenerate-secret");

    $response->assertSuccessful()
        ->assertJsonStructure(['client_secret']);
});
