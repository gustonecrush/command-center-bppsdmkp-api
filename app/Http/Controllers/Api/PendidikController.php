<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pendidik;
use App\Models\School;
use Illuminate\Http\Request;

class PendidikController extends Controller
{
    public function index(Request $request)
    {
        // Get the satdik_id from the query parameters
        $satdik_id = $request->query('satdik_id');

        // Fetch all schools or filter by satdik_id if provided
        if ($satdik_id) {
            // Filter by satdik_id
            $pendidiks = Pendidik::where('satdik_id', $satdik_id)->get();
        } else {
            // Fetch all records if no filter is provided
            $pendidiks = Pendidik::all();
        }

        // Return a JSON response
        return response()->json($pendidiks);
    }
}
