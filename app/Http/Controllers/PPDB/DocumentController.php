<?php

namespace App\Http\Controllers\PPDB;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function index($studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $documents = $student->documents;

        return response()->json([
            'success' => true,
            'message' => 'Data dokumen berhasil diambil',
            'data' => $documents
        ]);
    }

    public function store(Request $request, $studentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data dokumen tidak dapat ditambahkan setelah pendaftaran diverifikasi'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:akta_kelahiran,kartu_keluarga,ijazah,skhun,rapor,foto,ktp_orangtua,kip,kks,pkh,surat_keterangan_domisili,surat_keterangan_tidak_mampu,surat_keterangan_disabilitas,surat_pindah,lainnya',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $documentData = [
            'student_id' => $studentId,
            'document_type' => $request->document_type,
            'document_name' => $request->document_name,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'description' => $request->description,
            'is_required' => $request->is_required ?? true,
        ];

        $document = Document::create($documentData);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diupload',
            'data' => $document
        ], 201);
    }

    public function show($studentId, $documentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $document = Document::where('id', $documentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Data dokumen berhasil diambil',
            'data' => $document
        ]);
    }

    public function update(Request $request, $studentId, $documentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data dokumen tidak dapat diubah setelah pendaftaran diverifikasi'
            ], 400);
        }

        $document = Document::where('id', $documentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'document_name' => 'sometimes|string|max:255',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $documentData = $request->only(['document_name', 'description']);

        if ($request->hasFile('file')) {
            Storage::delete($document->file_path);
            
            $file = $request->file('file');
            $path = $file->store('documents', 'public');
            
            $documentData['file_path'] = $path;
            $documentData['file_type'] = $file->getClientOriginalExtension();
            $documentData['file_size'] = $file->getSize();
        }

        $document->update($documentData);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diupdate',
            'data' => $document
        ]);
    }

    public function destroy(Request $request, $studentId, $documentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (in_array($student->status, ['verified', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data dokumen tidak dapat dihapus setelah pendaftaran diverifikasi'
            ], 400);
        }

        $document = Document::where('id', $documentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        Storage::delete($document->file_path);
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus'
        ]);
    }

  

    public function download($studentId, $documentId)
    {
        $student = Student::where('id', $studentId)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        $document = Document::where('id', $documentId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->document_name . '.' . $document->file_type);
    }
}