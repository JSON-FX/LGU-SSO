<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Traits\SetsSsoCookie;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OAuthToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SsoController extends Controller
{
    use SetsSsoCookie;

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

            $employee->load(['office', 'position', 'applications']);

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

    public function validateRedirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url'],
        ]);

        $application = Application::where('client_id', $validated['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $application) {
            return response()->json([
                'valid' => false,
                'message' => 'Application not found or inactive.',
            ], 404);
        }

        $allowedUris = $application->redirect_uris ?? [];

        if (! in_array($validated['redirect_uri'], $allowedUris, true)) {
            return response()->json([
                'valid' => false,
                'message' => 'Redirect URI is not allowed for this application.',
            ], 403);
        }

        return response()->json([
            'valid' => true,
            'application_name' => $application->name,
        ]);
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

        $employee->load(['office', 'position', 'applications']);
        $role = $application instanceof Application ? $employee->getRoleFor($application) : null;

        return response()->json([
            'data' => new EmployeeResource($employee),
            'role' => $role?->value,
        ]);
    }

    /**
     * Public session check for the SSO login page.
     * Reads the SSO cookie and returns the token if valid.
     * No app credentials required — used by SSO-UI to auto-authenticate.
     */
    public function sessionCheck(Request $request): JsonResponse
    {
        $cookieName = config('sso.cookie_name');
        $token = $request->cookie($cookieName);

        if (! $token) {
            return response()->json([
                'authenticated' => false,
            ]);
        }

        try {
            JWTAuth::setToken($token);
            JWTAuth::getPayload();
            $employee = Employee::find(JWTAuth::getPayload()->get('sub'));

            if (! $employee || ! $employee->is_active) {
                $response = response()->json([
                    'authenticated' => false,
                ]);

                return $this->clearSsoCookie($response);
            }

            return response()->json([
                'authenticated' => true,
                'access_token' => $token,
            ]);
        } catch (JWTException $e) {
            $response = response()->json([
                'authenticated' => false,
            ]);

            return $this->clearSsoCookie($response);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $cookieName = config('sso.cookie_name');
        $token = $request->cookie($cookieName);

        if (! $token) {
            return response()->json([
                'authenticated' => false,
                'message' => 'No SSO cookie present.',
            ]);
        }

        try {
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            $employee = Employee::find($payload->get('sub'));

            if (! $employee || ! $employee->is_active) {
                $response = response()->json([
                    'authenticated' => false,
                    'message' => 'Invalid or inactive employee.',
                ]);

                return $this->clearSsoCookie($response);
            }

            $application = $request->attributes->get('application');
            AuditLog::log('sso_check', $employee, $application);

            $employee->load(['office']);

            return response()->json([
                'authenticated' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'employee' => new EmployeeResource($employee),
            ]);
        } catch (JWTException $e) {
            $response = response()->json([
                'authenticated' => false,
                'message' => 'Invalid or expired token.',
            ]);

            return $this->clearSsoCookie($response);
        }
    }

    public function cookieLogout(Request $request): JsonResponse
    {
        $cookieName = config('sso.cookie_name');
        $token = $request->cookie($cookieName);

        if (! $token) {
            $response = response()->json([
                'message' => 'No SSO cookie present.',
            ]);

            return $this->clearSsoCookie($response);
        }

        try {
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            $employee = Employee::find($payload->get('sub'));

            if ($employee) {
                $hashedToken = hash('sha256', $token);
                OAuthToken::query()
                    ->where('access_token', $hashedToken)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);

                $application = $request->attributes->get('application');
                AuditLog::log('sso_logout', $employee, $application);
            }

            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            // Token already invalid — still clear the cookie
        }

        $response = response()->json([
            'message' => 'Successfully logged out.',
        ]);

        return $this->clearSsoCookie($response);
    }
}
