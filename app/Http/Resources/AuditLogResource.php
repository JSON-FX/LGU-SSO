<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'uuid' => $this->employee->uuid,
                'full_name' => $this->employee->full_name,
                'email' => $this->employee->email,
            ]),
            'application' => $this->whenLoaded('application', fn () => [
                'uuid' => $this->application->uuid,
                'name' => $this->application->name,
            ]),
            'action' => $this->action,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
