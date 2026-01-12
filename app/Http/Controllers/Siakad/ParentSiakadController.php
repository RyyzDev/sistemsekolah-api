<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ParentSiakadController extends Controller
{
    public function index(Request $request)
    {
        $query = ParentModel::with(['user', 'student.user']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $parents = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $parents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'student_id' => 'required|exists:students,id',
            'relation_type' => 'required|in:Ayah,Ibu,Wali',
            'phone' => 'required|string|max:20',
            'occupation' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $user->assignRole('Parent');

            $parent = ParentModel::create([
                'user_id' => $user->id,
                'student_id' => $validated['student_id'],
                'relation_type' => $validated['relation_type'],
                'phone' => $validated['phone'],
                'occupation' => $validated['occupation'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parent created successfully',
                'data' => $parent->load(['user', 'student.user']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create parent: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(ParentModel $parent)
    {
        return response()->json([
            'success' => true,
            'data' => $parent->load(['user', 'student.user']),
        ]);
    }

    public function update(Request $request, ParentModel $parent)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $parent->user_id,
            'relation_type' => 'in:Ayah,Ibu,Wali',
            'phone' => 'string|max:20',
            'occupation' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name']) || isset($validated['email'])) {
                $parent->user->update(array_filter([
                    'name' => $validated['name'] ?? null,
                    'email' => $validated['email'] ?? null,
                ]));
            }

            $parentData = array_filter([
                'relation_type' => $validated['relation_type'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'occupation' => $validated['occupation'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $parent->update($parentData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parent updated successfully',
                'data' => $parent->fresh()->load(['user', 'student.user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update parent: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(ParentModel $parent)
    {
        DB::beginTransaction();
        try {
            $user = $parent->user;
            $parent->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parent deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete parent: ' . $e->getMessage(),
            ], 500);
        }
    }
}