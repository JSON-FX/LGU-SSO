<?php

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        Application::create([
            'name' => 'Admin App Management System',
            'description' => 'The frontend admin application for managing the SSO system',
            'client_id' => 'aams-client-'.Str::random(20),
            'client_secret' => Hash::make('aams-secret-key'),
            'redirect_uris' => ['http://aams.lgu-sso.test/callback'],
            'rate_limit_per_minute' => 100,
        ]);

        Application::create([
            'name' => 'HR Management System',
            'description' => 'Human Resources management application',
            'client_id' => 'hrms-client-'.Str::random(20),
            'client_secret' => Hash::make('hrms-secret-key'),
            'redirect_uris' => ['http://hrms.lgu-sso.test/callback'],
            'rate_limit_per_minute' => 60,
        ]);

        Application::create([
            'name' => 'Document Tracking System',
            'description' => 'Document tracking and management application',
            'client_id' => 'dts-client-'.Str::random(20),
            'client_secret' => Hash::make('dts-secret-key'),
            'redirect_uris' => ['http://dts.lgu-sso.test/callback'],
            'rate_limit_per_minute' => 60,
        ]);

        Application::create([
            'name' => 'LGU-Chat',
            'description' => 'LGU Chat messaging application',
            'client_id' => 'lguchat-client-28f6267b251e22159a55',
            'client_secret' => Hash::make('724c8d217e19b81711ea725904ea41d467df591d'),
            'redirect_uris' => ['http://chat.local:8080/api/auth/sso/callback'],
            'rate_limit_per_minute' => 100,
        ]);
    }
}
