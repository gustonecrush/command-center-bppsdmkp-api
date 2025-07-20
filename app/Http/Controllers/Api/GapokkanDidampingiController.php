<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GapokkanDidampingi;

class GapokkanDidampingiController extends Controller
{
    public function perSatminkal()
    {
        $data = GapokkanDidampingi::select('satminkal')
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'GAPOKKAN' THEN 1 ELSE 0 END) AS gapokkan")
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'KOPERASI' THEN 1 ELSE 0 END) AS koperasi")
            ->selectRaw("COUNT(*) AS total")
            ->groupBy('satminkal')
            ->get();

        return response()->json($data);
    }

    public function perProvinsi()
    {
        $data = GapokkanDidampingi::select('provinsi')
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'GAPOKKAN' THEN 1 ELSE 0 END) AS gapokkan")
            ->selectRaw("SUM(CASE WHEN klasifikasi = 'KOPERASI' THEN 1 ELSE 0 END) AS koperasi")
            ->selectRaw("COUNT(*) AS total")
            ->groupBy('provinsi')
            ->get();

        return response()->json($data);
    }
}
