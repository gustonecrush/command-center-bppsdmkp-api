<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GapokkanDidampingi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GapokkanDidampingiController extends Controller
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

    private function applyLocationFilter($query, Request $request)
    {
        $provinsiCode = $request->query('provinsi');
        $kabupaten = $request->query('kabupaten');

        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();

            if ($provinsi) {
                $query->where('provinsi', $provinsi->provinsi);
            }
        }

        if ($kabupaten) {
            $query->where('kab_kota', 'LIKE', "%{$kabupaten}%");
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
        $filteredQuery = $this->applyLocationFilter($query, $request);
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
        $filteredQuery = $this->applyLocationFilter($query, $request);
        $data = $filteredQuery->get();

        return response()->json($data);
    }

    public function getLocationGapokkan(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $provinsiCode = $request->query('provinsi');
        $kabupaten = $request->query('kabupaten');

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

        // Get provinsi name from code
        $provinsiName = null;
        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();
            $provinsiName = $provinsi ? $provinsi->provinsi : null;
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

        if ($triwulanFilter) {
            $sql .= " AND gp.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        if ($provinsiName) {
            $sql .= " AND gp.provinsi = ? ";
            $bindings[] = $provinsiName;
        }

        if ($kabupaten) {
            $sql .= " AND gp.kab_kota LIKE ? ";
            $bindings[] = "%{$kabupaten}%";
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
