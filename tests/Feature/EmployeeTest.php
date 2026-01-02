<?php

use App\Enums\AppRole;
use App\Models\Application;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Employee::factory()->create();
});

it('can list employees', function () {
    Employee::factory()->count(3)->create();

    $response = $this->actingAs($this->admin, 'api')
        ->getJson('/api/v1/employees');

    $response->assertSuccessful()
        ->assertJsonCount(4, 'data');
});

it('can create an employee', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson('/api/v1/employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '1990-01-15',
            'civil_status' => 'single',
            'residence' => '123 Test Street',
            'nationality' => 'Filipino',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.first_name', 'John')
        ->assertJsonPath('data.email', 'john.doe@example.com');
});

it('can show an employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->getJson("/api/v1/employees/{$employee->uuid}");

    $response->assertSuccessful()
        ->assertJsonPath('data.uuid', $employee->uuid);
});

it('can update an employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->putJson("/api/v1/employees/{$employee->uuid}", [
            'first_name' => 'Updated Name',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.first_name', 'Updated Name');
});

it('can delete an employee', function () {
    $employee = Employee::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson("/api/v1/employees/{$employee->uuid}");

    $response->assertSuccessful();

    $this->assertSoftDeleted('employees', ['id' => $employee->id]);
});

it('can grant application access to employee', function () {
    $employee = Employee::factory()->create();
    $application = Application::factory()->create();

    $response = $this->actingAs($this->admin, 'api')
        ->postJson("/api/v1/employees/{$employee->uuid}/applications", [
            'application_uuid' => $application->uuid,
            'role' => AppRole::Standard->value,
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('employee_application', [
        'employee_id' => $employee->id,
        'application_id' => $application->id,
        'role' => AppRole::Standard->value,
    ]);
});

it('can revoke application access from employee', function () {
    $employee = Employee::factory()->create();
    $application = Application::factory()->create();
    $employee->applications()->attach($application->id, ['role' => AppRole::Standard->value]);

    $response = $this->actingAs($this->admin, 'api')
        ->deleteJson("/api/v1/employees/{$employee->uuid}/applications/{$application->uuid}");

    $response->assertSuccessful();

    $this->assertDatabaseMissing('employee_application', [
        'employee_id' => $employee->id,
        'application_id' => $application->id,
    ]);
});
