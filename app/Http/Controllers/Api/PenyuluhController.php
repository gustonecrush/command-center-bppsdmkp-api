<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penyuluh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PenyuluhController extends Controller
{
    public function groupByStatus(Request $request)
    {
        $statusTypes = ['PNS', 'PPPK', 'PPB'];

        return $this->groupByFixedTypes($request, 'status', $statusTypes);
    }

    public function groupByJabatan(Request $request)
    {
        $jabatanTypes = [
            'PP PEMULA',
            'APP TERAMPIL',
            'APP MAHIR',
            'APP PENYELIA',
            'PP PERTAMA',
            'PP MUDA',
            'PP MADYA'
        ];

        return $this->groupByFixedTypes($request, 'jabatan', $jabatanTypes);
    }

    public function groupByPendidikan(Request $request)
    {
        $pendidikanTypes = ['S3', 'S2', 'S1/D4', 'D3', 'SMA'];

        return $this->groupByFixedTypes($request, 'pendidikan', $pendidikanTypes);
    }

    public function groupByKelompokUsia(Request $request)
    {
        $usiaTypes = ['<= 25', '25-30', '30-35', '35-40', '40-45', '45-50', '50-55', '55-60', '> 60'];

        return $this->groupByFixedTypes($request, 'kelompok_usia', $usiaTypes);
    }

    public function groupByKelamin(Request $request)
    {
        $kelaminTypes = ['L', 'P'];

        return $this->groupByFixedTypes($request, 'kelamin', $kelaminTypes);
    }

    public function countBySatminkal()
    {
        $data = Penyuluh::select('satminkal', DB::raw('count(*) as total'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal')
            ->get();

        return response()->json($data);
    }


    private function groupByFixedTypes(Request $request, string $column, array $fixedTypes)
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

        $query = Penyuluh::select('provinsi', $column, DB::raw('count(*) as total'))
            ->whereNotNull('provinsi');

        if ($triwulanFilter) {
            $query->where('triwulan', $triwulanFilter);
        }

        $data = $query->groupBy('provinsi', $column)
            ->orderBy('provinsi')
            ->get();

        $grouped = $data->groupBy('provinsi')->map(function ($items, $provinsi) use ($column, $fixedTypes) {
            $result = collect($fixedTypes)->map(function ($type) use ($items, $column) {
                $match = $items->firstWhere($column, $type);
                return [
                    $column => $type,
                    'total' => $match ? $match->total : 0
                ];
            });

            return [
                'provinsi' => $provinsi,
                $column => $result
            ];
        })->values();

        return response()->json($grouped);
    }


    public function getGroupedBySatminkalDetails(Request $request)
    {
        $keyword = $request->query('satminkal');

        if (!$keyword) {
            return response()->json(['error' => 'Missing satminkal parameter'], 400);
        }

        $baseQuery = Penyuluh::where('satminkal', $keyword);

        $total = $baseQuery->count();

        // Fixed category definitions
        $statusTypes = ['PNS', 'PPPK', 'PPB'];
        $jabatanTypes = [
            'PP PEMULA',
            'APP TERAMPIL',
            'APP MAHIR',
            'APP PENYELIA',
            'PP PERTAMA',
            'PP MUDA',
            'PP MADYA'
        ];
        $pendidikanTypes = ['S3', 'S2', 'S1/D4', 'D3', 'SMA'];
        $usiaTypes = ['<= 25', '25-30', '30-35', '35-40', '40-45', '45-50', '50-55', '55-60', '> 60'];
        $kelaminTypes = ['L', 'P'];

        $groupedDetails = function ($column, $types) use ($baseQuery) {
            $data = (clone $baseQuery)
                ->select('provinsi', $column, DB::raw('count(*) as total'))
                ->whereNotNull('provinsi')
                ->groupBy('provinsi', $column)
                ->get();

            return $data->groupBy('provinsi')->map(function ($items, $provinsi) use ($types, $column) {
                $result = collect($types)->map(function ($type) use ($items, $column) {
                    $match = $items->firstWhere($column, $type);
                    return [
                        $column => $type,
                        'total' => $match ? $match->total : 0
                    ];
                });

                return [
                    'provinsi' => $provinsi,
                    $column => $result
                ];
            })->values();
        };

        return response()->json([
            'satminkal' => $keyword,
            'total' => $total,
            'details' => [
                'status' => $groupedDetails('status', $statusTypes),
                'jabatan' => $groupedDetails('jabatan', $jabatanTypes),
                'pendidikan' => $groupedDetails('pendidikan', $pendidikanTypes),
                'kelompok_usia' => $groupedDetails('kelompok_usia', $usiaTypes),
                'kelamin' => $groupedDetails('kelamin', $kelaminTypes),
            ]
        ]);
    }

    public function getLocationPenyuluh(Request $request)
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
                p.no,
                p.nama,
                p.status,
                p.satminkal,
                k.latitude,
                k.longitude
            FROM penyuluh p
            LEFT JOIN mtr_kabupatens k 
                ON k.kabupaten LIKE CONCAT('%', p.kab_kota, '%')
            WHERE 1=1
        ";

        $bindings = [];

        // Apply filter if ada triwulanFilter
        if ($triwulanFilter) {
            $sql .= " AND p.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        $data = DB::select($sql, $bindings);

        return response()->json($data);
    }
}
