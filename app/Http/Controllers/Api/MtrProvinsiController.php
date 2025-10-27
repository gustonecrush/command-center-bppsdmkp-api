<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MtrProvinsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MtrProvinsiController extends Controller
{
    public function index()
    {
        $provinsis = MtrProvinsi::select('id', 'kode', 'provinsi')->get();

        return response()->json([
            'success' => true,
            'data' => $provinsis
        ], 200);
    }
}
