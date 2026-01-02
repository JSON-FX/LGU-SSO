<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    public function definition(): array
    {
        $offices = [
            ['Municipal Budget Office', 'MBO'],
            ['Municipal Accounting Office', 'MAO'],
            ['Municipal Treasurer\'s Office', 'MTO'],
            ['Municipal Planning and Development Office', 'MPDO'],
            ['Municipal Engineering Office', 'MEO'],
            ['Municipal Health Office', 'MHO'],
            ['Municipal Social Welfare and Development Office', 'MSWDO'],
            ['Municipal Agriculture Office', 'MAgrO'],
            ['Human Resource Management Office', 'HRMO'],
            ['Municipal Civil Registrar Office', 'MCRO'],
        ];

        $office = fake()->unique()->randomElement($offices);

        return [
            'name' => $office[0],
            'abbreviation' => $office[1],
        ];
    }
}
