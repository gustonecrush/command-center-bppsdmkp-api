<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PesertaDidik;
use Illuminate\Http\Request;

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

    public function summary(Request $request)
    {
        // Get the satdik_id from the query parameters
        $satdik_id = $request->query('satdik_id');

        // Base query for the Alumni model
        $tingkatPendidikan = $request->query('tingkatPendidikan');

        $query = PesertaDidik::query();

        $query->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
            $q->join('satuan_pendidikan as sp', 'peserta_didiks.satdik_name', '=', 'sp.Nama');

            if ($tingkatPendidikan === 'SUPM') {
                $q->where('sp.Nama', 'LIKE', '%Sekolah%');
            } elseif ($tingkatPendidikan === 'Politeknik') {
                $q->where(function ($q2) {
                    $q2->where('sp.Nama', 'LIKE', '%Politeknik%')
                        ->orWhere('sp.Nama', 'LIKE', '%Akademi%')->orWhere('sp.Nama', 'LIKE', '%Pasca%');
                });
            }
        });

        // Apply the satdik_id filter if provided
        if ($satdik_id) {
            $query->where('satdik_id', $satdik_id);
        }

        $parent_job_count = $query->clone()
            ->select('parent_job')
            ->groupBy('parent_job')
            ->selectRaw('parent_job, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $origin_count = $query->clone()
            ->select('origin')
            ->groupBy('origin')
            ->selectRaw('origin, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $prodis_count = $query->clone()
            ->select('prodis')
            ->groupBy('prodis')
            ->selectRaw('prodis, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $province_counts = $query->clone()
            ->select('province_name')
            ->groupBy('province_name')
            ->selectRaw('province_name, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();


        $religion_counts = $query->clone()
            ->select('religion')
            ->groupBy('religion')
            ->selectRaw('religion, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $gender_counts = $query->clone()
            ->select('gender')
            ->groupBy('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $nama_satdik_count = PesertaDidik::join('satuan_pendidikan as sp', 'peserta_didiks.satdik_name', '=', 'sp.Nama')
            ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
                if ($tingkatPendidikan === 'SUPM') {
                    $q->where('sp.Nama', 'LIKE', '%Sekolah%');
                } elseif ($tingkatPendidikan === 'Politeknik') {
                    $q->where(function ($q2) {
                        $q2->where('sp.Nama', 'LIKE', '%Politeknik%')
                            ->orWhere('sp.Nama', 'LIKE', '%Akademi%')->orWhere('sp.Nama', 'LIKE', '%Pasca%');
                    });
                }
            })
            ->selectRaw('sp.Nama as nama_satdik, COUNT(*) as count')
            ->groupBy('sp.Nama')
            ->orderByDesc('count')
            ->get();

        // Prepare the data with totals
        $data = [
            'parent_job_count' => $parent_job_count,
            'origin_count' => $origin_count,
            'prodis_count' => $prodis_count,
            'province_counts' => $province_counts,
            'religion_counts' => $religion_counts,
            'gender_counts' => $gender_counts,
            'nama_satdik_count' => $nama_satdik_count,
        ];

        // Return a JSON response
        return response()->json($data);
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
