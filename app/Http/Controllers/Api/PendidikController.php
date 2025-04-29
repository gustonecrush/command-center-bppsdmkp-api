<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pendidik;
use Illuminate\Http\Request;

class PendidikController extends Controller
{
    public function index(Request $request)
    {
        // Get the satdik_id from the query parameters
        $satdik_id = $request->query('satdik_id');

        // Define the columns to select (exclude created_at and updated_at)
        $columns = [
            'id',
            'nip',
            'nuptk',
            'nidn',
            'address',
            'avatar',
            'nrg',
            'name',
            'title',
            'end_title',
            'jabatan_id',
            'jabatan',
            'golongan_id',
            'golongan',
            'program_studi',
            'tmt_mengajar',
            'tmt_menjabat',
            'bidang_keahlian_id',
            'bidang_keahlian',
            'kompetensi',
            'kategori_pegawai_id',
            'sertifikasi',
            'status_sertifikasi',
            'bidang_studi_sertifikasi',
            'no_sertifikasi',
            'tgl_sertifikasi',
            'satdik_id',
            'education_id',
            'tgl_lahir',
            'gender',
            'program_studi_id',
            'aktif',
            'note',
            'status_pegawai'
        ];

        // Fetch all schools or filter by satdik_id if provided
        if ($satdik_id) {
            // Filter by satdik_id and select specific columns
            $pendidiks = Pendidik::where('satdik_id', $satdik_id)->select($columns)->get();
        } else {
            // Fetch all records with selected columns if no filter is provided
            $pendidiks = Pendidik::select($columns)->get();
        }

        // Return a JSON response
        return response()->json($pendidiks);
    }


    public function summary(Request $request)
    {
        // Get the satdik_id from the query parameters
        $satdik_id = $request->query('satdik_id');

        // Base query for the Pendidik model
        $query = Pendidik::query();

        // Apply the satdik_id filter if provided
        if ($satdik_id) {
            $query->where('satdik_id', $satdik_id);
        }

        // Get counts for each column group: golongan, program_studi, jabatan, and status_sertifikasi
        $golongan_counts = $query->clone()
            ->select('golongan')
            ->groupBy('golongan')
            ->selectRaw('golongan, COUNT(*) as count')
            ->get();

        $program_studi_counts = $query->clone()
            ->select('program_studi')
            ->groupBy('program_studi')
            ->selectRaw('program_studi, COUNT(*) as count')
            ->get();

        $jabatan_counts = $query->clone()
            ->select('jabatan')
            ->groupBy('jabatan')
            ->selectRaw('jabatan, COUNT(*) as count')
            ->get();

        // Add counts for status_sertifikasi
        $status_sertifikasi_counts = $query->clone()
            ->select('status_sertifikasi')
            ->groupBy('status_sertifikasi')
            ->selectRaw('status_sertifikasi, COUNT(*) as count')
            ->get();

        $status_aktif_counts = $query->clone()
            ->select('aktif')
            ->groupBy('aktif')
            ->selectRaw('aktif, COUNT(*) as count')
            ->get()
            ->map(function ($item) {
                return [
                    'status_aktif' => match ($item->aktif) {
                        'Y' => 'Aktif',
                        'N' => 'Tidak Aktif',
                        default => 'Tidak diketahui',
                    },
                    'count' => $item->count,
                ];
            });


        // Calculate total counts
        $total_golongan_count = $golongan_counts->sum('count');
        $total_program_studi_count = $program_studi_counts->sum('count');
        $total_jabatan_count = $jabatan_counts->sum('count');
        $total_status_sertifikasi_count = $status_sertifikasi_counts->sum('count');

        $total_status_aktif_count = $status_aktif_counts->sum('count');

        // Prepare the data with totals
        $data = [
            'golongan_count' => $golongan_counts,
            'total_golongan_count' => $total_golongan_count,
            'program_studi_count' => $program_studi_counts,
            'total_program_studi_count' => $total_program_studi_count,
            'jabatan_count' => $jabatan_counts,
            'total_jabatan_count' => $total_jabatan_count,
            'status_sertifikasi_count' => $status_sertifikasi_counts,
            'total_status_sertifikasi_count' => $total_status_sertifikasi_count,
            'status_aktif_count' => $status_aktif_counts,
            'total_status_aktif_count' => $total_status_aktif_count,
        ];

        // Return a JSON response
        return response()->json($data);
    }
}
