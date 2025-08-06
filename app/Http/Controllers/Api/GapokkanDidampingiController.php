<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GapokkanDidampingi;
use Illuminate\Http\Request;

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
}
