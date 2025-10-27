<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PesertaDidik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PesertaDidikController extends Controller
{
    public function index(Request $request)
    {
        $satdikId = $request->query('satdik_id');

        $pesertaDidik = PesertaDidik::when($satdikId, function ($query) use ($satdikId) {
            return $query->where('satdik_id', $satdikId);
        })->get();

        return response()->json($pesertaDidik);
    }

    public function getStudentWithLocation(Request $request)
    {
        $tingkatPendidikan = $request->input('tingkatPendidikan');
        $provinsi = $request->input('provinsi');
        $kabupaten = $request->input('kabupaten');

        $query = DB::table('peserta_didiks as pd')
            ->select('pd.nama_lengkap', 'pd.id_peserta_didik', 'mk.latitude', 'mk.longitude')
            ->leftJoin('mtr_kabupatens as mk', DB::raw("CAST(pd.id_kabupaten AS CHAR) COLLATE utf8mb4_general_ci"), '=', 'mk.id');

        // Filter by tingkatPendidikan
        if ($tingkatPendidikan && $tingkatPendidikan !== 'All') {
            $query->join('satuan_pendidikan as sp', 'pd.id_satdik', '=', 'sp.RowID');

            if ($tingkatPendidikan === 'Menengah') {
                $query->where('sp.nama', 'LIKE', '%Sekolah%');
            } elseif ($tingkatPendidikan === 'Tinggi') {
                $query->where(function ($q2) {
                    $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                        ->orWhere('sp.nama', 'LIKE', '%Akademi%')
                        ->orWhere('sp.nama', 'LIKE', '%Pasca%');
                });
            }
        }

        // Filter by provinsi
        if ($provinsi && $provinsi !== 'All') {
            $query->where('mk.id_provinsi', $provinsi);
        }

        // Filter by kabupaten
        if ($kabupaten && $kabupaten !== 'All') {
            $query->where('mk.id_kabupaten', $kabupaten);
        }

        $data = $query->get();

        return response()->json($data);
    }


    public function summary(Request $request)
    {
        try {
            $satdik_id = $request->query('satdik_id');
            $tingkatPendidikan = $request->query('tingkatPendidikan');
            $provinsi = $request->query('provinsi');
            $kabupaten = $request->query('kabupaten');

            $satdikNama = null;
            $query = PesertaDidik::query();
            $query->where('status', 'Active');

            if ($satdik_id) {
                $query->where('peserta_didiks.id_satdik', $satdik_id);
            }

            // Filter by provinsi
            if ($provinsi && $provinsi !== 'All') {
                $query->where('peserta_didiks.provinsi', $provinsi);
            }

            // Filter by kabupaten
            if ($kabupaten && $kabupaten !== 'All') {
                $query->where('peserta_didiks.kabupaten', $kabupaten);
            }

            if ($tingkatPendidikan && $tingkatPendidikan !== 'All') {
                $query->join('satuan_pendidikan as sp', 'peserta_didiks.id_satdik', '=', 'sp.RowID');

                if ($tingkatPendidikan === 'Menengah') {
                    $query->where('sp.nama', 'LIKE', '%Sekolah%');
                } elseif ($tingkatPendidikan === 'Tinggi') {
                    $query->where(function ($q2) {
                        $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                            ->orWhere('sp.nama', 'LIKE', '%Akademi%')
                            ->orWhere('sp.nama', 'LIKE', '%Pasca%');
                    });
                }
            }

            $parent_job_count = (clone $query)
                ->selectRaw('pekerjaan_orang_tua, COUNT(*) as count')
                ->groupBy('pekerjaan_orang_tua')
                ->orderByDesc('count')
                ->get();

            $origin_count = (clone $query)
                ->selectRaw('asal, COUNT(*) as count')
                ->groupBy('asal')
                ->orderByDesc('count')
                ->get();

            $level_count = (clone $query)
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->orderByDesc('count')
                ->get();

            $jenjang_pendidikan_count = (clone $query)
                ->selectRaw(expression: 'jenjang_pendidikan, COUNT(*) as count')
                ->groupBy('jenjang_pendidikan')
                ->orderByDesc('count')
                ->get();

            $tingkat_count = (clone $query)
                ->selectRaw(expression: 'tingkat, COUNT(*) as count')
                ->groupBy('tingkat')
                ->orderByDesc('count')
                ->get();

            $prodis_count = (clone $query)
                ->join('mtr_program_studis', 'peserta_didiks.id_program_studi', '=', 'mtr_program_studis.id')
                ->selectRaw('mtr_program_studis.program_studi_singkatan, COUNT(*) as count')
                ->groupBy('mtr_program_studis.program_studi_singkatan')
                ->orderByDesc('count')
                ->get();

            $province_counts = (clone $query)
                ->selectRaw('provinsi, COUNT(*) as count')
                ->groupBy('provinsi')
                ->orderByDesc('count')
                ->get();

            $religion_counts = (clone $query)
                ->selectRaw('agama, COUNT(*) as count')
                ->groupBy('agama')
                ->orderByDesc('count')
                ->get();

            $gender_counts = (clone $query)
                ->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->orderByDesc('count')
                ->get();

            $kampusAUP = [
                'Politeknik AUP',
                'Pasca Sarjana Politeknik AUP',
                'Kampus Tegal',
                'Kampus Lampung',
                'Kampus Aceh',
                'Kampus Pariaman',
                'Kampus Maluku',
            ];

            $politeknikAupCount = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.id_satdik', '=', 'sp.RowID')
                ->where('peserta_didiks.status', 'Active')
                ->when($satdik_id, function ($q) use ($satdik_id) {
                    $q->where('peserta_didiks.id_satdik', $satdik_id);
                })
                ->when($provinsi && $provinsi !== 'All', function ($q) use ($provinsi) {
                    $q->where('peserta_didiks.provinsi', $provinsi);
                })
                ->when($kabupaten && $kabupaten !== 'All', function ($q) use ($kabupaten) {
                    $q->where('peserta_didiks.id_kabupaten', $kabupaten);
                })
                ->whereIn('sp.nama', $kampusAUP)
                ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
                    if ($tingkatPendidikan === 'Menengah') {
                        $q->where('sp.nama', 'LIKE', '%Sekolah%');
                    } elseif ($tingkatPendidikan === 'Tinggi') {
                        $q->where(function ($q2) {
                            $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                                ->orWhere('sp.nama', 'LIKE', '%Akademi%')
                                ->orWhere('sp.nama', 'LIKE', '%Pasca%');
                        });
                    }
                })
                ->selectRaw('"Politeknik AUP" as nama_satdik, COUNT(*) as count')
                ->groupBy('nama_satdik')
                ->first();

            $otherSatdikCounts = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.id_satdik', '=', 'sp.RowID')
                ->where('peserta_didiks.status', 'Active')
                ->when($satdik_id, function ($q) use ($satdik_id) {
                    $q->where('peserta_didiks.id_satdik', $satdik_id);
                })
                ->when($provinsi && $provinsi !== 'All', function ($q) use ($provinsi) {
                    $q->where('peserta_didiks.provinsi', $provinsi);
                })
                ->when($kabupaten && $kabupaten !== 'All', function ($q) use ($kabupaten) {
                    $q->where('peserta_didiks.id_kabupaten', $kabupaten);
                })
                ->whereNotIn('sp.nama', $kampusAUP)
                ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
                    if ($tingkatPendidikan === 'Menengah') {
                        $q->where('sp.nama', 'LIKE', '%Sekolah%');
                    } elseif ($tingkatPendidikan === 'Tinggi') {
                        $q->where(function ($q2) {
                            $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                                ->orWhere('sp.nama', 'LIKE', '%Akademi%')
                                ->orWhere('sp.nama', 'LIKE', '%Pasca%');
                        });
                    }
                })
                ->selectRaw('sp.nama as nama_satdik, COUNT(*) as count')
                ->groupBy('sp.nama')
                ->orderByDesc('count')
                ->get();

            $nama_satdik_count = $otherSatdikCounts;
            if ($politeknikAupCount) {
                $nama_satdik_count->prepend($politeknikAupCount);
            }

            return response()->json([
                'parent_job_count' => $parent_job_count,
                'origin_count' => $origin_count,
                'level_count' => $level_count,
                'prodis_count' => $prodis_count,
                'province_counts' => $province_counts,
                'jenjang_counts' => $jenjang_pendidikan_count,
                'tingkat_counts' => $tingkat_count,
                'religion_counts' => $religion_counts,
                'gender_counts' => $gender_counts,
                'nama_satdik_count' => $nama_satdik_count,
            ]);
        } catch (\Exception $e) {
            Log::error('Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }

    public function summaryPerType(Request $request)
    {
        $data = PesertaDidik::select(
            'gender',
            'religion',
            'province_name',
            'satdik_name',
            'prodis',
            'origin',
            'parent_job',
            'enroll_year',
            'level',
            'status'
        )->get();

        $grouped = [
            'pendidikan_tinggi' => $data->filter(fn($d) => str_contains(strtolower($d->satdik_name), 'politeknik')),
            'pendidikan_menengah' => $data->reject(fn($d) => str_contains(strtolower($d->satdik_name), 'politeknik')),
        ];

        $result = [];

        foreach ($grouped as $key => $group) {
            $result[$key] = [
                'total_count' => $group->count(),
                'gender_count' => $group->groupBy('gender')->map(fn($g) => [
                    'gender' => $g->first()->gender,
                    'count' => $g->count()
                ])->values(),

                'religion_count' => $group->groupBy('religion')->map(fn($g) => [
                    'religion' => $g->first()->religion,
                    'count' => $g->count()
                ])->values(),

                'province_count' => $group->groupBy('province_name')->map(fn($g) => [
                    'province_name' => $g->first()->province_name,
                    'count' => $g->count()
                ])->values(),

                'satdik_count' => $group->groupBy('satdik_name')->map(fn($g) => [
                    'satdik_name' => $g->first()->satdik_name,
                    'count' => $g->count()
                ])->values(),

                'prodis_count' => $group->groupBy('prodis')->map(fn($g) => [
                    'prodis' => $g->first()->prodis,
                    'count' => $g->count()
                ])->values(),

                'origin_count' => $group->groupBy('origin')->map(fn($g) => [
                    'origin' => $g->first()->origin,
                    'count' => $g->count()
                ])->values(),

                'parent_job_count' => $group->groupBy('parent_job')->map(fn($g) => [
                    'parent_job' => $g->first()->parent_job,
                    'count' => $g->count()
                ])->values(),

                'enroll_year_count' => $group->groupBy('enroll_year')->map(fn($g) => [
                    'enroll_year' => $g->first()->enroll_year,
                    'count' => $g->count()
                ])->values(),

                'level_count' => $group->groupBy('level')->map(fn($g) => [
                    'level' => $g->first()->level,
                    'count' => $g->count()
                ])->values(),

                'status_count' => $group->groupBy('status')->map(fn($g) => [
                    'status' => $g->first()->status,
                    'count' => $g->count()
                ])->values(),
            ];
        }

        return response()->json($result);
    }

    public function show($id)
    {
        try {
            $peserta = DB::table('peserta_didiks as pd')
                ->select(
                    'pd.*',
                    'mk.latitude',
                    'mk.longitude'
                )
                ->leftJoin('mtr_kabupatens as mk', DB::raw("CAST(pd.id_kabupaten AS CHAR) COLLATE utf8mb4_general_ci"), '=', 'mk.id')
                ->where('pd.id_peserta_didik', $id)
                ->where('pd.status', 'Active')
                ->first();

            if (!$peserta) {
                return response()->json(['message' => 'Peserta Didik not found'], 404);
            }

            return response()->json($peserta);
        } catch (\Exception $e) {
            Log::error("Error fetching peserta_didik: " . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
