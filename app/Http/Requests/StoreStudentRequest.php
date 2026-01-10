<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Student;

class StoreStudentRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return true; // Ubah ke true agar request diproses
    }

    /**
     * Aturan validasi yang berlaku untuk request.
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'nik' => 'required|string|size:16|unique:students,nik',
            'no_kk' => 'required|string|size:16',
            'gender' => 'required|in:L,P',
            'birth_place' => 'required|string|max:255',
            'birth_date' => 'required|date|before:today',
            'religion' => 'required|in:islam,kristen,katolik,hindu,buddha,konghucu',
            'citizenship' => 'required|in:wni,wna',
            'address' => 'required|string',
            'rt' => 'required|string|max:3',
            'rw' => 'required|string|max:3',
            'kelurahan' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'required|string|size:5',
            'residence_type' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:13',
            'email' => 'required|email',
            'special_needs' => 'required|string|max:100',
            'special_needs_description' => 'required|string|max:255',
            'hobby' => 'nullable|string|max:100',
            'kps_pkh_number' => 'nullable|string|max:25',
            'kip_number' => 'nullable|string|max:25',
            'kks_number' => 'nullable|string|max:25',
            'previous_school_name' => 'required|string|max:100',
            'ijazah_number' => 'required|string|max:25',
            'registration_type' => 'required|in:baru,pindahan,kembali_bersekolah',
            'registration_path' => 'nullable|in:domisili,prestasi,afirmasi,mutasi',
        ];

    }


     /**
     * Validasi bahwa tidak ada duplikasi data
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $existingStudent = Student::where('user_id', $this->user()->id)->first();
            if ($existingStudent) {
            $validator->errors()->add('user_id', 'User sudah memiliki data siswa.');
            }
        });
    }

    /**
     * Custom format response jika validasi gagal (khusus API)
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422));
    }
}