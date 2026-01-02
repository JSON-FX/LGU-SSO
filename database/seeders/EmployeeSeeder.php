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
            'birthday' => '1990-01-15',
            'civil_status' => CivilStatus::Single,
            'province_code' => '1300000000',
            'city_code' => '1303000000',
            'barangay_code' => '1303001000',
            'residence' => '123 Admin Street',
            'nationality' => 'Filipino',
            'email' => 'admin@lgu-sso.test',
            'password' => 'password',
        ]);

        $employee1 = Employee::create([
            'first_name' => 'Juan',
            'middle_name' => 'Cruz',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'birthday' => '1985-06-20',
            'civil_status' => CivilStatus::Married,
            'province_code' => '1300000000',
            'city_code' => '1302000000',
            'barangay_code' => '1302001000',
            'residence' => '456 Sample Street',
            'nationality' => 'Filipino',
            'email' => 'juan@lgu-sso.test',
            'password' => 'password',
        ]);

        $employee2 = Employee::create([
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'birthday' => '1992-03-10',
            'civil_status' => CivilStatus::Single,
            'province_code' => '0700000000',
            'city_code' => '0701000000',
            'barangay_code' => null,
            'residence' => '789 Cebu Street',
            'nationality' => 'Filipino',
            'email' => 'maria@lgu-sso.test',
            'password' => 'password',
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
