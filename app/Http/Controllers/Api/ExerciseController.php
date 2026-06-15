<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExerciseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exercise::query()
            ->orderBy('name')
            ->orderBy('id');

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('muscle_group', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%");
            });
        }

        return ExerciseResource::collection($query->paginate(20));
    }

    public function show(Exercise $exercise): ExerciseResource
    {
        return new ExerciseResource($exercise);
    }

    public function store(ExerciseRequest $request): JsonResponse
    {
        $exercise = Exercise::create($request->validated());

        return (new ExerciseResource($exercise))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ExerciseRequest $request, Exercise $exercise): ExerciseResource
    {
        $exercise->update($request->validated());

        return new ExerciseResource($exercise);
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $exercise->delete();

        return response()->json(null, 204);
    }
}
