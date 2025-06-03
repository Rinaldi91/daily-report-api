<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Region::query();

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Pagination
            $perPage = $request->per_page ?? 10;
            $regions = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $regions->items(),
                'meta' => [
                    'current_page' => $regions->currentPage(),
                    'last_page' => $regions->lastPage(),
                    'per_page' => $regions->perPage(),
                    'total' => $regions->total()
                ],
                'links' => [
                    'first' => $regions->url(1),
                    'last' => $regions->url($regions->lastPage()),
                    'prev' => $regions->previousPageUrl(),
                    'next' => $regions->nextPageUrl()
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
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:regions',
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

            $region = Region::create($data);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Region created successfully',
                'data' => $region
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create region',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $region = Region::where('slug', $slug)->firstOrFail();

            return response()->json([
                'status' => true,
                'message' => 'Region retrieved successfully',
                'data' => $region
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Region not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $slug)
    {
        DB::beginTransaction();
        try {
            $category = Region::where('slug', $slug)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:regions,slug,' . $category->id,
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

            $category->update($data);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Region updated successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update Region',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $region = Region::where('slug', $slug)->firstOrFail();
            $region->delete();

            return response()->json([
                'status' => true,
                'message' => 'Region deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete Region',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
