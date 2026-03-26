<?php

namespace Database\Seeders;

use App\Enums\AppRole;
use App\Enums\CivilStatus;
use App\Models\Application;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Employee::create([
            'first_name' => 'Admin',
            'middle_name' => null,
            'last_name' => 'User',
            'username' => Employee::generateUsername('Admin', 'User'),
            'birthday' => '1990-01-15',
            'civil_status' => CivilStatus::Single,
            'province' => 'Metro Manila',
            'city' => 'Quezon City',
            'barangay' => 'Barangay Holy Spirit',
            'residence' => '123 Admin Street',
            'nationality' => 'Filipino',
            'email' => 'admin@lgu-sso.test',
            'password' => 'password',
            'must_change_password' => false,
            'position' => 'System Administrator',
        ]);

        $applications = Application::all();

        foreach ($applications as $app) {
            $admin->applications()->attach($app->id, ['role' => AppRole::SuperAdministrator->value]);
        }
    }
}
