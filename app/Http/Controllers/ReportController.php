<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportDetail;
use App\Models\ReportDeviceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Location;
use App\Models\Parameter;
use App\Models\PartUsedForImage;
use App\Models\PartUsedForRepair;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Report::with(['Employee', 'HealthFacility', 'ReportDeviceItem']);

            if ($request->has('search')) {
                $query->where('report_number', 'like', '%' . $request->search . '%');
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('created_at')) {
                $query->where('created_at', $request->created_at);
            }

            $perPage = $request->per_page ?? 10;
            $reports = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Report retrieved successfully',
                'data' => $reports
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    private function generateReportNumber()
    {
        try {
            // Format: RPT-YYYYMMDD-XXX
            $today = now()->format('Ymd'); // Format: 20250624
            $prefix = "RPT-{$today}";

            // Cari report terakhir pada hari ini
            $lastReport = Report::where('report_number', 'LIKE', "{$prefix}%")
                ->orderBy('report_number', 'DESC')
                ->first();

            if ($lastReport) {
                // Ambil nomor seri terakhir
                $lastNumber = substr($lastReport->report_number, -3);
                $nextNumber = intval($lastNumber) + 1;
            } else {
                // Jika belum ada report hari ini, mulai dari 1
                $nextNumber = 1;
            }

            // Format nomor seri dengan 3 digit (001, 002, dst.)
            $serialNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            return "{$prefix}-{$serialNumber}";
        } catch (\Exception $e) {
            // Fallback jika terjadi error
            return "RPT-" . now()->format('Ymd') . "-" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }

    public function previewReportNumber()
    {
        try {
            $nextReportNumber = $this->generateReportNumber();

            return response()->json([
                'status' => true,
                'message' => 'Next report number generated',
                'data' => [
                    'next_report_number' => $nextReportNumber,
                    'format_info' => [
                        'pattern' => 'RPT-YYYYMMDD-XXX',
                        'description' => 'RPT = Report prefix, YYYYMMDD = Date, XXX = Serial number (001-999)',
                        'example' => 'RPT-20250624-001'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate report number preview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validation = $request->validate([
                // Table reports
                'user_id' => 'required|exists:users,id',
                'employee_id' => 'required|exists:employees,id',
                'health_facility_id' => 'required|exists:health_facilities,id',
                'report_number' => 'nullable|string|unique:reports,report_number',
                'problem' => 'nullable|string',
                'error_code' => 'nullable|string',
                'job_action' => 'required|string',

                // Device works structure
                'device_works' => 'required|array|min:1',
                'device_works.*.medical_device_id' => 'required|exists:medical_devices,id',
                'device_works.*.type_of_work_ids' => 'required|array|min:1',
                'device_works.*.type_of_work_ids.*' => 'exists:type_of_works,id',

                // *** NEW: Location data validation ***
                'latitude' => 'required|string',
                'longitude' => 'required|string',
                'address' => 'required|string',
            ]);

            // Generate report number otomatis jika tidak disediakan
            $reportNumber = $validation['report_number'] ?? $this->generateReportNumber();

            // Buat report terlebih dahulu
            $reportData = [
                'user_id' => $validation['user_id'],
                'employee_id' => $validation['employee_id'],
                'health_facility_id' => $validation['health_facility_id'],
                'report_number' => $reportNumber,
                'problem' => $validation['problem'],
                'error_code' => $validation['error_code'],
                'job_action' => $validation['job_action'],
                'is_status' => 'Progress' // Status awal
            ];

            // Simpan report
            $report = Report::create($reportData);

            Location::create([
                'report_id' => $report->id,
                'latitude' => $validation['latitude'],
                'longitude' => $validation['longitude'],
                'address' => $validation['address'],
            ]);

            // Save device works dengan kontrol yang tepat
            foreach ($validation['device_works'] as $deviceWork) {
                $medicalDeviceId = $deviceWork['medical_device_id'];
                $typeOfWorkIds = $deviceWork['type_of_work_ids'];

                // Setiap medical device hanya dengan type of work yang ditentukan
                foreach ($typeOfWorkIds as $typeOfWorkId) {
                    ReportDeviceItem::create([
                        'report_id' => $report->id,
                        'medical_device_id' => $medicalDeviceId,
                        'type_of_work_id' => $typeOfWorkId
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Report created successfully',
                'data' => $report->load(['reportDeviceItem', 'user', 'employee', 'healthFacility'])
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create report',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    // public function completeReport(Request $request, $reportId)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Validasi bahwa report ada dan belum selesai
    //         $report = Report::findOrFail($reportId);

    //         if ($report->is_status === 'Completed') {
    //             return response()->json([
    //                 'status' => 'Progress',
    //                 'message' => 'Report has already been completed'
    //             ], 400);
    //         }

    //         // Validasi bahwa report detail belum ada (mencegah duplikasi)
    //         $existingDetail = ReportDetail::where('report_id', $reportId)->first();
    //         if ($existingDetail) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Report detail already exists for this report'
    //             ], 400);
    //         }

    //         $validation = $request->validate([
    //             'completion_status_id' => 'required|exists:completion_statuses,id',
    //             'note' => 'nullable|string|max:1000',
    //             'suggestion' => 'nullable|string|max:1000',
    //             'customer_name' => 'nullable|string|max:255',
    //             'customer_phone' => 'nullable|string|max:20',
    //             'attendance_employee' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
    //             'attendance_customer' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',

    //             'latitude' => 'required|string',
    //             'longitude' => 'required|string',
    //             'address' => 'required|string|max:255',

    //             'parameters' => 'nullable|array',
    //             'parameters.*.name' => 'required_with:parameters|string|max:255',
    //             'parameters.*.uraian' => 'nullable|string|max:1000',
    //             'parameters.*.description' => 'nullable|string|max:1000',

    //             'parts_used' => 'nullable|array',
    //             'parts_used.*.uraian' => 'required_with:parts_used|string|max:255',
    //             'parts_used.*.quantity' => 'required_with:parts_used|integer|min:1',
    //             'parts_used.*.images' => 'nullable|array',
    //             'parts_used.*.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         ]);

    //         $lokasi = Location::where('report_id', $report->id)->firstOrFail();
    //         $lokasi->update([
    //             'latitude' => $validation['latitude'],
    //             'longitude' => $validation['longitude'],
    //             'address' => $validation['address'],
    //         ]);

    //         // Handle upload tanda tangan teknisi
    //         $employeeSignaturePath = null;
    //         if ($request->hasFile('attendance_employee')) {
    //             $employeeSignaturePath = $this->uploadSignature(
    //                 $request->file('attendance_employee'),
    //                 'employee_signatures',
    //                 $reportId
    //             );
    //         }

    //         // Handle upload tanda tangan customer
    //         $customerSignaturePath = null;
    //         if ($request->hasFile('attendance_customer')) {
    //             $customerSignaturePath = $this->uploadSignature(
    //                 $request->file('attendance_customer'),
    //                 'customer_signatures',
    //                 $reportId
    //             );
    //         }

    //         // Buat report detail
    //         $reportDetailData = [
    //             'report_id' => $reportId,
    //             'completion_status_id' => $validation['completion_status_id'],
    //             'note' => $validation['note'] ?? null,
    //             'suggestion' => $validation['suggestion'] ?? null,
    //             'customer_name' => $validation['customer_name'] ?? null,
    //             'customer_phone' => $validation['customer_phone'] ?? null,
    //             'attendance_employee' => $employeeSignaturePath,
    //             'attendance_customer' => $customerSignaturePath,
    //         ];

    //         $reportDetail = ReportDetail::create($reportDetailData);

    //         // Tambahan: Simpan data parameters
    //         if (!empty($validation['parameters'])) {
    //             foreach ($validation['parameters'] as $paramData) {
    //                 Parameter::create([
    //                     'report_id' => $reportId,
    //                     'name' => $paramData['name'],
    //                     'uraian' => $paramData['uraian'] ?? null,
    //                     'description' => $paramData['description'] ?? null,
    //                 ]);
    //             }
    //         }

    //         // Tambahan: Simpan data parts used dan images
    //         if ($request->has('parts_used')) {
    //             foreach ($request->input('parts_used') as $index => $partData) {
    //                 // Buat record part terlebih dahulu untuk mendapatkan ID-nya
    //                 $part = PartUsedForRepair::create([
    //                     'report_id' => $reportId,
    //                     'uraian' => $partData['uraian'],
    //                     'quantity' => $partData['quantity'],
    //                 ]);

    //                 // Cek dan simpan gambar-gambar untuk part ini
    //                 if ($request->hasFile("parts_used.{$index}.images")) {
    //                     foreach ($request->file("parts_used.{$index}.images") as $imageFile) {
    //                         // Simpan file dan dapatkan path-nya
    //                         $path = $imageFile->store("part_images/report_{$reportId}", 'public');

    //                         PartUsedForImage::create([
    //                             'part_used_for_repair_id' => $part->id,
    //                             'image' => $path,
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }


    //         $completedAt = now();
    //         $totalSeconds = $report->created_at->diffInSeconds($completedAt);
    //         $totalTime = gmdate('H:i:s', $totalSeconds);
    //         // Update status report menjadi completed
    //         $report->update([
    //             'is_status' => 'Completed',
    //             'completed_at' => $completedAt, // Gunakan variabel yang sudah dibuat
    //             'total_time' => $totalTime      // Simpan total waktu yang sudah diformat
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Report completed successfully',
    //             'data' => $report->fresh()->load([
    //                 'user',
    //                 'employee',
    //                 'healthFacility',
    //                 'reportDeviceItem',
    //                 'reportDetail.completionStatus',
    //                 'location',
    //                 'parameter', // Tambahan: Muat relasi baru
    //                 'partUsedForRepair.images' // Tambahan: Muat relasi bersarang
    //             ])
    //         ], 200);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors()
    //         ], 422);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Report not found'
    //         ], 404);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         // Log error untuk debugging
    //         Log::error('Complete Report Error: ' . $e->getMessage(), [
    //             'report_id' => $reportId,
    //             'request_data' => $request->except(['attendance_employee', 'attendance_customer']),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to complete report',
    //             'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
    //         ], 500);
    //     }
    // }

    public function completeReport(Request $request, $reportId)
    {
        try {
            DB::beginTransaction();

            // Validasi bahwa report ada dan belum selesai
            $report = Report::findOrFail($reportId);

            if ($report->is_status === 'Completed') {
                return response()->json([
                    'status' => 'Progress',
                    'message' => 'Report has already been completed'
                ], 400);
            }

            // Validasi bahwa report detail belum ada (mencegah duplikasi)
            $existingDetail = ReportDetail::where('report_id', $reportId)->first();
            if ($existingDetail) {
                return response()->json([
                    'status' => false,
                    'message' => 'Report detail already exists for this report'
                ], 400);
            }

            $validation = $request->validate([
                'completion_status_id' => 'required|exists:completion_statuses,id',
                'note' => 'nullable|string|max:1000',
                'suggestion' => 'nullable|string|max:1000',
                'customer_name' => 'nullable|string|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'attendance_employee' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
                'attendance_customer' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',

                'latitude' => 'required|string',
                'longitude' => 'required|string',
                'address' => 'required|string|max:255',

                'parameters' => 'nullable|array',
                'parameters.*.name' => 'required_with:parameters|string|max:255',
                'parameters.*.uraian' => 'nullable|string|max:1000',
                'parameters.*.description' => 'nullable|string|max:1000',

                'parts_used' => 'nullable|array',
                'parts_used.*.uraian' => 'required_with:parts_used|string|max:255',
                'parts_used.*.quantity' => 'required_with:parts_used|integer|min:1',
                'parts_used.*.images' => 'nullable|array',
                'parts_used.*.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $lokasi = Location::where('report_id', $report->id)->firstOrFail();
            $lokasi->update([
                'latitude' => $validation['latitude'],
                'longitude' => $validation['longitude'],
                'address' => $validation['address'],
            ]);

            // Handle upload tanda tangan teknisi
            $employeeSignaturePath = null;
            if ($request->hasFile('attendance_employee')) {
                $employeeSignaturePath = $this->uploadSignature(
                    $request->file('attendance_employee'),
                    'employee_signatures',
                    $reportId
                );
            }

            // Handle upload tanda tangan customer
            $customerSignaturePath = null;
            if ($request->hasFile('attendance_customer')) {
                $customerSignaturePath = $this->uploadSignature(
                    $request->file('attendance_customer'),
                    'customer_signatures',
                    $reportId
                );
            }

            // Buat report detail
            $reportDetail = ReportDetail::create([
                'report_id' => $reportId,
                'completion_status_id' => $validation['completion_status_id'],
                'note' => $validation['note'] ?? null,
                'suggestion' => $validation['suggestion'] ?? null,
                'customer_name' => $validation['customer_name'] ?? null,
                'customer_phone' => $validation['customer_phone'] ?? null,
                'attendance_employee' => $employeeSignaturePath,
                'attendance_customer' => $customerSignaturePath,
            ]);

            // Simpan data parameters
            if (!empty($validation['parameters'])) {
                foreach ($validation['parameters'] as $paramData) {
                    Parameter::create([
                        'report_id' => $reportId,
                        'name' => $paramData['name'],
                        'uraian' => $paramData['uraian'] ?? null,
                        'description' => $paramData['description'] ?? null,
                    ]);
                }
            }

            // Simpan data parts used dan images
            if ($request->has('parts_used')) {
                foreach ($request->input('parts_used') as $index => $partData) {
                    // Validasi data part terlebih dahulu
                    if (!isset($partData['uraian']) || !isset($partData['quantity'])) {
                        continue; // Skip jika data tidak lengkap
                    }

                    // Simpan data part (selalu disimpan meskipun tanpa image)
                    $part = PartUsedForRepair::create([
                        'report_id' => $reportId,
                        'uraian' => $partData['uraian'],
                        'quantity' => $partData['quantity'],
                    ]);

                    // Proses image hanya jika ada file yang diupload
                    if ($request->hasFile("parts_used.{$index}.images")) {
                        $images = $request->file("parts_used.{$index}.images");

                        // Pastikan $images adalah array, jika tidak jadikan array
                        if (!is_array($images)) {
                            $images = [$images];
                        }

                        foreach ($images as $imageFile) {
                            // Validasi file image
                            if ($imageFile->isValid()) {
                                // Tentukan path folder
                                $folderPath = "part_images/report_{$reportId}";

                                // Buat folder jika belum ada
                                if (!Storage::disk('public')->exists($folderPath)) {
                                    Storage::disk('public')->makeDirectory($folderPath);
                                }

                                // Generate nama file unik untuk menghindari konflik
                                $fileName = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                                $fullPath = $folderPath . '/' . $fileName;

                                // Simpan file
                                $path = $imageFile->storeAs($folderPath, $fileName, 'public');

                                // Simpan record image ke database
                                PartUsedForImage::create([
                                    'part_used_for_repair_id' => $part->id,
                                    'image' => $path,
                                ]);
                            }
                        }
                    }
                }
            }

            // === AWAL PERUBAHAN ===
            // Tentukan status report berdasarkan completion_status_id
            $reportUpdateData = [];

            if ($validation['completion_status_id'] == 1) { // Selesai
                $completedAt = now();
                $totalSeconds = $report->created_at->diffInSeconds($completedAt);
                $totalTime = gmdate('H:i:s', $totalSeconds);

                $reportUpdateData = [
                    'is_status' => 'Completed',
                    'completed_at' => $completedAt,
                    'total_time' => $totalTime
                ];
            } elseif ($validation['completion_status_id'] == 2) { // Tidak Selesai
                $reportUpdateData = [
                    'is_status' => 'Pending',
                    'completed_at' => null, // Pastikan completed_at kosong
                    'total_time' => null    // Pastikan total_time kosong
                ];
            }
            // Jika ada status lain, Anda bisa menambahkannya dengan blok elseif lainnya.

            // Update status report jika ada data yang perlu diupdate
            if (!empty($reportUpdateData)) {
                $report->update($reportUpdateData);
            }
            // === AKHIR PERUBAHAN ===

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Report completed successfully',
                'data' => $report->fresh()->load([
                    'user',
                    'employee',
                    'healthFacility',
                    'reportDeviceItem',
                    'reportDetail.completionStatus',
                    'location',
                    'parameter',
                    'partUsedForRepair.images'
                ])
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Report not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Complete Report Error: ' . $e->getMessage(), [
                'report_id' => $reportId,
                'request_data' => $request->except(['attendance_employee', 'attendance_customer']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to complete report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function uploadSignature($file, $folder, $reportId)
    {
        try {
            // Pastikan direktori ada
            $storagePath = storage_path("app/public/signatures/{$folder}");
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Generate nama file unik
            $timestamp = now()->format('YmdHis');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $fileName = "signature_{$reportId}_{$folder}_{$timestamp}_{$originalName}.{$extension}";

            // Simpan file ke storage/app/public/signatures/{folder}
            $path = $file->storeAs("signatures/{$folder}", $fileName, 'public');

            return $fileName; // Return nama file saja untuk disimpan di database

        } catch (\Exception $e) {
            throw new \Exception("Failed to upload signature: " . $e->getMessage());
        }
    }

    public function getSignatureUrl($fileName, $type)
    {
        if (!$fileName) {
            return null;
        }

        $folder = $type === 'employee' ? 'employee_signatures' : 'customer_signatures';
        $filePath = storage_path("app/public/signatures/{$folder}/{$fileName}");

        if (!file_exists($filePath)) {
            return null;
        }

        return url("storage/signatures/{$folder}/{$fileName}");
    }

    public function show($id)
    {
        try {
            $report = Report::with([
                'reportDetail',
                'user',
                'employee',
                'healthFacility',
                'ReportDeviceItem',
                'Location',
                'parameter', // Tambahan: Muat relasi baru
                'partUsedForRepair.images'
            ])->findOrFail($id);

            // Konversi ke array untuk manipulasi data
            $reportArray = $report->toArray();

            // Tambahkan URL tanda tangan jika ada detail
            if (isset($reportArray['report_detail'])) {
                // Jika 1 detail (array, bukan array of array)
                if (isset($reportArray['report_detail']['attendance_employee'])) {
                    $reportArray['report_detail']['employee_signature_url'] = $this->getSignatureUrl(
                        $reportArray['report_detail']['attendance_employee'],
                        'employee'
                    );
                    $reportArray['report_detail']['customer_signature_url'] = $this->getSignatureUrl(
                        $reportArray['report_detail']['attendance_customer'],
                        'customer'
                    );
                }

                // Jika banyak detail (array of array)
                if (is_array($reportArray['report_detail']) && isset($reportArray['report_detail'][0])) {
                    $reportArray['report_detail'] = collect($reportArray['report_detail'])->map(function ($detail) {
                        $detail['employee_signature_url'] = isset($detail['attendance_employee'])
                            ? $this->getSignatureUrl($detail['attendance_employee'], 'employee')
                            : null;
                        $detail['customer_signature_url'] = isset($detail['attendance_customer'])
                            ? $this->getSignatureUrl($detail['attendance_customer'], 'customer')
                            : null;
                        return $detail;
                    })->toArray();
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Report retrieved successfully',
                'data' => $reportArray
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Report not found',
                'error' => $th->getMessage()
            ], 404);
        }
    }


    public function getReportsByEmployee($employeeId)
    {
        try {
            $reports = Report::with(['reportDetail', 'healthFacility'])
                ->where('employee_id', $employeeId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Reports retrieved successfully',
                'data' => $reports
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve reports',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $report = Report::findOrFail($id);

            $validation = $request->validate([
                // Table reports
                'user_id' => 'sometimes|exists:users,id',
                'employee_id' => 'sometimes|exists:employees,id',
                'health_facility_id' => 'sometimes|exists:health_facilities,id',
                'report_number' => 'sometimes|string|unique:reports,report_number,' . $id,
                'problem' => 'sometimes|string',
                'error_code' => 'sometimes|string',
                'job_action' => 'sometimes|string',
                'status' => 'sometimes|in:in_progress,completed,cancelled',

                // Report device items
                'medical_device_ids' => 'sometimes|array',
                'medical_device_ids.*' => 'exists:medical_devices,id',
                'type_of_work_ids' => 'sometimes|array',
                'type_of_work_ids.*' => 'exists:type_of_works,id',

                // Report details (jika report sudah completed)
                'completion_status_id' => 'sometimes|exists:completion_statuses,id',
                'note' => 'sometimes|nullable|string',
                'suggestion' => 'sometimes|nullable|string',
                'customer_name' => 'sometimes|nullable|string',
                'customer_phone' => 'sometimes|nullable|string',
                'attendance_employee' => 'sometimes|nullable|file|image|mimes:jpeg,png,jpg|max:2048',
                'attendance_customer' => 'sometimes|nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            // Update report data
            $reportData = array_intersect_key($validation, array_flip([
                'user_id',
                'employee_id',
                'health_facility_id',
                'report_number',
                'problem',
                'error_code',
                'job_action',
                'status'
            ]));

            if (!empty($reportData)) {
                $report->update($reportData);
            }

            // Update medical devices jika ada
            if (isset($validation['medical_device_ids'])) {
                // Hapus yang lama
                ReportDeviceItem::where('report_id', $report->id)
                    ->whereNotNull('medical_device_id')
                    ->delete();

                // Tambah yang baru
                foreach ($validation['medical_device_ids'] as $deviceId) {
                    ReportDeviceItem::create([
                        'report_id' => $report->id,
                        'medical_device_id' => $deviceId
                    ]);
                }
            }

            // Update type of works jika ada
            if (isset($validation['type_of_work_ids'])) {
                // Hapus yang lama
                ReportDeviceItem::where('report_id', $report->id)
                    ->whereNotNull('type_of_work_id')
                    ->delete();

                // Tambah yang baru
                foreach ($validation['type_of_work_ids'] as $workId) {
                    ReportDeviceItem::create([
                        'report_id' => $report->id,
                        'type_of_work_id' => $workId
                    ]);
                }
            }

            // Update report details jika ada dan report sudah completed atau akan completed
            $detailFields = ['completion_status_id', 'note', 'suggestion', 'customer_name', 'customer_phone'];
            $hasDetailUpdate = !empty(array_intersect_key($validation, array_flip($detailFields)))
                || $request->hasFile('attendance_employee')
                || $request->hasFile('attendance_customer');

            if ($hasDetailUpdate) {
                $reportDetail = ReportDetail::where('report_id', $report->id)->first();

                if (!$reportDetail && ($report->status === 'completed' || (isset($validation['status']) && $validation['status'] === 'completed'))) {
                    // Buat report detail baru jika belum ada
                    $reportDetail = new ReportDetail(['report_id' => $report->id]);
                }

                if ($reportDetail) {
                    // Handle upload tanda tangan baru
                    if ($request->hasFile('attendance_employee')) {
                        // Hapus file lama jika ada
                        $this->deleteOldSignature($reportDetail->attendance_employee, 'employee_signatures');

                        $employeeSignaturePath = $this->uploadSignature(
                            $request->file('attendance_employee'),
                            'employee_signatures',
                            $report->id
                        );
                        $reportDetail->attendance_employee = $employeeSignaturePath;
                    }

                    if ($request->hasFile('attendance_customer')) {
                        // Hapus file lama jika ada
                        $this->deleteOldSignature($reportDetail->attendance_customer, 'customer_signatures');

                        $customerSignaturePath = $this->uploadSignature(
                            $request->file('attendance_customer'),
                            'customer_signatures',
                            $report->id
                        );
                        $reportDetail->attendance_customer = $customerSignaturePath;
                    }

                    // Update field lainnya
                    foreach ($detailFields as $field) {
                        if (isset($validation[$field])) {
                            $reportDetail->$field = $validation[$field];
                        }
                    }

                    $reportDetail->save();
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Report updated successfully',
                'data' => $report->fresh()->load(['reportDetail', 'reportDeviceItem', 'user', 'employee', 'healthFacility'])
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update report',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function updateReportDetail(Request $request, $reportId)
    {
        try {
            DB::beginTransaction();

            $report = Report::findOrFail($reportId);
            $reportDetail = ReportDetail::where('report_id', $reportId)->first();

            if (!$reportDetail) {
                return response()->json([
                    'status' => false,
                    'message' => 'Report detail not found'
                ], 404);
            }

            $validation = $request->validate([
                'completion_status_id' => 'sometimes|exists:completion_statuses,id',
                'note' => 'sometimes|nullable|string',
                'suggestion' => 'sometimes|nullable|string',
                'customer_name' => 'sometimes|nullable|string',
                'customer_phone' => 'sometimes|nullable|string',
                'attendance_employee' => 'sometimes|nullable|file|image|mimes:jpeg,png,jpg|max:2048',
                'attendance_customer' => 'sometimes|nullable|file|image|mimes:jpeg,png,jpg|max:2048',
                'remove_employee_signature' => 'sometimes|boolean',
                'remove_customer_signature' => 'sometimes|boolean',
            ]);

            // Handle penghapusan tanda tangan
            if (isset($validation['remove_employee_signature']) && $validation['remove_employee_signature']) {
                $this->deleteOldSignature($reportDetail->attendance_employee, 'employee_signatures');
                $reportDetail->attendance_employee = null;
            }

            if (isset($validation['remove_customer_signature']) && $validation['remove_customer_signature']) {
                $this->deleteOldSignature($reportDetail->attendance_customer, 'customer_signatures');
                $reportDetail->attendance_customer = null;
            }

            // Handle upload tanda tangan baru
            if ($request->hasFile('attendance_employee')) {
                $this->deleteOldSignature($reportDetail->attendance_employee, 'employee_signatures');

                $employeeSignaturePath = $this->uploadSignature(
                    $request->file('attendance_employee'),
                    'employee_signatures',
                    $reportId
                );
                $reportDetail->attendance_employee = $employeeSignaturePath;
            }

            if ($request->hasFile('attendance_customer')) {
                $this->deleteOldSignature($reportDetail->attendance_customer, 'customer_signatures');

                $customerSignaturePath = $this->uploadSignature(
                    $request->file('attendance_customer'),
                    'customer_signatures',
                    $reportId
                );
                $reportDetail->attendance_customer = $customerSignaturePath;
            }

            // Update field lainnya
            $updateableFields = ['completion_status_id', 'note', 'suggestion', 'customer_name', 'customer_phone'];
            foreach ($updateableFields as $field) {
                if (isset($validation[$field])) {
                    $reportDetail->$field = $validation[$field];
                }
            }

            $reportDetail->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Report detail updated successfully',
                'data' => $report->fresh()->load(['reportDetail', 'reportDeviceItem'])
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update report detail',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    private function deleteOldSignature($fileName, $folder)
    {
        if ($fileName) {
            $filePath = storage_path("app/public/signatures/{$folder}/{$fileName}");
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $report = Report::findOrFail($id);

            // Hapus file signatures jika ada
            $reportDetail = $report->reportDetail;
            if ($reportDetail) {
                $this->deleteOldSignature($reportDetail->attendance_employee, 'employee_signatures');
                $this->deleteOldSignature($reportDetail->attendance_customer, 'customer_signatures');
            }

            // Soft delete atau hard delete sesuai kebutuhan
            $report->delete(); // Jika menggunakan SoftDeletes
            // atau $report->forceDelete(); untuk hard delete

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Report deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete report',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getSignatureFile($type, $fileName)
    {
        try {
            $folder = $type === 'employee' ? 'employee_signatures' : 'customer_signatures';
            $filePath = storage_path("app/public/signatures/{$folder}/{$fileName}");

            // Cek apakah file ada
            if (!file_exists($filePath)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Signature file not found'
                ], 404);
            }

            // Return file dengan header yang sesuai
            return response()->file($filePath, [
                'Content-Type' => mime_content_type($filePath),
                'Cache-Control' => 'public, max-age=3600'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving signature file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
