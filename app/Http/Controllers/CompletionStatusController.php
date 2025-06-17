<?php

namespace App\Http\Controllers;

use App\Models\CompletionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompletionStatusController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CompletionStatus::query();

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->has('all')) {
                $completionStatuses = $query->get(); // Gunakan get() untuk mengambil semua record

                return response()->json([
                    'status' => true,
                    'message' => 'All data retrieved successfully',
                    'data' => $completionStatuses,
                ], 200);
            }

            // Pagination
            $perPage = $request->per_page ?? 10;
            $completionStatuses = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $completionStatuses->items(),
                'meta' => [
                    'current_page' => $completionStatuses->currentPage(),
                    'last_page' => $completionStatuses->lastPage(),
                    'per_page' => $completionStatuses->perPage(),
                    'total' => $completionStatuses->total()
                ],
                'links' => [
                    'first' => $completionStatuses->url(1),
                    'last' => $completionStatuses->url($completionStatuses->lastPage()),
                    'prev' => $completionStatuses->previousPageUrl(),
                    'next' => $completionStatuses->nextPageUrl()
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
                'slug' => 'nullable|string|max:255|unique:completion_statuses',
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

            $completionStatus = CompletionStatus::create($data);

            DB::commit();    

            return response()->json([
                'status' => true,
                'message' => 'Completion status created successfully',
                'data' => $completionStatus
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create completion status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $completionStatus = CompletionStatus::where('slug', $slug)->firstOrFail();

            return response()->json([
                'status' => true,
                'message' => 'Completion status retrieved successfully',
                'data' => $completionStatus
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Completion status not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $completionStatus = CompletionStatus::where('id', $id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:completion_statuses,slug,' . $completionStatus->id,
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

            $completionStatus->update($data);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Completion status updated successfully',
                'data' => $completionStatus
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();  
            return response()->json([
                'status' => false,
                'message' => 'Failed to update completion status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $completionStatus = CompletionStatus::where('slug', $slug)->firstOrFail();
            $completionStatus->delete();

            return response()->json([
                'status' => true,
                'message' => 'Completion status deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete completion status',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
