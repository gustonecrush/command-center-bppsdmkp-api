<?php

namespace App\Http\Controllers\Api;

use App\Models\SatuanPendidikan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SatuanPendidikanController extends Controller
{
    public function index(Request $request)
    {
        // Optional filter: RowID (you can rename or add more filters as needed)
        $rowId = $request->query('row_id');

        // Query satuan_pendidikan, optionally filtered
        $data = SatuanPendidikan::when($rowId, function ($query) use ($rowId) {
            return $query->where('RowID', $rowId);
        })->get();

        return response()->json($data);
    }
}
