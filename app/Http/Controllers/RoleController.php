<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Role::with('permissions');

            // Optional search by role name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Handle pagination
            $perPage = $request->query('per_page', 10);
            $roles = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $roles->items(),
                'meta' => [
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total()
                ],
                'links' => [
                    'first' => $roles->url(1),
                    'last' => $roles->url($roles->lastPage()),
                    'prev' => $roles->previousPageUrl(),
                    'next' => $roles->nextPageUrl()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve role data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'role' => $role->load('permissions')
        ], 201);
    }

    public function show($id)
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            return response()->json([
                'status' => true,
                'role' => $role
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
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully',
            'role' => $role->fresh()->load('permissions')
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
