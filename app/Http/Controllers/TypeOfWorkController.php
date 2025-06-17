<?php

namespace App\Http\Controllers;

use App\Models\TypeOfWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TypeOfWorkController extends Controller
{
    public function index(Request $request)
    {
        try{
            $query = TypeOfWork::query();

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->has('all')) {
                $typeOfWorks = $query->get(); // Gunakan get() untuk mengambil semua record

                return response()->json([
                    'status' => true,
                    'message' => 'All data retrieved successfully',
                    'data' => $typeOfWorks,
                ], 200);
            }


            // Pagination
            $perPage = $request->per_page ?? 10;
            $typeOfWorks = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $typeOfWorks->items(),
                'meta' => [
                    'current_page' => $typeOfWorks->currentPage(),
                    'last_page' => $typeOfWorks->lastPage(),
                    'per_page' => $typeOfWorks->perPage(),
                    'total' => $typeOfWorks->total()
                ],
                'links' => [
                    'first' => $typeOfWorks->url(1),
                    'last' => $typeOfWorks->url($typeOfWorks->lastPage()),
                    'prev' => $typeOfWorks->previousPageUrl(),
                    'next' => $typeOfWorks->nextPageUrl()
                ]
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:type_of_works',
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

            $typeOfWork = TypeOfWork::create($data);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Type of work created successfully',
                'data' => $typeOfWork
            ], 201);
        }catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create type of work',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $typeOfWork = TypeOfWork::where('slug', $slug)->firstOrFail();  

            return response()->json([
                'status' => true,
                'message' => 'Type of work retrieved successfully',
                'data' => $typeOfWork
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Type of work not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $typeOfWork = TypeOfWork::where('id', $id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:type_of_works,slug,' . $typeOfWork->id,
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

            $typeOfWork->update($data);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Type of work updated successfully',
                'data' => $typeOfWork
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update type of work',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $typeOfWork = TypeOfWork::where('slug', $slug)->firstOrFail();
            $typeOfWork->delete();

            return response()->json([
                'status' => true,
                'message' => 'Type of work deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete type of work',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
