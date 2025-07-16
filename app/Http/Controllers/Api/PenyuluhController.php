<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penyuluh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PenyuluhController extends Controller
{
    public function groupByStatus()
    {
        $statusTypes = ['PNS', 'PPPK', 'PPB'];

        return $this->groupByFixedTypes('status', $statusTypes);
    }

    public function groupByJabatan()
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

        return $this->groupByFixedTypes('jabatan', $jabatanTypes);
    }

    public function groupByPendidikan()
    {
        $pendidikanTypes = ['S3', 'S2', 'S1/D4', 'D3', 'SMA'];

        return $this->groupByFixedTypes('pendidikan', $pendidikanTypes);
    }

    public function groupByKelompokUsia()
    {
        $usiaTypes = ['<= 25', '25-30', '30-35', '35-40', '40-45', '45-50', '50-55', '55-60', '> 60'];

        return $this->groupByFixedTypes('kelompok_usia', $usiaTypes);
    }

    public function groupByKelamin()
    {
        $kelaminTypes = ['L', 'P'];

        return $this->groupByFixedTypes('kelamin', $kelaminTypes);
    }

    public function countBySatminkal()
    {
        $data = Penyuluh::select('satminkal', DB::raw('count(*) as total'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal')
            ->get();

        return response()->json($data);
    }


    private function groupByFixedTypes(string $column, array $fixedTypes)
    {
        $data = Penyuluh::select('provinsi', $column, DB::raw('count(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', $column)
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
}
