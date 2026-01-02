<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OAuthToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $employee = Employee::query()
            ->where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (! $employee || ! Hash::check($request->password, $employee->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = JWTAuth::fromUser($employee);

        OAuthToken::create([
            'employee_id' => $employee->id,
            'access_token' => hash('sha256', $token),
        ]);

        AuditLog::log('login', $employee);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'employee' => new EmployeeResource($employee),
        ]);
    }

    public function logout(): JsonResponse
    {
        $employee = auth()->user();
        $token = JWTAuth::getToken();

        if ($token) {
            $hashedToken = hash('sha256', $token->get());
            OAuthToken::query()
                ->where('access_token', $hashedToken)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            JWTAuth::invalidate($token);
        }

        AuditLog::log('logout', $employee);

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    public function logoutAll(): JsonResponse
    {
        $employee = auth()->user();

        OAuthToken::query()
            ->where('employee_id', $employee->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $token = JWTAuth::getToken();
        if ($token) {
            JWTAuth::invalidate($token);
        }

        AuditLog::log('logout_all', $employee);

        return response()->json([
            'message' => 'Successfully logged out from all sessions.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $employee = auth()->user();
        $oldToken = JWTAuth::getToken();

        if ($oldToken) {
            $hashedOldToken = hash('sha256', $oldToken->get());
            OAuthToken::query()
                ->where('access_token', $hashedOldToken)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        $newToken = JWTAuth::refresh();

        OAuthToken::create([
            'employee_id' => $employee->id,
            'access_token' => hash('sha256', $newToken),
        ]);

        AuditLog::log('token_refresh', $employee);

        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'bearer',
        ]);
    }

    public function me(): JsonResponse
    {
        $employee = auth()->user();
        $employee->load(['province', 'city', 'barangay', 'applications']);

        return response()->json([
            'data' => new EmployeeResource($employee),
        ]);
    }
}
