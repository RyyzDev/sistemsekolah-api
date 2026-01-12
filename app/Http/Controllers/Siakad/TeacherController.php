<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $teachers = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'nip' => 'required|string|max:20|unique:teachers,nip',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:100',
            'gender' => 'required|in:L,P',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $user->assignRole('Teacher');

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'],
                'birth_date' => $validated['birth_date'],
                'birth_place' => $validated['birth_place'],
                'gender' => $validated['gender'],
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully',
                'data' => $teacher->load('user'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Teacher $teacher)
    {
        return response()->json([
            'success' => true,
            'data' => $teacher->load(['user', 'classrooms', 'homeroomClassrooms']),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $teacher->user_id,
            'nip' => 'string|max:20|unique:teachers,nip,' . $teacher->id,
            'birth_date' => 'date',
            'birth_place' => 'string|max:100',
            'gender' => 'in:L,P',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name']) || isset($validated['email'])) {
                $teacher->user->update(array_filter([
                    'name' => $validated['name'] ?? null,
                    'email' => $validated['email'] ?? null,
                ]));
            }

            $teacherData = array_filter([
                'nip' => $validated['nip'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
            ]);

            $teacher->update($teacherData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => $teacher->fresh()->load('user'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            $user = $teacher->user;
            $teacher->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teacher: ' . $e->getMessage(),
            ], 500);
        }
    }
}