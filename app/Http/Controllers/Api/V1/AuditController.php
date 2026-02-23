<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AuditLog::query()
            ->with(['employee', 'application'])
            ->orderByDesc('created_at');

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('employee_uuid')) {
            $employee = Employee::where('uuid', $request->employee_uuid)->first();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            }
        }

        if ($request->has('application_uuid')) {
            $application = Application::where('uuid', $request->application_uuid)->first();
            if ($application) {
                $query->where('application_id', $application->id);
            }
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $perPage = min($request->input('per_page', 15), 100);

        return AuditLogResource::collection($query->paginate($perPage));
    }

    public function employeeLogs(Employee $employee): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->with(['application'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return AuditLogResource::collection($logs);
    }

    public function applicationLogs(Application $application): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->with(['employee'])
            ->where('application_id', $application->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return AuditLogResource::collection($logs);
    }
}
