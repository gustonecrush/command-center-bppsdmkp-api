<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penyuluh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyuluhController extends Controller
{
    public function groupByProvinsiAnd($column)
    {
        if (!in_array($column, ['status', 'jabatan', 'pendidikan', 'kelompok_usia', 'kelamin'])) {
            return response()->json(['error' => 'Invalid column'], 400);
        }

        $data = Penyuluh::select('provinsi', $column, DB::raw('count(*) as total'))
            ->groupBy('provinsi', $column)
            ->orderBy('provinsi')
            ->get();

        return response()->json($data);
    }
}
