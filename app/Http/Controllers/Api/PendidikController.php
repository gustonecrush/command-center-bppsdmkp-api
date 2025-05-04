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
        $satdik_id = $request->query('satdik_id');
        $tingkatPendidikan = $request->query('tingkatPendidikan');

        $query = Pendidik::query();

        $query->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
            $q->join('satuan_pendidikan as sp', 'pendidiks.satdik_id', '=', 'sp.RowID');

            if ($tingkatPendidikan === 'SUPM') {
                $q->where('sp.nama', 'LIKE', '%Sekolah%');
            } elseif ($tingkatPendidikan === 'Politeknik') {
                $q->where(function ($q2) {
                    $q2->where('sp.nama', 'LIKE', '%Politeknik%')
                        ->orWhere('sp.nama', 'LIKE', '%Akademi%')->orWhere('sp.nama', 'LIKE', '%Pasca%');
                });
            }
        });

        if ($satdik_id) {
            $query->where('pendidiks.satdik_id', $satdik_id);
        }

        // Copy the filtered base query for each summary
        $golongan_counts = $query->clone()
            ->selectRaw('golongan, COUNT(*) as count')
            ->groupBy('golongan')
            ->orderByDesc('count')
            ->get();

        $program_studi_counts = $query->clone()
            ->selectRaw('program_studi, COUNT(*) as count')
            ->groupBy('program_studi')
            ->orderByDesc('count')
            ->get();

        $jabatan_counts = $query->clone()
            ->selectRaw('jabatan, COUNT(*) as count')
            ->groupBy('jabatan')
            ->orderByDesc('count')
            ->get();
        $gender_counts = $query->clone()
            ->selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->orderByDesc('count')
            ->get();

        $status_sertifikasi_counts = $query->clone()
            ->selectRaw('status_sertifikasi, COUNT(*) as count')
            ->groupBy('status_sertifikasi')
            ->orderByDesc('count')
            ->get();

        $status_aktif_counts = $query->clone()
            ->selectRaw('aktif, COUNT(*) as count')
            ->groupBy('aktif')
            ->orderByDesc('count')
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

        $nama_satdik_count = Pendidik::join('satuan_pendidikan as sp', 'pendidiks.satdik_id', '=', 'sp.RowID')
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


        return response()->json([
            'golongan_count' => $golongan_counts,
            'total_golongan_count' => $golongan_counts->sum('count'),
            'program_studi_count' => $program_studi_counts,
            'total_program_studi_count' => $program_studi_counts->sum('count'),
            'jabatan_count' => $jabatan_counts,
            'total_jabatan_count' => $jabatan_counts->sum('count'),
            'status_sertifikasi_count' => $status_sertifikasi_counts,
            'total_status_sertifikasi_count' => $status_sertifikasi_counts->sum('count'),
            'status_aktif_count' => $status_aktif_counts,
            'gender_count' => $gender_counts,
            'total_status_aktif_count' => $status_aktif_counts->sum('count'),
            'nama_satdik_count' => $nama_satdik_count,
        ]);
    }
}
