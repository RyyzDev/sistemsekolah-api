<?php

namespace App\Http\Controllers\PPDB;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\StudentDetailResource;

class StudentController extends Controller
{
   

    public function me(Request $request)
    {
        $student = Student::with(['parents', 'achievements', 'documents', 'grades'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan',
                'data'    => null
            ], 404);
        }


        return (new StudentDetailResource($student))
            ->additional([
                'success' => true,
                'message' => 'Data siswa berhasil diambil',
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // 1. Buat data siswa
            $student = Student::create(array_merge($validated, [
                'user_id' => $request->user()->id,
                'status'  => 'draft',
            ]));

            // 2. Tambahkan data otomatis
            $student->registration_number = $student->generateRegistrationNumber();
            $student->registration_date = now();
            $student->save();

            DB::commit();

            return (new StudentDetailResource($student))
                ->additional([
                    'success' => true,
                    'message' => 'Data siswa berhasil dibuat',
                ])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat data siswa',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $student = Student::with(['user', 'parents', 'achievements', 'documents', 'grades'])
            ->findOrFail($id);

        return (new StudentDetailResource($student))
            ->additional([
                'success' => true,
                'message' => 'Data siswa berhasil diambil',
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function update(StoreStudentRequest $request, $id)
    {
        $student = Student::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'paid', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa yang sudah disubmit tidak dapat diubah'
            ], 400);
        }

        $student->update($request->validated());

        return (new StudentDetailResource($student))
            ->additional([
                'success' => true,
                'message' => 'Data siswa berhasil diupdate',
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function uploadPhoto(Request $request, $id)
    {
        $student = Student::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($student->photo) {
            Storage::delete($student->photo);
        }

        $path = $request->file('photo')->store('students/photos', 'public');
        $student->update(['photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil diupload',
            'data'    => ['photo_url' => Storage::url($path)]
        ]);
    }

    public function submit(Request $request, $id)
    {
        $student = Student::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($student->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa sudah pernah disubmit'
            ], 400);
        }

        if (!$student->isComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa belum lengkap'
            ], 400);
        }

        $student->update(['status' => 'submitted']);

        return (new StudentDetailResource($student))
            ->additional([
                'success' => true,
                'message' => 'Data siswa berhasil disubmit!',
            ])
            ->response()
            ->setStatusCode(200);
    }

   

    public function destroy(Request $request, $id)
    {
        $student = Student::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($student->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya data draft yang dapat dihapus'
            ], 400);
        }

        if ($student->photo) {
            Storage::delete($student->photo);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil dihapus'
        ]);
    }

   
}