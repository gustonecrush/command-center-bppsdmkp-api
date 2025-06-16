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

    public function getStudentWithLocation()
    {
        $data = DB::table('peserta_didikls as pd')
            ->select('pd.nama_lengkap', 'mk.latitude', 'mk.longitude')
            ->leftJoin('mtr_kabupatens as mk', DB::raw("CAST(pd.id_kabupaten AS CHAR) COLLATE utf8mb4_general_ci"), '=', 'mk.id')
            ->get();
        return response()->json($data);
    }

    // public function summary(Request $request)
    // {
    //     // Get the satdik_id from the query parameters
    //     $satdik_id = $request->query('satdik_id');

    //     // Base query for the Alumni model
    //     $tingkatPendidikan = $request->query('tingkatPendidikan');

    //     $query = PesertaDidik::query();

    //     $query->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
    //         $q->join('satuan_pendidikan as sp', 'peserta_didiks.satdik_name', '=', 'sp.nama');

    //         if ($tingkatPendidikan === 'SUPM') {
    //             $q->where('sp.nama', 'LIKE', '%Sekolah%');
    //         } elseif ($tingkatPendidikan === 'Politeknik') {
    //             $q->where(function ($q2) {
    //                 $q2->where('sp.nama', 'LIKE', '%Politeknik%')
    //                     ->orWhere('sp.nama', 'LIKE', '%Akademi%')->orWhere('sp.nama', 'LIKE', '%Pasca%');
    //             });
    //         }
    //     });

    //     // Apply the satdik_id filter if provided
    //     if ($satdik_id) {
    //         $query->where('satdik_id', $satdik_id);
    //     }

    //     $parent_job_count = $query->clone()
    //         ->select('parent_job')
    //         ->groupBy('parent_job')
    //         ->selectRaw('parent_job, COUNT(*) as count')
    //         ->orderByDesc('count')
    //         ->get();

    //     $origin_count = $query->clone()
    //         ->select('origin')
    //         ->groupBy('origin')
    //         ->selectRaw('origin, COUNT(*) as count')
    //         ->orderByDesc('count')
    //         ->get();

    //     $prodis_count = $query->clone()
    //         ->select('prodis')
    //         ->groupBy('prodis')
    //         ->selectRaw('prodis, COUNT(*) as count')
    //         ->orderByDesc('count')
    //         ->get();

    //     $province_counts = $query->clone()
    //         ->select('province_name')
    //         ->groupBy('province_name')
    //         ->selectRaw('province_name, COUNT(*) as count')
    //         ->orderByDesc('count')
    //         ->get();


    //     $religion_counts = $query->clone()
    //         ->select('religion')
    //         ->groupBy('religion')
    //         ->selectRaw('religion, COUNT(*) as count')
    //         ->orderByDesc('count')
    //         ->get();

    //     $gender_counts = $query->clone()
    //         ->select('gender')
    //         ->groupBy('gender')
    //         ->selectRaw('gender, COUNT(*) as count')
    //         ->orderByDesc('count')
    //         ->get();

    //     $nama_satdik_count = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.satdik_name', '=', 'sp.nama')->when($satdik_id, function ($q) use ($satdik_id) {
    //         $satdikNama = DB::table('satuan_pendidikan')->where('RowID', $satdik_id)->value('nama');

    //         // Only apply filter if nama is found
    //         if ($satdikNama) {
    //             $q->where('sp.nama', $satdikNama);
    //         }
    //     })
    //         ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
    //             if ($tingkatPendidikan === 'SUPM') {
    //                 $q->where('sp.nama', 'LIKE', '%Sekolah%');
    //             } elseif ($tingkatPendidikan === 'Politeknik') {
    //                 $q->where(function ($q2) {
    //                     $q2->where('sp.nama', 'LIKE', '%Politeknik%')
    //                         ->orWhere('sp.nama', 'LIKE', '%Akademi%')->orWhere('sp.nama', 'LIKE', '%Pasca%');
    //                 });
    //             }
    //         })
    //         ->selectRaw('sp.nama as nama_satdik, COUNT(*) as count')
    //         ->groupBy('sp.nama')
    //         ->orderByDesc('count')
    //         ->get();

    //     // Prepare the data with totals
    //     $data = [
    //         'parent_job_count' => $parent_job_count,
    //         'origin_count' => $origin_count,
    //         'prodis_count' => $prodis_count,
    //         'province_counts' => $province_counts,
    //         'religion_counts' => $religion_counts,
    //         'gender_counts' => $gender_counts,
    //         'nama_satdik_count' => $nama_satdik_count,
    //     ];

    //     // Return a JSON response
    //     return response()->json($data);
    // }

    public function summary(Request $request)
    {
        try {
            $satdik_id = $request->query('satdik_id');
            $tingkatPendidikan = $request->query('tingkatPendidikan');

            $satdikNama = null;
            if ($satdik_id) {
                $satdikNama = DB::table('satuan_pendidikan')->where('RowID', $satdik_id)->value('nama');
            }

            $query = PesertaDidik::query();

            // $query = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.satdik_name', '=', 'sp.nama');

            // if ($satdikNama) {
            //     $query->where('sp.nama', $satdikNama);
            // }

            // if ($tingkatPendidikan && $tingkatPendidikan !== 'All') {
            //     if ($tingkatPendidikan === 'SUPM') {
            //         $query->where('sp.nama', 'LIKE', '%Sekolah%');
            //     } elseif ($tingkatPendidikan === 'Politeknik') {
            //         $query->where(function ($q) {
            //             $q->where('sp.nama', 'LIKE', '%Politeknik%')
            //                 ->orWhere('sp.nama', 'LIKE', '%Akademi%')
            //                 ->orWhere('sp.nama', 'LIKE', '%Pasca%')
            //                 ->orWhere('sp.nama', 'LIKE', '%Kampus%');
            //         });
            //     }
            // }

            if ($tingkatPendidikan && $tingkatPendidikan !== 'All') {
                $query->join('satuan_pendidikan as sp', 'tenaga_kependidikans.satdik_id', '=', 'sp.RowID');

                if ($tingkatPendidikan === 'SUPM') {
                    $query->where('sp.nama', 'LIKE', '%Sekolah%');
                } elseif ($tingkatPendidikan === 'Politeknik') {
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

            // ----------- REBUILD nama_satdik_count with custom mapping -------------
            // $aupKampus = [
            //     'Kampus Tegal',
            //     'Kampus Lampung',
            //     'Kampus Aceh',
            //     'Kampus Pariaman',
            //     'Kampus Maluku',
            // ];

            // $nama_satdik_query = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.satdik_name', '=', 'sp.nama');

            // if ($satdikNama) {
            //     $nama_satdik_query->where('sp.nama', $satdikNama);
            // }

            // if ($tingkatPendidikan && $tingkatPendidikan !== 'All') {
            //     if ($tingkatPendidikan === 'SUPM') {
            //         $nama_satdik_query->where('sp.nama', 'LIKE', '%Sekolah%');
            //     } elseif ($tingkatPendidikan === 'Politeknik') {
            //         $nama_satdik_query->where(function ($q) use ($aupKampus) {
            //             $q->where('sp.nama', 'LIKE', '%Politeknik%')
            //                 ->orWhere('sp.nama', 'LIKE', '%Akademi%')
            //                 ->orWhere('sp.nama', 'LIKE', '%Pasca%')
            //                 ->orWhereIn('sp.nama', $aupKampus);
            //         });
            //     }
            // }

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
                ->when($satdik_id, function ($q) use ($satdik_id) {
                    $q->where('peserta_didiks.id_satdik', $satdik_id);
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

            // Query kampus selain Politeknik AUP
            $otherSatdikCounts = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.id_satdik', '=', 'sp.RowID')
                ->when($satdik_id, function ($q) use ($satdik_id) {
                    $q->where('peserta_didiks.id_satdik', $satdik_id);
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

            // Gabungkan hasil politeknik AUP di depan, lalu kampus lain
            $nama_satdik_count = $otherSatdikCounts;
            if ($politeknikAupCount) {
                $nama_satdik_count->prepend($politeknikAupCount);
            }


            // ---------------- RETURN ------------------
            return response()->json([
                'parent_job_count' => $parent_job_count,
                'origin_count' => $origin_count,
                'prodis_count' => $prodis_count,
                'province_counts' => $province_counts,
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
}
