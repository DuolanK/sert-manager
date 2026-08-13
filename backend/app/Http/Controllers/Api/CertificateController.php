<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'certificates:list:' . md5(json_encode($request->only(['search', 'status', 'page', 'per_page'])));

        return Cache::tags(['certificates'])->remember($cacheKey, 60, function () use ($request) {
            $query = Certificate::query();

            if ($request->filled('search')) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min((int) $request->input('per_page', 15), 100);
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
            'expires_at' => 'required|date|after:today',
            'status' => 'sometimes|in:active,expired,redeemed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $certificate = Certificate::create($validator->validated());

        Cache::tags(['certificates'])->flush();

        return response()->json($certificate, 201);
    }

    public function show(Certificate $certificate)
    {
        return response()->json($certificate);
    }

    public function update(Request $request, Certificate $certificate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0.01',
            'expires_at' => 'sometimes|date|after:today',
            'status' => 'sometimes|in:active,expired,redeemed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $certificate->update($validator->validated());

        Cache::tags(['certificates'])->flush();

        return response()->json($certificate);
    }

    public function destroy(Certificate $certificate)
    {
        $certificate->delete(); // soft delete

        Cache::tags(['certificates'])->flush();

        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $certificate = Certificate::withTrashed()->findOrFail($id);
        $certificate->restore();

        Cache::tags(['certificates'])->flush();

        return response()->json($certificate);
    }

    public function forceDestroy($id)
    {
        $certificate = Certificate::withTrashed()->findOrFail($id);
        $certificate->forceDelete();

        Cache::tags(['certificates'])->flush();

        return response()->json(null, 204);
    }
}
