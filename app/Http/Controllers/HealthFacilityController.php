<?php

namespace App\Http\Controllers;

use App\Models\HealthFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HealthFacilityController extends Controller
{
    public function index(Request $request)
{
    try {
        $query = HealthFacility::with(['type', 'medicalDevices']);

        $query->orderBy('created_at', 'desc');

        // Filter by search (name)
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by type_ids[]
        if ($request->has('type_ids')) {
            $typeIds = $request->input('type_ids');
            if (is_array($typeIds) && count($typeIds) > 0) {
                $query->whereIn('type_of_health_facility_id', $typeIds);
            }
        }

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
                'total' => $facilities->total(),
            ],
            'links' => [
                'first' => $facilities->url(1),
                'last' => $facilities->url($facilities->lastPage()),
                'prev' => $facilities->previousPageUrl(),
                'next' => $facilities->nextPageUrl(),
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


    public function show($slug)
    {
        try {
            $facility = HealthFacility::with(['type', 'medicalDevices'])
                ->where('slug', $slug)
                ->firstOrFail();

            return response()->json([
                'status' => true,
                'message' => 'Health facility retrieved successfully',
                'data' => $facility
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Health facility not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'type_of_health_facility_id' => 'required|exists:type_of_health_facilities,id',
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:health_facilities',
                'email' => 'nullable|email',
                'city' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'lat' => 'nullable|numeric|between:-90,90',
                'lng' => 'nullable|numeric|between:-180,180',
                'medical_device_ids' => 'nullable|array',
                'medical_device_ids.*' => 'exists:medical_devices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Generate slug jika tidak ada
            if (empty($validated['slug'])) {
                $validated['slug'] = $this->generateUniqueSlug($validated['name']);
            }

            // Auto geocode jika address ada tapi koordinat tidak ada
            if (!empty($validated['address']) && (empty($validated['lat']) || empty($validated['lng']))) {
                $coordinates = $this->geocodeAddress($validated['address']);
                if ($coordinates) {
                    $validated['lat'] = $coordinates['lat'];
                    $validated['lng'] = $coordinates['lng'];
                }
            }

            // Pisahkan medical device ids dari data utama
            $medicalDeviceIds = $validated['medical_device_ids'] ?? [];
            unset($validated['medical_device_ids']);

            $facility = HealthFacility::create($validated);

            // Attach medical devices
            if (!empty($medicalDeviceIds)) {
                $facility->medicalDevices()->attach($medicalDeviceIds);
            }

            $facility->load(['type', 'medicalDevices']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Health facility created successfully',
                'data' => $facility,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to create health facility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $facility = HealthFacility::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'type_of_health_facility_id' => 'required|exists:type_of_health_facilities,id',
                'name' => 'required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:health_facilities,slug,' . $facility->id,
                'email' => 'nullable|email',
                'city' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'lat' => 'nullable|numeric|between:-90,90',
                'lng' => 'nullable|numeric|between:-180,180',
                'medical_device_ids' => 'nullable|array',
                'medical_device_ids.*' => 'exists:medical_devices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Generate slug jika name diubah dan berbeda dari yang lama
            if (isset($validated['name']) && $validated['name'] !== $facility->name) {
                // Jika slug tidak disediakan atau sama dengan slug lama, generate yang baru
                if (!isset($validated['slug']) || $validated['slug'] === $facility->slug) {
                    $validated['slug'] = $this->generateUniqueSlug($validated['name'], $facility->id);
                }
            }

            // Auto geocode jika address diubah tapi koordinat tidak diubah
            if (isset($validated['address']) && $validated['address'] !== $facility->address) {
                if (!isset($validated['lat']) || !isset($validated['lng'])) {
                    $coordinates = $this->geocodeAddress($validated['address']);
                    if ($coordinates) {
                        $validated['lat'] = $coordinates['lat'];
                        $validated['lng'] = $coordinates['lng'];
                    }
                }
            }

            // Pisahkan medical device ids dari data utama
            $medicalDeviceIds = $validated['medical_device_ids'] ?? null;
            unset($validated['medical_device_ids']);

            $facility->update($validated);

            // Sync medical devices (mengganti semua relasi)
            if ($medicalDeviceIds !== null) {
                $facility->medicalDevices()->sync($medicalDeviceIds);
            }

            $facility->load(['type', 'medicalDevices']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Health facility updated successfully',
                'data' => $facility,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update health facility',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $facility = HealthFacility::findOrFail($id);
            
            // Detach semua medical devices
            $facility->medicalDevices()->detach();
            
            $facility->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Health facility deleted successfully',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete health facility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Method untuk menambah medical device ke health facility
    public function addMedicalDevice(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $facility = HealthFacility::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'medical_device_ids' => 'required|array',
                'medical_device_ids.*' => 'exists:medical_devices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $medicalDeviceIds = $request->medical_device_ids;
            
            // Attach medical devices (tidak mengganti yang sudah ada)
            $facility->medicalDevices()->attach($medicalDeviceIds);

            $facility->load(['type', 'medicalDevices']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Medical devices added successfully',
                'data' => $facility,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to add medical devices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Method untuk menghapus medical device dari health facility
    public function removeMedicalDevice(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $facility = HealthFacility::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'medical_device_ids' => 'required|array',
                'medical_device_ids.*' => 'exists:medical_devices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $medicalDeviceIds = $request->medical_device_ids;
            
            // Detach medical devices
            $facility->medicalDevices()->detach($medicalDeviceIds);

            $facility->load(['type', 'medicalDevices']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Medical devices removed successfully',
                'data' => $facility,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to remove medical devices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper method untuk generate unique slug
    private function generateUniqueSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        $query = HealthFacility::where('slug', $slug);
        
        // Exclude current record saat update
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            
            $query = HealthFacility::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    // Helper method untuk geocoding (placeholder - implementasi sesuai kebutuhan)
    private function geocodeAddress($address)
    {
        // Implementasi geocoding menggunakan Google Maps API atau service lainnya
        // Return array dengan 'lat' dan 'lng' atau null jika gagal
        return null;
    }
}