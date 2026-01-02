<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'full_name' => $this->full_name,
            'initials' => $this->initials,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'age' => $this->age,
            'civil_status' => $this->civil_status?->value,
            'province' => $this->whenLoaded('province', fn () => new LocationResource($this->province)),
            'city' => $this->whenLoaded('city', fn () => new LocationResource($this->city)),
            'barangay' => $this->whenLoaded('barangay', fn () => new LocationResource($this->barangay)),
            'residence' => $this->residence,
            'block_number' => $this->block_number,
            'building_floor' => $this->building_floor,
            'house_number' => $this->house_number,
            'nationality' => $this->nationality,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'applications' => $this->whenLoaded('applications', fn () => $this->applications->map(fn ($app) => [
                'uuid' => $app->uuid,
                'name' => $app->name,
                'role' => $app->pivot->role,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
