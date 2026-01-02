<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $offices = [
            ['name' => 'Municipal Budget Office', 'abbreviation' => 'MBO'],
            ['name' => 'Municipal Accounting Office', 'abbreviation' => 'MAO'],
            ['name' => 'Municipal Treasurer\'s Office', 'abbreviation' => 'MTO'],
            ['name' => 'Municipal Planning and Development Office', 'abbreviation' => 'MPDO'],
            ['name' => 'Municipal Engineering Office', 'abbreviation' => 'MEO'],
            ['name' => 'Municipal Health Office', 'abbreviation' => 'MHO'],
            ['name' => 'Municipal Social Welfare and Development Office', 'abbreviation' => 'MSWDO'],
            ['name' => 'Municipal Agriculture Office', 'abbreviation' => 'MAgrO'],
            ['name' => 'Human Resource Management Office', 'abbreviation' => 'HRMO'],
            ['name' => 'Municipal Civil Registrar Office', 'abbreviation' => 'MCRO'],
        ];

        foreach ($offices as $office) {
            Office::query()->firstOrCreate(
                ['abbreviation' => $office['abbreviation']],
                $office
            );
        }
    }
}
