<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        if ($permissions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found.',
                'permissions' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data retrieval was successful.',
            'permissions' => $permissions
        ]);
    }

    public function paginate(Request $request)
    {
        try {
            $query = Permission::query();
            $query->orderBy('created_at', 'desc');

            // Optional search by permission name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Handle pagination
            $perPage = $request->query('per_page', 10);
            $permissions = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $permissions->items(),
                'meta' => [
                    'current_page' => $permissions->currentPage(),
                    'last_page' => $permissions->lastPage(),
                    'per_page' => $permissions->perPage(),
                    'total' => $permissions->total()
                ],
                'links' => [
                    'first' => $permissions->url(1),
                    'last' => $permissions->url($permissions->lastPage()),
                    'prev' => $permissions->previousPageUrl(),
                    'next' => $permissions->nextPageUrl()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve permission data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $permission = Permission::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    public function show($id)
    {
        try {
            $permission = Permission::findOrFail($id);
            return response()->json([
                'status' => true,
                'permission' => $permission
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $permission->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Permission updated successfully',
            'permission' => $permission
        ]);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'status' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }
}
