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
