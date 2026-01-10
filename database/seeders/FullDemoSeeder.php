<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Student;
use App\Models\ParentModel as StudentParent;
use App\Models\Payment;
use App\Models\PaymentItem;

class FullDemoSeeder extends Seeder
{
    public function run(): void
    {
        // User
        $user = User::create([
            'google_id'        => Str::random(16),
            'name'             => 'Budi Setiawan',
            'role'             => 'applicant',
            'email'            => 'budi'.Str::random(4).'@mail.com',
            'password'         => bcrypt('password'),
            'contact'          => '08134567890',
            'email_verified_at'=> now(),
            'remember_token'   => Str::random(10),
        ]);

        // Student
        $student = Student::create([
            'user_id'             => $user->id,
            'registration_number' => 'REG20260001',
            'registration_type'   => 'baru',
            'registration_path'   => 'domisili',
            'registration_date'   => now(),
            'status'              => 'submitted',
            'full_name'           => 'Budi Setiawan',
            'nickname'            => 'Budi',
            'nisn'                => '0123456789',
            'nik'                 => (string)rand(1000000000000000,1999999999999999),
            'no_kk'               => (string)rand(1000000000000000,1999999999999999),
            'no_akta_lahir'       => '1234567890',
            'gender'              => 'L',
            'birth_place'         => 'Bandung',
            'birth_date'          => '2011-06-17',
            'religion'            => 'islam',
            'citizenship'         => 'wni',
            'nationality'         => 'Indonesia',
            'address'             => 'Jl. Contoh No.42, Bandung',
            'rt'                  => '001',
            'rw'                  => '002',
            'dusun'               => 'Sukamaju',
            'kelurahan'           => 'Cibeunying',
            'kecamatan'           => 'Cidadap',
            'kabupaten_kota'      => 'Bandung',
            'province'            => 'Jawa Barat',
            'postal_code'         => '40191',
            'latitude'            => -6.89148,
            'longitude'           => 107.61071,
            'residence_type'      => 'bersama_orangtua',
            'transportation'      => 'jalan_kaki',
            'phone_number'        => '022123456',
            'mobile_number'       => '081234567890',
            'email'               => 'siswa'.Str::random(4).'@mail.com',
            'height'              => 156,
            'weight'              => 50,
            'blood_type'          => 'O',
            'special_needs'       => 'tidak',
            'special_needs_description' => null,
            'disease_history'     => null,
            'child_number'        => 2,
            'total_siblings'      => 3,
            'hobby'               => 'Sepak bola',
            'ambition'            => 'Dokter',
            'kps_pkh_recipient'   => true,
            'kps_pkh_number'      => 'KPS12345',
            'kip_recipient'       => false,
            'kip_number'          => null,
            'pip_eligible'        => false,
            'kks_number'          => null,
            'previous_school_name'=> 'SDN Contoh 1',
            'previous_school_npsn'=> '12345678',
            'previous_school_address' => 'Jl. Sekolah Lama 12',
            'ijazah_number'       => 'IZJ123456789',
            'ijazah_date'         => '2023-06-19',
            'skhun_number'        => 'SKHUN1234567',
            'photo'               => null,
        ]);

        // Parents (ayah, ibu)
        StudentParent::create([
            'student_id'           => $student->id,
            'parent_type'          => 'ayah',
            'full_name'            => 'Pak Setiawan',
            'nik'                  => (string)rand(1000000000000000,1999999999999999),
            'birth_place'          => 'Bandung',
            'birth_date'           => '1980-01-13',
            'religion'             => 'islam',
            'citizenship'          => 'wni',
            'education'            => 's1',
            'occupation'           => 'Pegawai Swasta',
            'occupation_category'  => 'pegawai_swasta',
            'monthly_income'       => 5000000,
            'phone_number'         => '021987654',
            'mobile_number'        => '081987654321',
            'email'                => 'ayah'.Str::random(4).'@gmail.com',
            'address'              => 'Jl. Contoh Ayah Bandung',
            'rt'                   => '001',
            'rw'                   => '002',
            'kelurahan'            => 'Cibeunying',
            'kecamatan'            => 'Cidadap',
            'kabupaten_kota'       => 'Bandung',
            'province'             => 'Jawa Barat',
            'postal_code'          => '40191',
            'living_status'        => 'hidup',
            'is_guardian'          => false,
        ]);
        StudentParent::create([
            'student_id'           => $student->id,
            'parent_type'          => 'ibu',
            'full_name'            => 'Bu Sari',
            'nik'                  => (string)rand(1000000000000000,1999999999999999),
            'birth_place'          => 'Jakarta',
            'birth_date'           => '1982-04-23',
            'religion'             => 'islam',
            'citizenship'          => 'wni',
            'education'            => 'sma',
            'occupation'           => 'Ibu Rumah Tangga',
            'occupation_category'  => 'lainnya',
            'monthly_income'       => 0,
            'phone_number'         => '021876543',
            'mobile_number'        => '081876543210',
            'email'                => 'ibu'.Str::random(4).'@gmail.com',
            'address'              => 'Jl. Contoh Ibu Bandung',
            'rt'                   => '001',
            'rw'                   => '002',
            'kelurahan'            => 'Cibeunying',
            'kecamatan'            => 'Cidadap',
            'kabupaten_kota'       => 'Bandung',
            'province'             => 'Jawa Barat',
            'postal_code'          => '40191',
            'living_status'        => 'hidup',
            'is_guardian'          => true,
        ]);

        // Payment
        $payment = Payment::create([
            'student_id'        => $student->id,
            'order_id'          => 'ORD-20260110-ABC123',
            'transaction_id'    => 'TX'.Str::upper(Str::random(10)),
            'payment_type'      => 'registration_fee',
            'amount'            => 575000,
            'admin_fee'         => 5000,
            'total_amount'      => 580000,
            'payment_method'    => 'bank_transfer',
            'status'            => 'settlement',
            'va_number'         => '1234123412341234',
            'bank'              => 'bca',
            'biller_code'       => '88077',
            'bill_key'          => '1122334455',
            'snap_token'        => Str::uuid(),
            'snap_url'          => 'https://snap.midtrans.com/transaction/example',
            'paid_at'           => Carbon::now(),
            'expired_at'        => Carbon::now()->addDay(),
            'midtrans_response' => json_encode(['dummy' => true]),
            'notes'             => 'Pembayaran registrasi',
        ]);

        // Payment Items (Dua item)
        PaymentItem::create([
            'payment_id'       => $payment->id,
            'item_name'        => 'Biaya Pendaftaran',
            'item_description' => 'Pembayaran awal',
            'quantity'         => 1,
            'price'            => 500000,
            'subtotal'         => 500000,
        ]);
        PaymentItem::create([
            'payment_id'       => $payment->id,
            'item_name'        => 'Seragam',
            'item_description' => 'Pembelian seragam baru',
            'quantity'         => 1,
            'price'            => 75000,
            'subtotal'         => 75000,
        ]);
    }
}