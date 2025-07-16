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
        return $this->getGroupedByProvinsi('status');
    }
    public function groupByJabatan()
    {
        return $this->getGroupedByProvinsi('jabatan');
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
