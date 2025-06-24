<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Employee::with(['user', 'division', 'position']);

            // 🔍 Filter berdasarkan search
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('nik', 'like', '%' . $request->search . '%')
                        ->orWhere('employee_number', 'like', '%' . $request->search . '%');
                });
            }

            // ✅ Filter berdasarkan division_id jika ada
            if ($request->has('division_id')) {
                $query->where('division_id', $request->division_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }


            // ✅ Filter berdasarkan position_id jika ada
            if ($request->has('position_id')) {
                $query->where('position_id', $request->position_id);
            }

            $perPage = $request->per_page ?? 10;
            $employees = $query->paginate($perPage);

            // Tambahkan photo_url untuk setiap item
            $employeeData = collect($employees->items())->map(function ($employee) {
                $data = $employee->toArray();
                $data['photo_url'] = $employee->photo
                    ? asset('storage/employee_photos/' . $employee->photo)
                    : null;
                return $data;
            });

            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully',
                'data' => $employeeData,
                'meta' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                ],
                'links' => [
                    'first' => $employees->url(1),
                    'last' => $employees->url($employees->lastPage()),
                    'prev' => $employees->previousPageUrl(),
                    'next' => $employees->nextPageUrl(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function show($id)
    {
        try {
            $employee = Employee::with(['user', 'division', 'position'])->where('id', $id)->firstOrFail();

            // Ubah ke array agar bisa dimodifikasi
            $employeeData = $employee->toArray();

            // Tambahkan full URL untuk photo jika ada
            if ($employee->photo) {
                $employeeData['photo_url'] = asset('storage/employee_photos/' . $employee->photo);
            }

            return response()->json([
                'status' => true,
                'message' => 'Employee retrieved successfully',
                'data' => $employeeData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validation = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'region' => 'required|string',
                'division_id' => 'required|exists:divisions,id',
                'position_id' => 'required|exists:positions,id',
                'nik' => 'required|string|digits:16|unique:employees,nik',
                'name' => 'required|string',
                'gender' => 'required|string',
                'place_of_birth' => 'required|string',
                'date_of_birth' => 'required|date',
                'email' => 'required|email|unique:users,email',
                'phone_number' => 'required|string|numeric|digits_between:1,13',
                'address' => 'required|string',
                'date_of_entry' => 'required|date',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_active' => 'required|boolean',
                'status' => 'required|string',
            ]);

            // ✅ Cek apakah sudah ada employee dengan email atau phone_number
            $existingEmployee = Employee::where('email', $validation['email'])
                ->orWhere('phone_number', $validation['phone_number'])
                ->first();

            if ($existingEmployee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee with this email or phone number already exists',
                    'data' => [
                        'email' => $existingEmployee->email,
                        'phone_number' => $existingEmployee->phone_number,
                    ]
                ], 409);
            }

            // Generate employee_number terlebih dahulu
            $dateOfEntry = Carbon::parse($validation['date_of_entry'])->format('dmY');
            $count = Employee::whereDate('date_of_entry', $validation['date_of_entry'])->count();
            $sequenceNumber = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $employeeNumber = 'ARB-' . $sequenceNumber . $dateOfEntry;

            // ✅ Handle photo upload SEBELUM membuat employee data
            $photoFileName = null;
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoFile = $request->file('photo');

                // Buat nama file yang unik dengan employee number
                $photoFileName = $employeeNumber . '_' . time() . '.' . $photoFile->getClientOriginalExtension();

                // Simpan file ke storage/app/public/employee_photos
                $photoFile->storeAs('employee_photos', $photoFileName, 'public');
            }

            // Buat user baru jika user_id tidak disediakan
            if (!isset($validation['user_id'])) {
                $user = User::create([
                    'name' => $validation['name'],
                    'email' => $validation['email'],
                    'password' => Hash::make('arbimedan12345'),
                    'role_id' => 3,
                    'email_verified_at' => Carbon::now('Asia/Jakarta'),
                ]);

                if (class_exists('Spatie\Permission\Models\Role')) {
                    $employeeRole = Role::where('name', 'employee')->first();
                    if ($employeeRole) {
                        $user->assignRole($employeeRole);
                    }
                }

                $employeeData = [
                    'user_id' => $user->id,
                    'region' => $validation['region'],
                    'division_id' => $validation['division_id'],
                    'position_id' => $validation['position_id'],
                    'nik' => $validation['nik'],
                    'name' => $validation['name'],
                    'gender' => $validation['gender'],
                    'place_of_birth' => $validation['place_of_birth'],
                    'date_of_birth' => $validation['date_of_birth'],
                    'email' => $validation['email'],
                    'phone_number' => $validation['phone_number'],
                    'address' => $validation['address'],
                    'date_of_entry' => $validation['date_of_entry'],
                    'employee_number' => $employeeNumber,
                    'photo' => $photoFileName, // ✅ PENTING: Set photo di sini
                    'is_active' => $validation['is_active'],
                    'status' => $validation['status']
                ];
            } else {
                $employeeData = array_merge($validation, [
                    'employee_number' => $employeeNumber,
                    'photo' => $photoFileName, // ✅ PENTING: Set photo di sini juga
                ]);
            }

            // Buat employee dengan data yang sudah lengkap termasuk photo
            $employee = Employee::create($employeeData);

            DB::commit();

            // ✅ Tambahkan full URL photo untuk response
            $employeeResponse = $employee->toArray();
            if ($employee->photo) {
                $employeeResponse['photo_url'] = asset('storage/employee_photos/' . $employee->photo);
            }

            return response()->json([
                'status' => true,
                'message' => 'Employee created successfully',
                'data' => $employeeResponse
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Hapus file photo jika ada error setelah upload
            if (isset($photoFileName) && $photoFileName && file_exists(storage_path('app/public/employee_photos/' . $photoFileName))) {
                unlink(storage_path('app/public/employee_photos/' . $photoFileName));
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Cari employee yang akan diupdate atau gagalkan jika tidak ada
        $employee = Employee::findOrFail($id);

        // Aturan validasi yang lebih bersih dan aman
        $validatedData = $request->validate([
            'user_id'        => 'nullable|exists:users,id',
            'region'         => 'required|string|max:255',
            'division_id'    => 'required|exists:divisions,id',
            'position_id'    => 'required|exists:positions,id',
            'nik'            => ['required', 'string', 'digits:16', Rule::unique('employees')->ignore($employee->id)],
            'name'           => 'required|string|max:255',
            'gender'         => 'required|string|in:Laki-Laki,Perempuan', // Lebih spesifik
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth'  => 'required|date',
            'email'          => ['required', 'email', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            'phone_number'   => ['required', 'string', 'numeric', 'digits_between:10,15', Rule::unique('employees')->ignore($employee->id)],
            'address'        => 'required|string',
            'date_of_entry'  => 'required|date',
            'photo'          => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // PERUBAHAN UTAMA DI SINI
            'is_active'      => 'required|boolean',
            'status'      => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $photoFileName = $employee->photo; // Defaultnya adalah foto lama

            // Handle upload foto baru jika ada
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                // Hapus foto lama dari storage jika ada
                if ($employee->photo) {
                    Storage::disk('public')->delete('employee_photos/' . $employee->photo);
                }

                // Generate nama file baru yang unik dan simpan
                $photoFile = $request->file('photo');
                $photoFileName = $employee->employee_number . '_' . time() . '.' . $photoFile->getClientOriginalExtension();
                $photoFile->storeAs('employee_photos', $photoFileName, 'public');
            }

            // Update data user terkait jika ada
            if ($employee->user_id) {
                $user = User::find($employee->user_id);
                if ($user) {
                    // Cek duplikasi email di tabel users, abaikan user saat ini
                    $existingUser = User::where('email', $validatedData['email'])
                        ->where('id', '!=', $user->id)
                        ->first();

                    if ($existingUser) {
                        DB::rollBack(); // Batalkan transaksi jika email sudah dipakai user lain
                        return response()->json([
                            'status' => false,
                            'message' => 'This email is already taken by another user.',
                        ], 409);
                    }

                    $user->update([
                        'name' => $validatedData['name'],
                        'email' => $validatedData['email'],
                    ]);
                }
            }

            // Update data employee
            // Pastikan Anda hanya menambahkan 'photo' ke $validatedData jika memang ada perubahan
            // atau jika Anda ingin menyimpannya kembali jika 'photo' sebelumnya null.
            // Baris ini sudah benar karena $photoFileName akan tetap menjadi foto lama jika tidak ada upload baru.
            $employee->update(array_merge($validatedData, ['photo' => $photoFileName]));

            DB::commit();

            // Siapkan response dengan data terbaru dan URL foto
            $employeeResponse = $employee->fresh();
            if ($employeeResponse->photo) {
                $employeeResponse->photo_url = Storage::url('employee_photos/' . $employeeResponse->photo);
            }

            return response()->json([
                'status' => true,
                'message' => 'Employee updated successfully',
                'data' => $employeeResponse
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            // Jika terjadi error SETELAH foto baru diupload, hapus foto baru tersebut
            if (isset($photoFile) && isset($photoFileName) && $photoFileName !== $employee->getOriginal('photo')) {
                Storage::disk('public')->delete('employee_photos/' . $photoFileName);
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($id);
            $user = $employee->user;

            // Hapus employee
            $employee->delete();

            // Hapus user yang terkait
            if ($user) {
                $user->delete();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee and related user deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete employee and user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
