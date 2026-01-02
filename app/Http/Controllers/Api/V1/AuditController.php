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

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        return AuditLogResource::collection($query->paginate(50));
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
