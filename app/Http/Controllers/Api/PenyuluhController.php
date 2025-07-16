<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penyuluh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyuluhController extends Controller
{
    private function getGroupedByProvinsi(string $column): \Illuminate\Http\JsonResponse
    {
        if (!in_array($column, ['status', 'jabatan', 'pendidikan', 'kelompok_usia', 'kelamin'])) {
            return response()->json(['error' => 'Invalid column'], 400);
        }

        $data = Penyuluh::select('provinsi', $column, DB::raw('count(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', $column)
            ->orderBy('provinsi')
            ->get();

        // Group by provinsi, then map values under the selected column
        $grouped = $data->groupBy('provinsi')->map(function ($items) use ($column) {
            return [
                'provinsi' => $items->first()->provinsi,
                $column => $items->map(function ($item) use ($column) {
                    return [
                        $column => $item->$column,
                        'total' => $item->total
                    ];
                })->values()
            ];
        })->values();

        return response()->json($grouped);
    }

    public function groupByStatus()
    {
        $data = Penyuluh::select('provinsi', 'status', DB::raw('count(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', 'status')
            ->orderBy('provinsi')
            ->get();

        // Define fixed status types
        $statusTypes = ['PNS', 'PPPK', 'PPB'];

        $grouped = $data->groupBy('provinsi')->map(function ($items, $provinsi) use ($statusTypes) {
            $statusCounts = collect($statusTypes)->map(function ($type) use ($items) {
                $match = $items->firstWhere('status', $type);
                return [
                    'status' => $type,
                    'total' => $match ? $match->total : 0
                ];
            });

            return [
                'provinsi' => $provinsi,
                'status' => $statusCounts
            ];
        })->values();

        return response()->json($grouped);
    }

    public function groupByJabatan()
    {
        $data = Penyuluh::select('provinsi', 'jabatan', DB::raw('count(*) as total'))
            ->whereNotNull('provinsi')
            ->groupBy('provinsi', 'jabatan')
            ->orderBy('provinsi')
            ->get();

        // Define fixed jabatan types in the required order
        $jabatanTypes = [
            'PP PEMULA',
            'APP TERAMPIL',
            'APP MAHIR',
            'APP PENYELIA',
            'PP PERTAMA',
            'PP MUDA',
            'PP MADYA'
        ];

        $grouped = $data->groupBy('provinsi')->map(function ($items, $provinsi) use ($jabatanTypes) {
            $jabatanCounts = collect($jabatanTypes)->map(function ($type) use ($items) {
                $match = $items->firstWhere('jabatan', $type);
                return [
                    'jabatan' => $type,
                    'total' => $match ? $match->total : 0
                ];
            });

            return [
                'provinsi' => $provinsi,
                'jabatan' => $jabatanCounts
            ];
        })->values();

        return response()->json($grouped);
    }

    public function groupByPendidikan()
    {
        return $this->getGroupedByProvinsi('pendidikan');
    }
    public function groupByKelompokUsia()
    {
        return $this->getGroupedByProvinsi('kelompok_usia');
    }
    public function groupByKelamin()
    {
        return $this->getGroupedByProvinsi('kelamin');
    }
}
