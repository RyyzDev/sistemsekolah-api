<?php

namespace App\Http\Controllers\PPDB;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParentController extends Controller
{
    public function index($studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $parents = $student->parents;

        return response()->json([
            'success' => true,
            'message' => 'Data orang tua berhasil diambil',
            'data' => $parents
        ]);
    }

    public function store(Request $request, $studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data orang tua tidak dapat ditambahkan setelah pendaftaran disubmit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'parent_type' => 'required|in:ayah,ibu,wali',
            'full_name' => 'required|string|max:255',
            'nik' => 'nullable|string|size:16',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'religion' => 'nullable|in:islam,kristen,katolik,hindu,buddha,konghucu',
            'education' => 'nullable|in:tidak_sekolah,sd,smp,sma,diploma,s1,s2,s3',
            'occupation' => 'nullable|string|max:255',
            'occupation_category' => 'nullable|in:tidak_bekerja,pns,tni_polri,guru_dosen,pegawai_swasta,wiraswasta,petani,nelayan,buruh,pensiunan,lainnya',
            'monthly_income' => 'nullable|numeric|min:0',
            'living_status' => 'required|in:hidup,meninggal',
            'phone_number' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $existingParent = ParentModel::where('student_id', $studentId)
            ->where('parent_type', $request->parent_type)
            ->first();

        if ($existingParent) {
            return response()->json([
                'success' => false,
                'message' => "Data {$request->parent_type} sudah ada"
            ], 400);
        }

        $parentData = $request->all();
        $parentData['student_id'] = $studentId;

        $parent = ParentModel::create($parentData);

        return response()->json([
            'success' => true,
            'message' => 'Data orang tua berhasil ditambahkan',
            'data' => $parent
        ], 201);
    }

    public function show($studentId, $parentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $parent = ParentModel::where('id', $parentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Data orang tua berhasil diambil',
            'data' => $parent
        ]);
    }

    public function update(Request $request, $studentId, $parentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data orang tua tidak dapat diubah setelah pendaftaran disubmit'
            ], 400);
        }

        $parent = ParentModel::where('id', $parentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'nik' => 'nullable|string|size:16',
            'education' => 'nullable|in:tidak_sekolah,sd,smp,sma,diploma,s1,s2,s3',
            'monthly_income' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $parent->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data orang tua berhasil diupdate',
            'data' => $parent
        ]);
    }

    public function destroy(Request $request, $studentId, $parentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data orang tua tidak dapat dihapus setelah pendaftaran disubmit'
            ], 400);
        }

        $parent = ParentModel::where('id', $parentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $parent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data orang tua berhasil dihapus'
        ]);
    }
}