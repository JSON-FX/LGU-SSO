<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class PsgcSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['code' => '0100000000', 'name' => 'Ilocos Norte', 'region_code' => '01'],
            ['code' => '0200000000', 'name' => 'Cagayan', 'region_code' => '02'],
            ['code' => '1300000000', 'name' => 'Metro Manila', 'region_code' => 'NCR'],
            ['code' => '1400000000', 'name' => 'Benguet', 'region_code' => 'CAR'],
            ['code' => '0300000000', 'name' => 'Pampanga', 'region_code' => '03'],
            ['code' => '0400000000', 'name' => 'Batangas', 'region_code' => '04A'],
            ['code' => '0600000000', 'name' => 'Iloilo', 'region_code' => '06'],
            ['code' => '0700000000', 'name' => 'Cebu', 'region_code' => '07'],
            ['code' => '1100000000', 'name' => 'Davao del Sur', 'region_code' => '11'],
        ];

        foreach ($provinces as $province) {
            Province::create($province);
        }

        $cities = [
            ['code' => '1301000000', 'name' => 'Manila', 'province_code' => '1300000000'],
            ['code' => '1302000000', 'name' => 'Quezon City', 'province_code' => '1300000000'],
            ['code' => '1303000000', 'name' => 'Makati', 'province_code' => '1300000000'],
            ['code' => '1304000000', 'name' => 'Pasig', 'province_code' => '1300000000'],
            ['code' => '1305000000', 'name' => 'Taguig', 'province_code' => '1300000000'],
            ['code' => '0701000000', 'name' => 'Cebu City', 'province_code' => '0700000000'],
            ['code' => '0702000000', 'name' => 'Mandaue', 'province_code' => '0700000000'],
            ['code' => '1101000000', 'name' => 'Davao City', 'province_code' => '1100000000'],
            ['code' => '0601000000', 'name' => 'Iloilo City', 'province_code' => '0600000000'],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }

        $barangays = [
            ['code' => '1301001000', 'name' => 'Binondo', 'city_code' => '1301000000'],
            ['code' => '1301002000', 'name' => 'Ermita', 'city_code' => '1301000000'],
            ['code' => '1301003000', 'name' => 'Intramuros', 'city_code' => '1301000000'],
            ['code' => '1301004000', 'name' => 'Malate', 'city_code' => '1301000000'],
            ['code' => '1302001000', 'name' => 'Bagong Pag-asa', 'city_code' => '1302000000'],
            ['code' => '1302002000', 'name' => 'Commonwealth', 'city_code' => '1302000000'],
            ['code' => '1303001000', 'name' => 'Bel-Air', 'city_code' => '1303000000'],
            ['code' => '1303002000', 'name' => 'Poblacion', 'city_code' => '1303000000'],
            ['code' => '1304001000', 'name' => 'Kapitolyo', 'city_code' => '1304000000'],
            ['code' => '1305001000', 'name' => 'Bonifacio Global City', 'city_code' => '1305000000'],
        ];

        foreach ($barangays as $barangay) {
            Barangay::create($barangay);
        }
    }
}
