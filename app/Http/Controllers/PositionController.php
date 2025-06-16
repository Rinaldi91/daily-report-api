<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        try{
            $query = Position::query(); 
            
            $query->orderBy('created_at', 'desc');

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->has('all')) {
                $positions = $query->get(); // Gunakan get() untuk mengambil semua record

                return response()->json([
                    'status' => true,
                    'message' => 'All data retrieved successfully',
                    'data' => $positions,
                ], 200);
            }


            // Pagination
            $perPage = $request->per_page ?? 10;
            $positions = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $positions->items(),
                'meta' => [
                    'current_page' => $positions->currentPage(),
                    'last_page' => $positions->lastPage(),
                    'per_page' => $positions->perPage(),
                    'total' => $positions->total()
                ],
                'links' => [
                    'first' => $positions->url(1),
                    'last' => $positions->url($positions->lastPage()),
                    'prev' => $positions->previousPageUrl(),
                    'next' => $positions->nextPageUrl()
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
                'slug' => 'nullable|string|max:255|unique:positions',
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

            $position = Position::create($data);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Position created successfully',
                'data' => $position
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $position = Position::findOrFail($id);  

            return response()->json([
                'status' => true,
                'message' => 'Position retrieved successfully',
                'data' => $position
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Position not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $position = Position::where('id', $id)->firstOrFail();  

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:positions,slug,' . $position->id,
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

            $position->update($data);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Position updated successfully',
                'data' => $position
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $position = Position::where('id', $id)->firstOrFail();
            $position->delete();

            return response()->json([
                'status' => true,
                'message' => 'Position deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete position',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
