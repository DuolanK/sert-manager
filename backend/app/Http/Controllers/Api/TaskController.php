<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'tasks:list:' . md5(json_encode($request->only(['search', 'completed', 'page', 'per_page'])));

        return Cache::tags(['tasks'])->remember($cacheKey, 60, function () use ($request) {
            $query = Task::query();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($request->search) . '%'])
                        ->orWhereRaw('LOWER(executor) LIKE ?', ['%' . strtolower($request->search) . '%']);
                });
            }

            if ($request->filled('completed')) {
                $query->where('completed', filter_var($request->completed, FILTER_VALIDATE_BOOLEAN));
            }

            $perPage = min((int) $request->input('per_page', 15), 100);
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'executor' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = Task::create($validator->validated());

        Cache::tags(['tasks'])->flush();

        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'executor' => 'sometimes|nullable|string|max:255',
            'due_date' => 'sometimes|nullable|date',
            'completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($validator->validated());

        Cache::tags(['tasks'])->flush();

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete(); // soft delete

        Cache::tags(['tasks'])->flush();

        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        Cache::tags(['tasks'])->flush();

        return response()->json($task);
    }

    public function forceDestroy($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->forceDelete();

        Cache::tags(['tasks'])->flush();

        return response()->json(null, 204);
    }
}
