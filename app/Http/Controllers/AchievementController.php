<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AchievementController extends Controller
{
    public function index($studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $achievements = $student->achievements;

        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil diambil',
            'data' => $achievements,
            'total_points' => $student->calculatePrestasiScore()
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
                'message' => 'Data prestasi tidak dapat ditambahkan setelah pendaftaran disubmit'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'achievement_name' => 'required|string|max:255',
            'achievement_type' => 'required|in:akademik,non_akademik,organisasi,olahraga,seni,lainnya',
            'level' => 'required|in:sekolah,kecamatan,kota,kabupaten,provinsi,nasional,internasional',
            'rank' => 'required|in:juara_1,juara_2,juara_3,finalis,peserta',
            'organizer' => 'required|string|max:255',
            'achievement_date' => 'required|date',
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $achievementData = $request->except('certificate_file');
        $achievementData['student_id'] = $studentId;

        if ($request->hasFile('certificate_file')) {
            $path = $request->file('certificate_file')->store('achievements/certificates', 'public');
            $achievementData['certificate_file'] = $path;
        }

        $achievement = Achievement::create($achievementData);

        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil ditambahkan',
            'data' => $achievement
        ], 201);
    }

    public function show($studentId, $achievementId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $achievement = Achievement::where('id', $achievementId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil diambil',
            'data' => $achievement
        ]);
    }

    public function update(Request $request, $studentId, $achievementId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data prestasi tidak dapat diubah setelah pendaftaran disubmit'
            ], 400);
        }

        $achievement = Achievement::where('id', $achievementId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'achievement_name' => 'sometimes|string|max:255',
            'achievement_type' => 'sometimes|in:akademik,non_akademik,organisasi,olahraga,seni,lainnya',
            'level' => 'sometimes|in:sekolah,kecamatan,kabupaten,provinsi,nasional,internasional',
            'rank' => 'sometimes|in:juara_1,juara_2,juara_3,finalis,peserta',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $achievementData = $request->except('certificate_file');

        if ($request->hasFile('certificate_file')) {
            if ($achievement->certificate_file) {
                Storage::delete($achievement->certificate_file);
            }
            $path = $request->file('certificate_file')->store('achievements/certificates', 'public');
            $achievementData['certificate_file'] = $path;
        }

        $achievement->update($achievementData);

        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil diupdate',
            'data' => $achievement
        ]);
    }

    public function destroy(Request $request, $studentId, $achievementId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['submitted', 'verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data prestasi tidak dapat dihapus setelah pendaftaran disubmit'
            ], 400);
        }

        $achievement = Achievement::where('id', $achievementId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if ($achievement->certificate_file) {
            Storage::delete($achievement->certificate_file);
        }

        $achievement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil dihapus'
        ]);
    }
}