<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentSiakadController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'nis' => 'required|string|max:20|unique:students,nis',
            'nisn' => 'nullable|string|max:20|unique:students,nisn',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:100',
            'gender' => 'required|in:L,P',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $user->assignRole('Student');

            $student = Student::create([
                'user_id' => $user->id,
                'nis' => $validated['nis'],
                'nisn' => $validated['nisn'] ?? null,
                'birth_date' => $validated['birth_date'],
                'birth_place' => $validated['birth_place'],
                'gender' => $validated['gender'],
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => $student->load('user'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Student $student)
    {
        return response()->json([
            'success' => true,
            'data' => $student->load(['user', 'classrooms.academicYear', 'parents.user']),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $student->user_id,
            'nis' => 'string|max:20|unique:students,nis,' . $student->id,
            'nisn' => 'nullable|string|max:20|unique:students,nisn,' . $student->id,
            'birth_date' => 'date',
            'birth_place' => 'string|max:100',
            'gender' => 'in:L,P',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name']) || isset($validated['email'])) {
                $student->user->update(array_filter([
                    'name' => $validated['name'] ?? null,
                    'email' => $validated['email'] ?? null,
                ]));
            }

            $studentData = array_filter([
                'nis' => $validated['nis'] ?? null,
                'nisn' => $validated['nisn'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);

            $student->update($studentData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => $student->fresh()->load('user'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Student $student)
    {
        DB::beginTransaction();
        try {
            $user = $student->user;
            $student->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student: ' . $e->getMessage(),
            ], 500);
        }
    }
}