<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-Raport - {{ $data['student']['name'] }}</title>
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        /* Header Section */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .header h2 { margin: 5px 0; font-size: 14pt; }
        .header p { margin: 0; font-size: 10pt; font-style: italic; }

        /* Information Table */
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 4px 2px; vertical-align: top; }
        .info-table .label { width: 120px; font-weight: bold; }
        .info-table .separator { width: 10px; }

        /* Main Grade Table */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            word-wrap: break-word;
        }
        .grades-table th {
            background-color: #f2f2f2;
            font-size: 10px;
            text-transform: uppercase;
        }
        .grades-table td.subject-name { text-align: left; padding-left: 8px; width: 25%; }

        /* Attendance Table */
        .attendance-box { width: 40%; margin-bottom: 30px; }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        .attendance-table td.label { background-color: #f9f9f9; width: 60%; }

        /* Signature Section */
        .signature-container {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table { width: 100%; border-collapse: collapse; }
        .signature-table td { text-align: center; width: 33%; vertical-align: top; }
        .space { height: 70px; }
        .name-underline { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN HASIL BELAJAR SISWA</h1>
        <h2>(E-RAPORT)</h2>
        <p>Semester {{ $data['semester']['type'] }} | Tahun Ajaran {{ $data['semester']['academic_year'] }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Siswa</td>
            <td class="separator">:</td>
            <td>{{ $data['student']['name'] }}</td>
            <td class="label">Kelas</td>
            <td class="separator">:</td>
            <td>{{ $data['classroom']['name'] }}</td>
        </tr>
        <tr>
            <td class="label">NIS / NISN</td>
            <td class="separator">:</td>
            <td>{{ $data['student']['nis'] }} / {{ $data['student']['nisn'] ?? '-' }}</td>
            <td class="label">Wali Kelas</td>
            <td class="separator">:</td>
            <td>{{ $data['classroom']['homeroom_teacher'] ?? '-' }}</td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 5px;">A. NILAI AKADEMIK</div>
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 30px;" rowspan="2">No</th>
                <th rowspan="2">Mata Pelajaran</th>
                <th colspan="4">Komponen Nilai</th>
                <th style="width: 60px;" rowspan="2">Nilai Akhir</th>
                <th style="width: 60px;" rowspan="2">Predikat</th>
            </tr>
            <tr>
                <th>Tugas</th>
                <th>UH</th>
                <th>UTS</th>
                <th>UAS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['subjects'] as $index => $subject)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="subject-name">{{ $subject['subject_name'] }}</td>
                <td>{{ $subject['components']['tugas']['score'] ?? '-' }}</td>
                <td>{{ $subject['components']['uh']['score'] ?? '-' }}</td>
                <td>{{ $subject['components']['uts']['score'] ?? '-' }}</td>
                <td>{{ $subject['components']['uas']['score'] ?? '-' }}</td>
                <td><strong>{{ $subject['final_score'] ?? '-' }}</strong></td>
                <td>{{ $subject['predicate'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f2f2f2;">
                <td colspan="6" style="text-align: right; font-weight: bold; padding-right: 10px;">Rata-rata (GPA)</td>
                <td colspan="2"><strong>{{ number_format($data['gpa'] ?? 0, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div style="font-weight: bold; margin-bottom: 5px;">B. KEHADIRAN (PRESENSI)</div>
    <div class="attendance-box">
        <table class="attendance-table">
            <tr><td class="label">Hadir</td><td>{{ $data['attendance_summary']['hadir'] }} hari</td></tr>
            <tr><td class="label">Izin</td><td>{{ $data['attendance_summary']['izin'] }} hari</td></tr>
            <tr><td class="label">Sakit</td><td>{{ $data['attendance_summary']['sakit'] }} hari</td></tr>
            <tr><td class="label">Tanpa Keterangan (Alpha)</td><td>{{ $data['attendance_summary']['alpha'] }} hari</td></tr>
        </table>
    </div>

    <div class="signature-container">
        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>Orang Tua / Wali
                    <div class="space"></div>
                    ( ................................ )
                </td>
                <td>
                    <br>Kepala Sekolah
                    <div class="space"></div>
                    <span class="name-underline">{{ $data['school_principal'] ?? '................................' }}</span>
                </td>
                <td>
                    Tangerang, {{ now()->translatedFormat('d F Y') }}<br>Wali Kelas
                    <div class="space"></div>
                    <span class="name-underline">{{ $data['classroom']['homeroom_teacher'] ?? '................................' }}</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>