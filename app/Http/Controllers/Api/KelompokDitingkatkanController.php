<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\KelompokDitingkatkan;

class KelompokDitingkatkanController extends Controller
{
    public function jumlahPerSatminkal()
    {
        $data = KelompokDitingkatkan::select('satminkal', DB::raw('COUNT(*) as jml_kelompok'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal')
            ->orderBy('satminkal')
            ->get();

        return response()->json($data);
    }

    public function jumlahPerProvinsi()
    {
        $data = KelompokDitingkatkan::select('provinsi', DB::raw('COUNT(*) as jml_kelompok'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi')
            ->orderBy('provinsi')
            ->get();

        return response()->json($data);
    }

    public function bidangUsahaPerProvinsi()
    {
        $bidangList = ['BUDIDAYA', 'PENANGKAPAN', 'PENGOLAHAN/PEMASARAN', 'GARAM', 'PENGAWASAN'];

        $baseQuery = KelompokDitingkatkan::select('provinsi', 'bidang_usaha', DB::raw('COUNT(*) as total'))
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

    public function kelasPerProvinsi()
    {
        $kelasList = ['PEMULA', 'MADYA', 'UTAMA', 'LANJUT'];

        $baseQuery = KelompokDitingkatkan::select('provinsi', 'kelas_menjadi', DB::raw('COUNT(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', 'kelas_menjadi')
            ->get();

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

    public function bidangUsahaPerSatminkal()
    {
        $bidangList = ['BUDIDAYA', 'PENANGKAPAN', 'PENGOLAHAN/PEMASARAN', 'GARAM', 'PENGAWASAN'];

        $baseQuery = KelompokDitingkatkan::select('satminkal', 'bidang_usaha', DB::raw('COUNT(*) as total'))
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
}
