<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Division::query();
            
            $query->orderBy('created_at', 'desc');

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Pagination
            $perPage = $request->per_page ?? 10;
            $divisions = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $divisions->items(),
                'meta' => [
                    'current_page' => $divisions->currentPage(),
                    'last_page' => $divisions->lastPage(),
                    'per_page' => $divisions->perPage(),
                    'total' => $divisions->total()
                ],
                'links' => [
                    'first' => $divisions->url(1),
                    'last' => $divisions->url($divisions->lastPage()),
                    'prev' => $divisions->previousPageUrl(),
                    'next' => $divisions->nextPageUrl()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve divisions',
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
                'slug' => 'nullable|string|max:255|unique:divisions',
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

            $division = Division::create($data);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Division created successfully',
                'data' => $division
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create division',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $division = Division::findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Division retrieved successfully',
                'data' => $division
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Division not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $division = Division::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:divisions,slug,' . $id,
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

            $division->update($data);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Division updated successfully',
                'data' => $division
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update division',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $division = Division::findOrFail($id);
            $division->delete();

            return response()->json([
                'status' => true,
                'message' => 'Division deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete division',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
