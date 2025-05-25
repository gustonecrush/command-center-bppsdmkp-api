<?php

namespace App\Http\Controllers\Api;

use App\Models\SatuanPendidikan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

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

    public function updateWebsite(Request $request, $rowId)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // max 2MB
        ]);

        $satuan = SatuanPendidikan::where('RowID', $rowId)->first();

        if (!$satuan) {
            return response()->json(['message' => 'Data not found.'], 404);
        }

        if ($request->hasFile('image')) {
            $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('uploads', $filename, 'public');

            // Save the URL or relative path to the Website column
            $satuan->Website = '/storage/' . $path;
            $satuan->save();

            return response()->json([
                'message' => 'Image uploaded and Website updated.',
                'data' => $satuan
            ]);
        }

        return response()->json(['message' => 'Image not uploaded.'], 400);
    }
}
