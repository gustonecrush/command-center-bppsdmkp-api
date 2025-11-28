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
        $rowId = $request->query('row_id');

        $data = SatuanPendidikan::when($rowId, function ($query) use ($rowId) {
            return $query->where('RowID', $rowId);
        })->get();

        return response()->json($data);
    }

    // CREATE
    public function store(Request $request)
    {
        $request->validate([
            'Nama' => 'required|string|max:255',
            'Jenjang' => 'nullable|string|max:255',
            'Alamat' => 'nullable|string|max:255',
            'Pimpinan' => 'nullable|string|max:255',
            'Website' => 'nullable|string|max:255',
            'Jumlah_Peserta_Didik' => 'nullable|integer',
            'Jumlah_Alumni' => 'nullable|integer',
            'Jumlah_Pendidik' => 'nullable|integer',
            'Jumlah_Tenaga_Kependidikan' => 'nullable|integer',
            'Lokasi_Lintang' => 'nullable|numeric',
            'Lokasi_Bujur' => 'nullable|numeric',
            'Akreditasi_Lembaga' => 'required|string|max:255',
            'Akreditasi_Prodi' => 'required|string',
            'Kode_Satker' => 'required|string|max:6',
        ]);

        $satuan = SatuanPendidikan::create([
            'Tanggal_Data' => now(),
            'Is_Manual_Update' => 1,
            'Nama' => $request->Nama,
            'Jenjang' => $request->Jenjang,
            'Alamat' => $request->Alamat,
            'Pimpinan' => $request->Pimpinan,
            'Website' => $request->Website,
            'Jumlah_Peserta_Didik' => $request->Jumlah_Peserta_Didik,
            'Jumlah_Alumni' => $request->Jumlah_Alumni,
            'Jumlah_Pendidik' => $request->Jumlah_Pendidik,
            'Jumlah_Tenaga_Kependidikan' => $request->Jumlah_Tenaga_Kependidikan,
            'Lokasi_Lintang' => $request->Lokasi_Lintang,
            'Lokasi_Bujur' => $request->Lokasi_Bujur,
            'Akreditasi_Lembaga' => $request->Akreditasi_Lembaga,
            'Akreditasi_Prodi' => $request->Akreditasi_Prodi,
            'Kode_Satker' => $request->Kode_Satker,
        ]);

        return response()->json([
            'message' => 'Data berhasil ditambahkan',
            'data' => $satuan
        ], 201);
    }

    // UPDATE
    public function update(Request $request, $rowId)
    {
        $satuan = SatuanPendidikan::where('RowID', $rowId)->first();

        if (!$satuan) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'Nama' => 'nullable|string|max:255',
            'Jenjang' => 'nullable|string|max:255',
            'Alamat' => 'nullable|string|max:255',
            'Pimpinan' => 'nullable|string|max:255',
            'Website' => 'nullable|string|max:255',
            'Jumlah_Peserta_Didik' => 'nullable|integer',
            'Jumlah_Alumni' => 'nullable|integer',
            'Jumlah_Pendidik' => 'nullable|integer',
            'Jumlah_Tenaga_Kependidikan' => 'nullable|integer',
            'Lokasi_Lintang' => 'nullable|numeric',
            'Lokasi_Bujur' => 'nullable|numeric',
            'Akreditasi_Lembaga' => 'nullable|string|max:255',
            'Akreditasi_Prodi' => 'nullable|string',
            'Kode_Satker' => 'nullable|string|max:6',
        ]);

        $satuan->update(array_filter($request->all()));

        return response()->json([
            'message' => 'Data berhasil diupdate',
            'data' => $satuan
        ]);
    }

    // DELETE
    public function destroy($rowId)
    {
        $satuan = SatuanPendidikan::where('RowID', $rowId)->first();

        if (!$satuan) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $satuan->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function updateWebsite(Request $request, $rowId)
    {
        $request->validate([
            'image' => 'required|image|max:20048',
        ]);

        $satuan = SatuanPendidikan::where('RowID', $rowId)->first();

        if (!$satuan) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        if ($request->hasFile('image')) {
            $filename = Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('uploads', $filename, 'public');

            $satuan->Website = '/storage/' . $path;
            $satuan->save();

            return response()->json([
                'message' => 'Image berhasil diupload',
                'data' => $satuan
            ]);
        }

        return response()->json(['message' => 'Image tidak terupload'], 400);
    }
}
