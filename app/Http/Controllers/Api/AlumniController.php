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
        $query = Alumni::query();

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

        // Calculate total counts
        $total_absorption_count = $absorption_counts->sum('count');
        $total_company_country_count = $company_country_counts->sum('count');
        $total_income_range_count = $income_range_counts->sum('count');
        $total_gender_count = $gender_counts->sum('count');

        // Prepare the data with totals
        $data = [
            'absorption_count' => $absorption_counts,
            'total_absorption_count' => $total_absorption_count,
            'company_country_count' => $company_country_counts,
            'total_company_country_count' => $total_company_country_count,
            'income_range_count' => $income_range_counts,
            'total_income_range_count' => $total_income_range_count,
            'gender_count' => $gender_counts,
            'total_gender_count' => $total_gender_count

        ];

        // Return a JSON response
        return response()->json($data);
    }

}
