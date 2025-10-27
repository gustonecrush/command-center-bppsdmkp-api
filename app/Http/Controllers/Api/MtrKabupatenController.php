<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MtrKabupaten;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MtrKabupatenController extends Controller
{
    public function getByProvinsi($id_provinsi)
    {
        $kabupatens = MtrKabupaten::where('id_provinsi', $id_provinsi)
            ->select('id', 'id_provinsi', 'kabupaten', 'alt_kabupaten')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kabupatens
        ], 200);
    }
}
