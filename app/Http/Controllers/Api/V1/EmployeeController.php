<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\GrantAppAccessRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Application;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $employees = Employee::query()
            ->with(['province', 'city', 'barangay', 'office'])
            ->paginate(15);

        return EmployeeResource::collection($employees);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => new EmployeeResource($employee),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['province', 'city', 'barangay', 'office', 'applications']);

        return response()->json([
            'data' => new EmployeeResource($employee),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => new EmployeeResource($employee->fresh()),
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->tokens()->update(['revoked_at' => now()]);
        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }

    public function applications(Employee $employee): JsonResponse
    {
        $employee->load('applications');

        return response()->json([
            'data' => $employee->applications->map(fn ($app) => [
                'uuid' => $app->uuid,
                'name' => $app->name,
                'role' => $app->pivot->role,
                'granted_at' => $app->pivot->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function grantAccess(GrantAppAccessRequest $request, Employee $employee): JsonResponse
    {
        $application = Application::where('uuid', $request->application_uuid)->firstOrFail();

        $employee->applications()->syncWithoutDetaching([
            $application->id => ['role' => $request->role],
        ]);

        return response()->json([
            'message' => 'Application access granted successfully.',
        ]);
    }

    public function updateAccess(Employee $employee, Application $application): JsonResponse
    {
        $validated = request()->validate([
            'role' => ['required', 'string'],
        ]);

        $employee->applications()->updateExistingPivot($application->id, [
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Application access updated successfully.',
        ]);
    }

    public function revokeAccess(Employee $employee, Application $application): JsonResponse
    {
        $employee->applications()->detach($application->id);

        return response()->json([
            'message' => 'Application access revoked successfully.',
        ]);
    }
}
