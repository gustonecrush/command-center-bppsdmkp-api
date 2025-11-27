<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TblIndikatorKinerja;
use App\Models\TblOutputKinerja;
use App\Models\TblSasaranKinerja;
use Illuminate\Support\Facades\Validator;

class KinerjaController extends Controller
{
    // GET all data with filter
    public function index(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');

        if (!$tahun) {
            return response()->json(['message' => 'Parameter tahun wajib diisi.'], 400);
        }

        $data = TblSasaranKinerja::with(['indikatorKinerja' => function ($ikuQuery) use ($tahun) {
            $ikuQuery->where('tahun', $tahun)->with('outputKomponen');
        }])
            ->where('tahun', $tahun)
            ->get();

        $result = $data->map(function ($sasaran) use ($tw) {
            return [
                'sasaran' => $sasaran->nama,
                'tahun' => $sasaran->tahun,
                'indikator_kinerja' => $sasaran->indikatorKinerja->map(function ($iku) use ($tw) {
                    return [
                        'nama' => $iku->nama,
                        'unit_pj' => $iku->unit_pj,
                        'output' => $iku->outputKomponen
                            ->when($tw, fn($query) => $query->filter(fn($o) => $o->tw === $tw))
                            ->map(function ($output) {
                                return [
                                    'nama' => $output->nama,
                                    'kode' => $output->kode,
                                    'alokasi_anggaran' => (float) $output->alokasi_anggaran,
                                    'realisasi_anggaran' => (float) $output->realisasi_anggaran,
                                    'satuan_target' => $output->satuan_target,
                                    't_tw' => (float) $output->t_tw,
                                    'r_tw' => (float) $output->r_tw,
                                    'tw' => $output->tw,
                                ];
                            })->values(),
                    ];
                }),
            ];
        });

        return response()->json($result);
    }

    // ========== SASARAN CRUD ==========
    public function getSasaran(Request $request)
    {
        $tahun = $request->query('tahun');
        $query = TblSasaranKinerja::query();

        if ($tahun) {
            $query->where('tahun', $tahun);
        }

        return response()->json($query->get());
    }

    public function storeSasaran(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'tahun' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sasaran = TblSasaranKinerja::create($request->all());
        return response()->json($sasaran, 201);
    }

    public function updateSasaran(Request $request, $id)
    {
        $sasaran = TblSasaranKinerja::find($id);
        if (!$sasaran) {
            return response()->json(['message' => 'Sasaran tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'string|max:255',
            'tahun' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sasaran->update($request->all());
        return response()->json($sasaran);
    }

    public function deleteSasaran($id)
    {
        $sasaran = TblSasaranKinerja::find($id);
        if (!$sasaran) {
            return response()->json(['message' => 'Sasaran tidak ditemukan'], 404);
        }

        $sasaran->delete();
        return response()->json(['message' => 'Sasaran berhasil dihapus']);
    }

    // ========== IKU CRUD ==========
    public function getIku(Request $request)
    {
        $tahun = $request->query('tahun');
        $id_sasaran = $request->query('id_sasaran');

        $query = TblIndikatorKinerja::with('sasaran');

        if ($tahun) {
            $query->where('tahun', $tahun);
        }
        if ($id_sasaran) {
            $query->where('id_sasaran', $id_sasaran);
        }

        return response()->json($query->get());
    }

    public function storeIku(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_sasaran' => 'required|exists:tbl_k_sasaran,id',
            'nama' => 'required|string|max:255',
            'unit_pj' => 'required|string|max:255',
            'tahun' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $iku = TblIndikatorKinerja::create($request->all());
        return response()->json($iku, 201);
    }

    public function updateIku(Request $request, $id)
    {
        $iku = TblIndikatorKinerja::find($id);
        if (!$iku) {
            return response()->json(['message' => 'IKU tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_sasaran' => 'exists:tbl_k_sasaran,id',
            'nama' => 'string|max:255',
            'unit_pj' => 'string|max:255',
            'tahun' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $iku->update($request->all());
        return response()->json($iku);
    }

    public function deleteIku($id)
    {
        $iku = TblIndikatorKinerja::find($id);
        if (!$iku) {
            return response()->json(['message' => 'IKU tidak ditemukan'], 404);
        }

        $iku->delete();
        return response()->json(['message' => 'IKU berhasil dihapus']);
    }

    // ========== OUTPUT CRUD ==========
    public function getOutput(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $id_iku = $request->query('id_iku');

        $query = TblOutputKinerja::with('iku');

        if ($tahun) {
            $query->where('tahun', $tahun);
        }
        if ($tw) {
            $query->where('tw', $tw);
        }
        if ($id_iku) {
            $query->where('id_iku', $id_iku);
        }

        return response()->json($query->get());
    }

    public function storeOutput(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_iku' => 'required|exists:tbl_k_iku,id',
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:100',
            'alokasi_anggaran' => 'numeric',
            'realisasi_anggaran' => 'numeric',
            'satuan_target' => 'nullable|string|max:100',
            't_tw' => 'numeric',
            'r_tw' => 'numeric',
            'tw' => 'required|string|max:255',
            'tahun' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $output = TblOutputKinerja::create($request->all());
        return response()->json($output, 201);
    }

    public function updateOutput(Request $request, $id)
    {
        $output = TblOutputKinerja::find($id);
        if (!$output) {
            return response()->json(['message' => 'Output tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_iku' => 'exists:tbl_k_iku,id',
            'nama' => 'string|max:255',
            'kode' => 'string|max:100',
            'alokasi_anggaran' => 'numeric',
            'realisasi_anggaran' => 'numeric',
            'satuan_target' => 'nullable|string|max:100',
            't_tw' => 'numeric',
            'r_tw' => 'numeric',
            'tw' => 'string|max:255',
            'tahun' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $output->update($request->all());
        return response()->json($output);
    }

    public function deleteOutput($id)
    {
        $output = TblOutputKinerja::find($id);
        if (!$output) {
            return response()->json(['message' => 'Output tidak ditemukan'], 404);
        }

        $output->delete();
        return response()->json(['message' => 'Output berhasil dihapus']);
    }
}
