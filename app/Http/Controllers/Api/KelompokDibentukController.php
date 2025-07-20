<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KelompokDibentuk;
use Illuminate\Support\Facades\DB;

class KelompokDibentukController extends Controller
{
    public function bidangUsahaPerSatminkal()
    {
        $bidangList = ['BUDIDAYA', 'GARAM', 'PENANGKAPAN', 'PENGOLAHAN/PEMASARAN'];

        $baseQuery = KelompokDibentuk::select('satminkal', 'bidang_usaha', DB::raw('COUNT(*) as total'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal', 'bidang_usaha')
            ->get();

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

    public function bidangUsahaPerProvinsi()
    {
        $bidangList = ['BUDIDAYA', 'GARAM', 'PENGOLAHAN/PEMASARAN', 'PENANGKAPAN'];

        $baseQuery = KelompokDibentuk::select('provinsi', 'bidang_usaha', DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', 'bidang_usaha')
            ->get();

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
}
