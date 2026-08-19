<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Cache version key. Bumping it (via put) invalidates every cached task
     * list regardless of the underlying cache store.
     *
     * We deliberately avoid Cache::tags() here: the `database` and `file`
     * stores do not support tagging and throw BadMethodCallException
     * ("This cache store does not support tagging"), which is exactly what
     * happened on the server when CACHE_STORE fell back to `database`.
     */
    private const CACHE_VERSION_KEY = 'tasks:cache:version';

    public function index(Request $request)
    {
        $cacheKey = 'tasks:list:' . $this->cacheVersion() . ':' . md5(json_encode($request->only(['search', 'completed', 'page', 'per_page'])));

        return $this->remember($cacheKey, 60, function () use ($request) {
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

        $this->flushCache();

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

        $this->flushCache();

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete(); // soft delete

        $this->flushCache();

        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        $this->flushCache();

        return response()->json($task);
    }

    public function forceDestroy($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->forceDelete();

        $this->flushCache();

        return response()->json(null, 204);
    }

    /**
     * Returns the current cache-generation token, defaulting to '0' when the
     * cache store is unavailable. The token is embedded in every list key so
     * that a single version bump invalidates all previously cached lists.
     */
    private function cacheVersion(): string
    {
        try {
            return (string) (Cache::get(self::CACHE_VERSION_KEY) ?? '0');
        } catch (\Throwable $e) {
            return '0';
        }
    }

    /**
     * Cache is an optimization; the database is the source of truth.
     * If the cache store is unavailable, serve the uncached result instead
     * of a 500 and record the real reason in the log.
     */
    private function remember(string $cacheKey, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::error('Cache unavailable, serving uncached data: ' . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Invalidates cached task lists by advancing the cache version. Works on
     * every cache store (database, file, redis, ...), unlike Cache::tags().
     */
    private function flushCache(): void
    {
        try {
            Cache::put(self::CACHE_VERSION_KEY, (string) time());
        } catch (\Throwable $e) {
            Log::error('Cache flush failed: ' . $e->getMessage());
        }
    }
}
