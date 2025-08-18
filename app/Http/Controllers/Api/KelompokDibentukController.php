<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KelompokDibentuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelompokDibentukController extends Controller
{
    public function bidangUsahaPerSatminkal(Request $request)
    {
        $bidangList = ['BUDIDAYA', 'GARAM', 'PENANGKAPAN', 'PENGOLAHAN/PEMASARAN'];

        $query = KelompokDibentuk::select('satminkal', 'bidang_usaha', DB::raw('COUNT(*) as total'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal', 'bidang_usaha');

        // Apply TW filter here
        $query = $this->applyTriwulanFilter($query, $request);

        $baseQuery = $query->get();

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

    public function bidangUsahaPerProvinsi(Request $request)
    {
        $bidangList = ['BUDIDAYA', 'GARAM', 'PENGOLAHAN/PEMASARAN', 'PENANGKAPAN'];

        $query = KelompokDibentuk::select('provinsi', 'bidang_usaha', DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', 'bidang_usaha');

        // Apply TW filter here
        $query = $this->applyTriwulanFilter($query, $request);

        $baseQuery = $query->get();

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

    /**
     * Apply Triwulan filter if 'tw' and 'tahun' query parameters are present.
     */
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

    public function getLocationKelompokDibentuk(Request $request)
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
                kb.no,
                kb.nama_kelompok,
                kb.satminkal,
                k.latitude,
                k.longitude
            FROM kelompok_dibentuk kb
            LEFT JOIN mtr_kabupatens k 
                ON k.kabupaten LIKE CONCAT('%', kb.kab_kota, '%')
            WHERE 1=1
        ";

        $bindings = [];

        // Apply filter if ada triwulanFilter
        if ($triwulanFilter) {
            $sql .= " AND kb.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        $data = DB::select($sql, $bindings);

        return response()->json($data);
    }

    public function getDetailKelompokDibentuk($no)
    {
        $sql = "
    SELECT 
        kb.*,
        k.latitude,
        k.longitude
    FROM kelompok_dibentuk kb
    LEFT JOIN mtr_kabupatens k 
        ON k.kabupaten LIKE CONCAT('%', kb.kab_kota, '%')
    WHERE kb.no_ba_pembentukan = ?
    LIMIT 1
";


        $data = DB::selectOne($sql, [$no]);

        if (!$data) {
            return response()->json(['message' => 'Penyuluh not found'], 404);
        }

        return response()->json($data);
    }
}
