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

        $employee1 = Employee::create([
            'first_name' => 'Juan',
            'middle_name' => 'Cruz',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'username' => Employee::generateUsername('Juan', 'Dela Cruz'),
            'birthday' => '1985-06-20',
            'civil_status' => CivilStatus::Married,
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'barangay' => 'Barangay 1',
            'residence' => '456 Sample Street',
            'nationality' => 'Filipino',
            'email' => 'juan@lgu-sso.test',
            'password' => 'password',
            'must_change_password' => false,
            'position' => 'Clerk',
        ]);

        $employee2 = Employee::create([
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'username' => Employee::generateUsername('Maria', 'Reyes'),
            'birthday' => '1992-03-10',
            'civil_status' => CivilStatus::Single,
            'province' => 'Cebu',
            'city' => 'Cebu City',
            'barangay' => null,
            'residence' => '789 Cebu Street',
            'nationality' => 'Filipino',
            'email' => 'maria@lgu-sso.test',
            'password' => 'password',
            'must_change_password' => false,
            'position' => 'Administrative Officer',
        ]);

        $applications = Application::all();

        foreach ($applications as $app) {
            $admin->applications()->attach($app->id, ['role' => AppRole::SuperAdministrator->value]);
        }

        if ($applications->count() >= 1) {
            $employee1->applications()->attach($applications->first()->id, ['role' => AppRole::Standard->value]);
        }

        if ($applications->count() >= 2) {
            $employee2->applications()->attach($applications->get(1)->id, ['role' => AppRole::Administrator->value]);
        }
    }
}
