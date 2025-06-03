<?php

namespace App\Http\Controllers;

use App\Models\HealthFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class HealthFacilityController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = HealthFacility::with('type', 'medicalDevice');

            if ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $perPage = $request->per_page ?? 10;
            $facilities = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $facilities->items(),
                'meta' => [
                    'current_page' => $facilities->currentPage(),
                    'last_page' => $facilities->lastPage(),
                    'per_page' => $facilities->perPage(),
                    'total' => $facilities->total(),
                ],
                'links' => [
                    'first' => $facilities->url(1),
                    'last' => $facilities->url($facilities->lastPage()),
                    'prev' => $facilities->previousPageUrl(),
                    'next' => $facilities->nextPageUrl(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $facility = HealthFacility::with('type')->where('id', $id)->firstOrFail();

            return response()->json([
                'status' => true,
                'message' => 'Health facility retrieved successfully',
                'data' => $facility
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Health facility not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'type_of_health_facility_id' => 'required|exists:type_of_health_facilities,id',
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:health_facilities',
                'email' => 'nullable|email',
                'city' => 'nullable',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
            ]);

            // Generate slug jika tidak ada
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $facility = HealthFacility::create($validated)->load('type');

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Health facility created successfully',
                'data' => $facility,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to create health facility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $facility = HealthFacility::findOrFail($id);

            $validated = $request->validate([
                'type_of_health_facility_id' => 'required|exists:type_of_health_facilities,id',
                'name' => 'required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:health_facilities,slug,' . $facility->id,
                'email' => 'nullable|email',
                'city' => 'nullable',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
            ]);

            // Generate slug jika tidak ada tapi name diubah
            if (!isset($validated['slug']) && isset($validated['name'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $facility->update($validated);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Health facility updated successfully',
                'data' => $facility,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update health facility',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function destroy($id)
    {
        try {
            $facility = HealthFacility::findOrFail($id);
            $facility->delete();

            return response()->json([
                'status' => true,
                'message' => 'Health facility deleted successfully',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete health facility',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
