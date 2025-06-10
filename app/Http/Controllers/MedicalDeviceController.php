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
        try {
            $query = MedicalDevice::query();

            // Urutkan berdasarkan data terbaru
            $query->orderBy('created_at', 'desc');

            // Filter pencarian berdasarkan brand, model, atau serial_number (optional)
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('brand', 'like', '%' . $request->search . '%')
                        ->orWhere('model', 'like', '%' . $request->search . '%')
                        ->orWhere('serial_number', 'like', '%' . $request->search . '%');
                });
            }

            // Jika user meminta semua data tanpa pagination
            if (strtoupper($request->page_all) === 'ALL') {
                $devices = $query->get();

                return response()->json([
                    'status' => true,
                    'message' => 'All data retrieved successfully',
                    'data' => $devices,
                    'meta' => [
                        'total' => $devices->count(),
                        'page_all' => true
                    ]
                ], 200);
            }

            // Jika tidak, kembalikan data dengan pagination
            $perPage = is_numeric($request->per_page) ? (int) $request->per_page : 10;
            $devices = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $devices->items(),
                'meta' => [
                    'current_page' => $devices->currentPage(),
                    'last_page' => $devices->lastPage(),
                    'per_page' => $devices->perPage(),
                    'total' => $devices->total(),
                    'page_all' => false
                ],
                'links' => [
                    'first' => $devices->url(1),
                    'last' => $devices->url($devices->lastPage()),
                    'prev' => $devices->previousPageUrl(),
                    'next' => $devices->nextPageUrl()
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
