<?php

namespace App\Http\Controllers;

use App\Models\MedicalDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MedicalDeviceController extends Controller
{
    public function index(Request $request)
    {
        try{
            $query = MedicalDevice::query();

            // Handle search by name if provided
            if ($request->has('search')) {
                $query->where('brand', 'like', '%' . $request->search . '%');
            }

            // Pagination
            $perPage = $request->per_page ?? 10;
            $devices = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $devices->items(),
                'meta' => [
                    'current_page' => $devices->currentPage(),
                    'last_page' => $devices->lastPage(),
                    'per_page' => $devices->perPage(),
                    'total' => $devices->total()
                ],
                'links' => [
                    'first' => $devices->url(1),
                    'last' => $devices->url($devices->lastPage()),
                    'prev' => $devices->previousPageUrl(),
                    'next' => $devices->nextPageUrl()
                ]
            ], 200);

        }catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $device = MedicalDevice::findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Medical device retrieved successfully',
                'data' => $device
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Medical device not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'brand' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'serial_number' => 'required|string|max:255|unique:medical_devices',
                'status' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'software_version' => 'nullable|string',
                'medical_device_category_id' => 'nullable|exists:medical_device_categories,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            $device = MedicalDevice::create($data);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Medical device created successfully',
                'data' => $device
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create medical device',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $device = MedicalDevice::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'brand' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'serial_number' => 'required|string|max:255|unique:medical_devices,serial_number,' . $device->id,
                'status' => 'required|string',
                'notes' => 'nullable|string',
                'software_version' => 'nullable|string',
                'medical_device_category_id' => 'nullable|exists:medical_device_categories,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            $device->update($data);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Medical device updated successfully',
                'data' => $device
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update medical device',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $device = MedicalDevice::findOrFail($id);
            $device->delete();

            return response()->json([
                'status' => true,
                'message' => 'Medical device deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete medical device',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
