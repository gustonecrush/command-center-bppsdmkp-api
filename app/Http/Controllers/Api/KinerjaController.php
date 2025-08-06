<?php

namespace App\Http\Controllers\Api;

use App\Models\TblSasaran;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class KinerjaController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->query('tahun');

        if (!$tahun) {
            return response()->json(['message' => 'Parameter tahun wajib diisi.'], 400);
        }

        $data = TblSasaran::with(['indikatorKinerja' => function ($ikuQuery) use ($tahun) {
            $ikuQuery->where('tahun', $tahun)
                ->with('outputKomponen');
        }])
            ->where('tahun', $tahun)
            ->get();

        $result = $data->map(function ($sasaran) {
            return [
                'sasaran' => $sasaran->nama,
                'tahun' => $sasaran->tahun,
                'indikator_kinerja' => $sasaran->indikatorKinerja->map(function ($iku) {
                    return [
                        'nama' => $iku->nama,
                        'unit_pj' => $iku->unit_pj,
                        'output' => $iku->outputKomponen->map(function ($output) {
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
                        }),
                    ];
                }),
            ];
        });

        return response()->json($result);
    }
}
