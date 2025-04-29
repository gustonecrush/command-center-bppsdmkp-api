<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve the satdik_id query parameter
        $satdikId = $request->query('satdik_id');

        // Query the alumni based on the provided satdik_id
        $alumni = Alumni::when($satdikId, function ($query) use ($satdikId) {
            return $query->where('satdik_id', $satdikId);
        })->get();

        // Return the alumni records as a JSON response
        return response()->json($alumni);
    }

    public function summary(Request $request)
    {
        // Get the satdik_id from the query parameters
        $satdik_id = $request->query('satdik_id');

        // Base query for the Alumni model
        $tingkatPendidikan = $request->query('tingkatPendidikan');

        $query = Alumni::query();

        $query->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
            $q->join('satuan_pendidikan as sp', 'alumnis.satdik_id', '=', 'sp.RowID');

            if ($tingkatPendidikan === 'SUPM') {
                $q->where('sp.nama', 'LIKE', '%Sekolah%');
            } elseif ($tingkatPendidikan === 'Politeknik') {
                $q->where(function ($q2) {
                    $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                        ->orWhere('sp.nama', 'LIKE', '%Akademi%')->orWhere('sp.nama', 'LIKE', '%Pasca%');
                });
            }
        });

        // Apply the satdik_id filter if provided
        if ($satdik_id) {
            $query->where('satdik_id', $satdik_id);
        }

        // Get counts for each column group: absorption and company_country
        $absorption_counts = $query->clone()
            ->select('absorption')
            ->groupBy('absorption')
            ->selectRaw('absorption, COUNT(*) as count')
            ->get();

        $company_country_counts = $query->clone()
            ->select('company_country')
            ->groupBy('company_country')
            ->selectRaw('company_country, COUNT(*) as count')
            ->get();

        $study_program_count = $query->clone()
            ->where('work_status', 'sudah')
            ->select('study_program')
            ->groupBy('study_program')
            ->selectRaw('study_program, COUNT(*) as count')
            ->get();

        $work_status_counts = $query->clone()
            ->select('work_status')
            ->groupBy('work_status')
            ->selectRaw('work_status, COUNT(*) as count')
            ->get();


        $income_range_counts = $query->clone()
            ->select('income_range')
            ->groupBy('income_range')
            ->selectRaw('income_range, COUNT(*) as count')
            ->get();

        $gender_counts = $query->clone()
            ->select('gender')
            ->groupBy('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->get();

        $employment_by_year_counts = $query->clone()
            ->where('work_status', 'sudah')
            ->select('year')
            ->groupBy('year')
            ->selectRaw('year, COUNT(*) as count')
            ->get();

        $alumni_per_year_counts = $query->clone()
            ->select('year')
            ->groupBy('year')
            ->selectRaw('year, COUNT(*) as count')
            ->get();

        $top_job_fields = $query->clone()
            ->select('job_field')
            ->groupBy('job_field')
            ->selectRaw('job_field, COUNT(*) as count')
            ->orderByDesc('count')
            ->get();

        $nama_satdik_count = Alumni::join('satuan_pendidikan as sp', 'alumnis.satdik_id', '=', 'sp.RowID')
            ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
                if ($tingkatPendidikan === 'SUPM') {
                    $q->where('sp.nama', 'LIKE', '%Sekolah%');
                } elseif ($tingkatPendidikan === 'Politeknik') {
                    $q->where(function ($q2) {
                        $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                            ->orWhere('sp.nama', 'LIKE', '%Akademi%')->orWhere('sp.nama', 'LIKE', '%Pasca%');
                    });
                }
            })
            ->selectRaw('sp.nama as nama_satdik, COUNT(*) as count')
            ->groupBy('sp.nama')
            ->orderByDesc('count')
            ->get();


        // Calculate total counts
        $total_absorption_count = $absorption_counts->sum('count');
        $total_study_program_count = $study_program_count->sum('count');
        $total_company_country_count = $company_country_counts->sum('count');
        $total_income_range_count = $income_range_counts->sum('count');
        $total_gender_count = $gender_counts->sum('count');
        $total_work_status_count = $work_status_counts->sum('count');
        $total_employment_by_year_count = $employment_by_year_counts->sum('count');
        $total_alumni_per_year_count = $alumni_per_year_counts->sum('count');
        $total_top_job_fields_count = $top_job_fields->sum('count');

        // Prepare the data with totals
        $data = [
            'absorption_count' => $absorption_counts,
            'total_absorption_count' => $total_absorption_count,
            'company_country_count' => $company_country_counts,
            'total_company_country_count' => $total_company_country_count,
            'income_range_count' => $income_range_counts,
            'total_income_range_count' => $total_income_range_count,
            'gender_count' => $gender_counts,
            'total_gender_count' => $total_gender_count,
            'study_program_count_has_work' => $study_program_count,
            'total_study_program_count_has_work' => $total_study_program_count,
            'work_status_count' => $work_status_counts,
            'total_work_status_count' => $total_work_status_count,
            'employment_by_year_count' => $employment_by_year_counts,
            'total_employment_by_year_count' => $total_employment_by_year_count,
            'alumni_per_year_count' => $alumni_per_year_counts,
            'total_alumni_per_year_count' => $total_alumni_per_year_count,
            'top_job_fields' => $top_job_fields,
            'total_top_job_fields_count' => $total_top_job_fields_count,
            'nama_satdik_count' => $nama_satdik_count,
        ];

        // Return a JSON response
        return response()->json($data);
    }
}
