<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KelompokDisuluh;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class KelompokDisuluhController extends Controller
{
    // ✅ Private reusable method to apply triwulan filter
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
        $query = KelompokDisuluh::select('satminkal', DB::raw('COUNT(*) as jml_kelompok'))
            ->whereNotNull('satminkal');

        $query = $this->applyTriwulanFilter($query, $request);

        $data = $query->groupBy('satminkal')
            ->orderBy('satminkal')
            ->get();

        return response()->json($data);
    }

    public function jumlahPerProvinsi(Request $request)
    {
        $query = KelompokDisuluh::select('provinsi', DB::raw('COUNT(*) as jml_kelompok'))
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

        $query = KelompokDisuluh::select('provinsi', 'bidang_usaha', DB::raw('COUNT(*) as total'))
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

    public function kelasKelompokPerProvinsi(Request $request)
    {
        $kelasList = ['LANJUT', 'MADYA', 'PEMULA', 'UTAMA'];

        $query = KelompokDisuluh::select('provinsi', 'kelas_kelompok', DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi');

        $query = $this->applyTriwulanFilter($query, $request);

        $baseQuery = $query->groupBy('provinsi', 'kelas_kelompok')->get();

        $result = $baseQuery->groupBy('provinsi')->map(function ($items, $provinsi) use ($kelasList) {
            $row = ['provinsi' => $provinsi];
            $total = 0;

            foreach ($kelasList as $kelas) {
                $found = $items->firstWhere('kelas_kelompok', $kelas);
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

        $query = KelompokDisuluh::select('satminkal', 'bidang_usaha', DB::raw('COUNT(*) as total'))
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

    public function getLocationKelompokDisuluh(Request $request)
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
                ks.no,
                ks.nama_kelompok,
                ks.nama_ketua,
                ks.satminkal,
                k.latitude,
                k.longitude
            FROM kelompok_disuluh ks
            LEFT JOIN mtr_kabupatens k 
                ON k.kabupaten LIKE CONCAT('%', ks.kab_kota, '%')
            WHERE 1=1
        ";

        $bindings = [];

        // Apply filter if ada triwulanFilter
        if ($triwulanFilter) {
            $sql .= " AND ks.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        $data = DB::select($sql, $bindings);

        return response()->json($data);
    }

    public function getDetailKelompokDisuluh($no)
    {
        $sql = "
    SELECT 
        ks.*,
        k.latitude,
        k.longitude
    FROM kelompok_disuluh ks
    LEFT JOIN mtr_kabupatens k 
        ON k.kabupaten LIKE CONCAT('%', ks.kab_kota, '%')
    WHERE ks.no = ?
    LIMIT 1
";


        $data = DB::selectOne($sql, [$no]);

        if (!$data) {
            return response()->json(['message' => 'Penyuluh not found'], 404);
        }

        return response()->json($data);
    }
}
