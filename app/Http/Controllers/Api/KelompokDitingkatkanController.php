<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\KelompokDitingkatkan;
use Illuminate\Http\Request;

class KelompokDitingkatkanController extends Controller
{
    private function applyTriwulanFilter($query, Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulan = "{$twMapping[$tw]} Tahun {$tahun}";
            $query->where('triwulan', $triwulan);
        }

        return $query;
    }

    public function jumlahPerSatminkal(Request $request)
    {
        $query = KelompokDitingkatkan::select('satminkal', DB::raw('COUNT(*) as jml_kelompok'))
            ->whereNotNull('satminkal');

        $query = $this->applyTriwulanFilter($query, $request);

        $data = $query->groupBy('satminkal')
            ->orderBy('satminkal')
            ->get();

        return response()->json($data);
    }

    public function jumlahPerProvinsi(Request $request)
    {
        $query = KelompokDitingkatkan::select('provinsi', DB::raw('COUNT(*) as jml_kelompok'))
            ->whereNotNull('provinsi');

        $query = $this->applyTriwulanFilter($query, $request);

        $data = $query->groupBy('provinsi')
            ->orderBy('provinsi')
            ->get();

        return response()->json($data);
    }

    public function bidangUsahaPerProvinsi(Request $request)
    {
        $bidangList = ['BUDIDAYA', 'PENANGKAPAN', 'PENGOLAHAN/PEMASARAN', 'GARAM', 'PENGAWASAN'];

        $query = KelompokDitingkatkan::select('provinsi', 'bidang_usaha', DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi');

        $query = $this->applyTriwulanFilter($query, $request);

        $baseQuery = $query->groupBy('provinsi', 'bidang_usaha')->get();

        $result = $baseQuery->groupBy('provinsi')->map(function ($items, $provinsi) use ($bidangList) {
            $row = ['provinsi' => $provinsi];
            $total = 0;

            foreach ($bidangList as $bidang) {
                $found = $items->firstWhere('bidang_usaha', $bidang);
                $jumlah = $found ? (int) $found->total : null;
                $row[$bidang] = $jumlah;
                $total += $jumlah ?? 0;
            }

            $row['total'] = $total;
            return $row;
        })->values();

        return response()->json($result);
    }

    public function kelasPerProvinsi(Request $request)
    {
        $kelasList = ['PEMULA', 'MADYA', 'UTAMA', 'LANJUT'];

        $query = KelompokDitingkatkan::select('provinsi', 'kelas_menjadi', DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi');

        $query = $this->applyTriwulanFilter($query, $request);

        $baseQuery = $query->groupBy('provinsi', 'kelas_menjadi')->get();

        $result = $baseQuery->groupBy('provinsi')->map(function ($items, $provinsi) use ($kelasList) {
            $row = ['provinsi' => $provinsi];
            $total = 0;

            foreach ($kelasList as $kelas) {
                $found = $items->firstWhere('kelas_menjadi', $kelas);
                $jumlah = $found ? (int) $found->total : null;
                $row[$kelas] = $jumlah;
                $total += $jumlah ?? 0;
            }

            $row['total'] = $total;
            return $row;
        })->values();

        return response()->json($result);
    }

    public function bidangUsahaPerSatminkal(Request $request)
    {
        $bidangList = ['BUDIDAYA', 'PENANGKAPAN', 'PENGOLAHAN/PEMASARAN', 'GARAM', 'PENGAWASAN'];

        $query = KelompokDitingkatkan::select('satminkal', 'bidang_usaha', DB::raw('COUNT(*) as total'))
            ->whereNotNull('satminkal');

        $query = $this->applyTriwulanFilter($query, $request);

        $baseQuery = $query->groupBy('satminkal', 'bidang_usaha')->get();

        $result = $baseQuery->groupBy('satminkal')->map(function ($items, $satminkal) use ($bidangList) {
            $row = ['satminkal' => $satminkal];
            $total = 0;

            foreach ($bidangList as $bidang) {
                $found = $items->firstWhere('bidang_usaha', $bidang);
                $jumlah = $found ? (int) $found->total : null;
                $row[$bidang] = $jumlah;
                $total += $jumlah ?? 0;
            }

            $row['total'] = $total;
            return $row;
        })->values();

        return response()->json($result);
    }

    public function getLocationKelompokDitingkatkan(Request $request)
    {

        $tahun = $request->query('tahun'); // e.g., 2025
        $tw = $request->query('tw');       // e.g., "TW I"

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        $triwulanFilter = null;
        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulanFilter = "{$twMapping[$tw]} Tahun {$tahun}";
        }

        $sql = "
            SELECT 
                kt.no,
                kt.nama_kelompok,
                kt.nama_ketua,
                kt.satminkal,
                k.latitude,
                k.longitude
            FROM kelompok_ditingkatkan kt
            LEFT JOIN mtr_kabupatens k 
                ON k.kabupaten LIKE CONCAT('%', kt.kab_kota, '%')
            WHERE 1=1
        ";

        $bindings = [];

        // Apply filter if ada triwulanFilter
        if ($triwulanFilter) {
            $sql .= " AND kt.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        $data = DB::select($sql, $bindings);

        return response()->json($data);
    }

    public function getDetailKelompokDitingkatkan($no)
    {
        $sql = "
    SELECT 
        kt.*,
        k.latitude,
        k.longitude
    FROM kelompok_ditingkatkan kt
    LEFT JOIN mtr_kabupatens k 
        ON k.kabupaten LIKE CONCAT('%', kt.kab_kota, '%')
    WHERE kt.no_piagam = ?
    LIMIT 1
";


        $data = DB::selectOne($sql, [$no]);

        if (!$data) {
            return response()->json(['message' => 'Penyuluh not found'], 404);
        }

        return response()->json($data);
    }
}
