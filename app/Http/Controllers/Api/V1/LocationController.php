<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    public function regions(): AnonymousResourceCollection
    {
        $regions = Region::query()
            ->orderBy('name')
            ->get();

        return LocationResource::collection($regions);
    }

    public function provincesByRegion(string $regionCode): AnonymousResourceCollection
    {
        $provinces = Province::query()
            ->where('region_code', $regionCode)
            ->orderBy('name')
            ->get();

        return LocationResource::collection($provinces);
    }

    public function provinces(): AnonymousResourceCollection
    {
        $provinces = Province::query()
            ->orderBy('name')
            ->get();

        return LocationResource::collection($provinces);
    }

    public function cities(string $provinceCode): AnonymousResourceCollection
    {
        $cities = City::query()
            ->where('province_code', $provinceCode)
            ->orderBy('name')
            ->get();

        return LocationResource::collection($cities);
    }

    public function barangays(string $cityCode): AnonymousResourceCollection
    {
        $barangays = Barangay::query()
            ->where('city_code', $cityCode)
            ->orderBy('name')
            ->get();

        return LocationResource::collection($barangays);
    }
}
