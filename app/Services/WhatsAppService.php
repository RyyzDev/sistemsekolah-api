<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.url', 'https://api.fonnte.com/send');
        $this->apiKey = config('services.whatsapp.key', '');
    }

    public function sendMessage(string $phoneNumber, string $message): bool
    {
        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'targetYou stopped at the app/Models/Notification.php. Please continue immediately from that line and finish the rest of app.15.43php' => $formattedPhone,
                'message' => $message,
                'countryCode' => '62', // Indonesia
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp sent successfully', [
                    'phone' => $formattedPhone,
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp send failed', [
                'phone' => $formattedPhone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendAttendanceAlert(string $phoneNumber, string $studentName, string $status, string $date): bool
    {
        $statusText = match($status) {
            'A' => 'Alpha (Tidak Hadir Tanpa Keterangan)',
            'S' => 'Sakit',
            'I' => 'Izin',
            default => $status,
        };

        $message = "🔔 *Notifikasi Presensi SIAKAD*\n\n";
        $message .= "Siswa: *{$studentName}*\n";
        $message .= "Status: *{$statusText}*\n";
        $message .= "Tanggal: {$date}\n\n";
        $message .= "Mohon segera menghubungi pihak sekolah jika ada pertanyaan.\n\n";
        $message .= "_Pesan otomatis dari sistem SIAKAD_";

        return $this->sendMessage($phoneNumber, $message);
    }

    public function sendGradeNotification(string $phoneNumber, string $studentName, string $subjectName, float $finalScore): bool
    {
        $message = "🔔 *Notifikasi Nilai SIAKAD*\n\n";
        $message .= "Siswa: *{$studentName}*\n";
        $message .= "Mata Pelajaran: *{$subjectName}*\n";
        $message .= "Nilai Akhir: *{$finalScore}*\n\n";
        $message .= "Silakan cek E-Raport untuk detail lengkap.\n\n";
        $message .= "_Pesan otomatis dari sistem SIAKAD_";

        return $this->sendMessage($phoneNumber, $message);
    }

    public function sendBulkMessage(array $recipients): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $results[$recipient['phone']] = $this->sendMessage(
                $recipient['phone'],
                $recipient['message']
            );
            
            usleep(500000); // 500ms delay between messages to avoid rate limiting
        }

        return $results;
    }

    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}