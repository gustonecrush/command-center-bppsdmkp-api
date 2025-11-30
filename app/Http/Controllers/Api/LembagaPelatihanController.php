<?php

namespace App\Http\Controllers\Api;

use App\Models\LembagaPelatihan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class LembagaPelatihanController extends Controller
{
    public function index(Request $request)
    {
        $rowId = $request->query('row_id');

        $data = LembagaPelatihan::when($rowId, function ($query) use ($rowId) {
            return $query->where('RowID', $rowId);
        })->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Nama' => 'required|string|max:255',
            'Jenis' => 'nullable|string|max:255',
            'Alamat' => 'nullable|string|max:255',
            'Pimpinan' => 'nullable|string|max:255',
            'Website' => 'nullable|string|max:255',
            'Email' => 'nullable|email|max:255',
            'Telepon' => 'nullable|string|max:50',
            'Jumlah_Instruktur' => 'nullable|integer',
            'Jumlah_Widyaiswara' => 'nullable|integer',
            'Lokasi_Lintang' => 'nullable|numeric',
            'Lokasi_Bujur' => 'nullable|numeric',
            'Kode_Satker' => 'nullable|string|max:6',
        ]);

        $lembaga = LembagaPelatihan::create([
            'Tanggal_Data' => now(),
            'Is_Manual_Update' => 1,
            'Nama' => $request->Nama,
            'Jenis' => $request->Jenis,
            'Alamat' => $request->Alamat,
            'Pimpinan' => $request->Pimpinan,
            'Website' => $request->Website,
            'Email' => $request->Email,
            'Telepon' => $request->Telepon,
            'Jumlah_Instruktur' => $request->Jumlah_Instruktur,
            'Jumlah_Widyaiswara' => $request->Jumlah_Widyaiswara,
            'Lokasi_Lintang' => $request->Lokasi_Lintang,
            'Lokasi_Bujur' => $request->Lokasi_Bujur,
            'Kode_Satker' => $request->Kode_Satker,
        ]);

        return response()->json([
            'message' => 'Data berhasil ditambahkan',
            'data' => $lembaga
        ], 201);
    }

    public function update(Request $request, $rowId)
    {
        $lembaga = LembagaPelatihan::where('RowID', $rowId)->first();

        if (!$lembaga) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'Nama' => 'nullable|string|max:255',
            'Jenis' => 'nullable|string|max:255',
            'Alamat' => 'nullable|string|max:255',
            'Pimpinan' => 'nullable|string|max:255',
            'Website' => 'nullable|string|max:255',
            'Email' => 'nullable|email|max:255',
            'Telepon' => 'nullable|string|max:50',
            'Jumlah_Instruktur' => 'nullable|integer',
            'Jumlah_Widyaiswara' => 'nullable|integer',
            'Lokasi_Lintang' => 'nullable|numeric',
            'Lokasi_Bujur' => 'nullable|numeric',
            'Kode_Satker' => 'nullable|string|max:6',
        ]);

        $lembaga->update(array_filter($request->all()));

        return response()->json([
            'message' => 'Data berhasil diupdate',
            'data' => $lembaga
        ]);
    }

    public function destroy($rowId)
    {
        $lembaga = LembagaPelatihan::where('RowID', $rowId)->first();

        if (!$lembaga) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $lembaga->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function updateWebsite(Request $request, $rowId)
    {
        $request->validate([
            'image' => 'required|image|max:20048',
        ]);

        $lembaga = LembagaPelatihan::where('RowID', $rowId)->first();

        if (!$lembaga) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        if ($request->hasFile('image')) {
            $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('uploads', $filename, 'public');

            $lembaga->Website = '/storage/' . $path;
            $lembaga->save();

            return response()->json([
                'message' => 'Image berhasil diupload',
                'data' => $lembaga
            ]);
        }

        return response()->json(['message' => 'Image tidak terupload'], 400);
    }
}
