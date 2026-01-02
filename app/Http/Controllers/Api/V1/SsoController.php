<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SsoController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $token = $request->input('token') ?? $request->bearerToken();

        if (! $token) {
            return response()->json([
                'valid' => false,
                'message' => 'Token is required.',
            ], 400);
        }

        try {
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            $employee = Employee::find($payload->get('sub'));

            if (! $employee || ! $employee->is_active) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid or inactive employee.',
                ], 401);
            }

            $application = $request->attributes->get('application');
            AuditLog::log('token_validate', $employee, $application);

            $employee->load(['province', 'city', 'barangay']);

            return response()->json([
                'valid' => true,
                'data' => new EmployeeResource($employee),
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token.',
            ], 401);
        }
    }

    public function authorize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            JWTAuth::setToken($validated['token']);
            $payload = JWTAuth::getPayload();
            $employee = Employee::find($payload->get('sub'));

            if (! $employee || ! $employee->is_active) {
                return response()->json([
                    'authorized' => false,
                    'message' => 'Invalid or inactive employee.',
                ], 401);
            }

            $application = $request->attributes->get('application');

            if (! $application instanceof Application) {
                return response()->json([
                    'authorized' => false,
                    'message' => 'Application not found.',
                ], 400);
            }

            if (! $employee->hasAccessTo($application)) {
                return response()->json([
                    'authorized' => false,
                    'message' => 'Employee does not have access to this application.',
                ], 403);
            }

            $role = $employee->getRoleFor($application);
            AuditLog::log('app_authorize', $employee, $application);

            return response()->json([
                'authorized' => true,
                'role' => $role?->value,
                'employee' => [
                    'uuid' => $employee->uuid,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'authorized' => false,
                'message' => 'Invalid token.',
            ], 401);
        }
    }

    public function employee(Request $request): JsonResponse
    {
        $employee = auth()->user();

        if (! $employee instanceof Employee) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $application = $request->attributes->get('application');

        if ($application instanceof Application && ! $employee->hasAccessTo($application)) {
            return response()->json([
                'message' => 'Employee does not have access to this application.',
            ], 403);
        }

        $employee->load(['province', 'city', 'barangay']);
        $role = $application instanceof Application ? $employee->getRoleFor($application) : null;

        return response()->json([
            'data' => new EmployeeResource($employee),
            'role' => $role?->value,
        ]);
    }
}
