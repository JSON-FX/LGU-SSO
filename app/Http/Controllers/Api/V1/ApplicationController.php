<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $applications = Application::query()->paginate(15);

        return ApplicationResource::collection($applications);
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $plainSecret = Str::random(40);

        $application = Application::create([
            ...$request->validated(),
            'client_secret' => $plainSecret,
        ]);

        return response()->json([
            'message' => 'Application created successfully.',
            'data' => new ApplicationResource($application),
            'client_secret' => $plainSecret,
        ], 201);
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json([
            'data' => new ApplicationResource($application),
        ]);
    }

    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $application->update($request->validated());

        return response()->json([
            'message' => 'Application updated successfully.',
            'data' => new ApplicationResource($application->fresh()),
        ]);
    }

    public function destroy(Application $application): JsonResponse
    {
        $application->tokens()->update(['revoked_at' => now()]);
        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully.',
        ]);
    }

    public function regenerateSecret(Application $application): JsonResponse
    {
        $plainSecret = $application->generateNewSecret();

        return response()->json([
            'message' => 'Client secret regenerated successfully.',
            'client_secret' => $plainSecret,
        ]);
    }
}
