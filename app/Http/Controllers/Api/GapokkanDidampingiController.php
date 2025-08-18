<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GapokkanDidampingi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GapokkanDidampingiController extends Controller
{
    private function applyTriwulanFilter($query, $request)
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

    public function perSatminkal(Request $request)
    {
        $query = GapokkanDidampingi::select('satminkal')
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'GAPOKKAN' THEN 1 ELSE 0 END) AS gapokkan")
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'KOPERASI' THEN 1 ELSE 0 END) AS koperasi")
            ->selectRaw("COUNT(*) AS total")
            ->groupBy('satminkal');

        $filteredQuery = $this->applyTriwulanFilter($query, $request);
        $data = $filteredQuery->get();

        return response()->json($data);
    }


    public function perProvinsi(Request $request)
    {
        $query = GapokkanDidampingi::select('provinsi')
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'GAPOKKAN' THEN 1 ELSE 0 END) AS gapokkan")
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'KOPERASI' THEN 1 ELSE 0 END) AS koperasi")
            ->selectRaw("COUNT(*) AS total")
            ->groupBy('provinsi');

        $filteredQuery = $this->applyTriwulanFilter($query, $request);
        $data = $filteredQuery->get();

        return response()->json($data);
    }

    public function getLocationGapokkan(Request $request)
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
                gp.no,
                gp.nama,
                gp.nama_ketua,
                gp.nomor_ba,
                gp.satminkal,
                k.latitude,
                k.longitude
            FROM gapokkan_didampingi gp
            LEFT JOIN mtr_kabupatens k 
                ON k.kabupaten LIKE CONCAT('%', gp.kab_kota, '%')
            WHERE 1=1
        ";

        $bindings = [];

        // Apply filter if ada triwulanFilter
        if ($triwulanFilter) {
            $sql .= " AND gp.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        $data = DB::select($sql, $bindings);

        return response()->json($data);
    }

    public function getDetailGapokkan(Request $request)
    {
        $no = $request->query('no');

        if (!$no) {
            return response()->json(['error' => 'Parameter no is required'], 400);
        }


        $sql = "
    SELECT 
        gp.*,
        k.latitude,
        k.longitude
    FROM gapokkan_didampingi gp
    LEFT JOIN mtr_kabupatens k 
        ON k.kabupaten LIKE CONCAT('%', gp.kab_kota, '%')
    WHERE gp.nomor_ba = ?
    LIMIT 1
";


        $data = DB::selectOne($sql, [$no]);

        if (!$data) {
            return response()->json(['message' => 'Kelompok not found'], 404);
        }

        return response()->json($data);
    }
}
