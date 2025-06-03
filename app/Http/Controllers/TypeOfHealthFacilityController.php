<?php

namespace App\Http\Controllers;

use App\Models\TypeOfHealthFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TypeOfHealthFacilityController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TypeOfHealthFacility::query();

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Pagination
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
                    'total' => $facilities->total()
                ],
                'links' => [
                    'first' => $facilities->url(1),
                    'last' => $facilities->url($facilities->lastPage()),
                    'prev' => $facilities->previousPageUrl(),
                    'next' => $facilities->nextPageUrl()
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

    public function store(Request $request)
    {
        DB::beginTransaction(); // Mulai transaksi

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:type_of_health_facilities',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $facility = TypeOfHealthFacility::create($data);

            DB::commit(); // Commit transaksi jika semua berjalan lancar

            return response()->json([
                'status' => true,
                'message' => 'Health facility type created successfully',
                'data' => $facility
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi error

            return response()->json([
                'status' => false,
                'message' => 'Failed to create health facility type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $facility = TypeOfHealthFacility::where('slug', $slug)->firstOrFail();

            return response()->json([
                'status' => true,
                'message' => 'Health facility type retrieved successfully',
                'data' => $facility
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Health facility type not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $slug)
    {
        DB::beginTransaction(); // Mulai transaksi

        try {
            $facility = TypeOfHealthFacility::where('slug', $slug)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:type_of_health_facilities,slug,' . $facility->id,
                'description' => 'sometimes|nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Generate slug if name is changed and slug is not provided
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $facility->update($data);

            DB::commit(); // Commit jika tidak ada error

            return response()->json([
                'status' => true,
                'message' => 'Health facility type updated successfully',
                'data' => $facility
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback jika ada error

            return response()->json([
                'status' => false,
                'message' => 'Failed to update health facility type',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $facility = TypeOfHealthFacility::where('slug', $slug)->firstOrFail();
            $facility->delete();

            return response()->json([
                'status' => true,
                'message' => 'Health facility type deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete health facility type',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
