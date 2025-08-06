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

        $data = TblSasaran::with(['indikatorKinerja' => function ($ikuQuery) use ($tahun) {
            $ikuQuery->where('tahun', $tahun)
                ->with(['outputKomponen']);
        }])
            ->where('tahun', $tahun)
            ->get()
            ->map(function ($sasaran) {
                return [
                    'sasaran' => $sasaran->nama,
                    'tahun' => $sasaran->tahun,
                    'indikator_kinerja' => $sasaran->iku ? $sasaran->iku->map(function ($iku) {
                        return [
                            'nama' => $iku->nama,
                            'unit_pj' => $iku->unit_pj,
                            'output' => $iku->output ? $iku->output->map(function ($output) {
                                return [
                                    'nama' => $output->nama,
                                    'kode' => $output->kode,
                                    'alokasi_anggaran' => $output->alokasi_anggaran,
                                    'realisasi_anggaran' => $output->realisasi_anggaran,
                                    'satuan_target' => $output->satuan_target,
                                    't_tw' => $output->t_tw,
                                    'r_tw' => $output->r_tw,
                                    'tw' => $output->tw,
                                ];
                            }) : [],
                        ];
                    }) : [],
                ];
            });

        return response()->json($data);
    }
}
