<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\Pendidik;
use App\Models\Pentaru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PentaruController extends Controller
{
    public function index()
    {
        return response()->json(Pentaru::all());
    }

    public function show($no_pendaftaran)
    {
        $pentaru = Pentaru::where('no_pendaftaran', $no_pendaftaran)->first();

        if (!$pentaru) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return response()->json($pentaru);
    }

    public function location(Request $request)
    {
        $tingkatPendidikan = $request->query('tingkatPendidikan');
        $tahun = $request->query('selectedYear');

        $pentaru = DB::table('pentaru as p')
            ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
                if ($tingkatPendidikan === 'Menengah') {
                    $q->where('p.nama_sekolah', 'like', '%SMA%')
                        ->orWhere('p.nama_sekolah', 'like', '%SMK%')
                        ->orWhere('p.nama_sekolah', 'like', '%Sekolah%');
                } elseif ($tingkatPendidikan === 'Tinggi') {
                    $q->where(function ($q2) {
                        $q2->where('p.nama_sekolah', 'like', '%Politeknik%')
                            ->orWhere('p.nama_sekolah', 'like', '%Akademi%')
                            ->orWhere('p.nama_sekolah', 'like', '%Pasca%');
                    });
                }
            })
            ->when($tahun, function ($q) use ($tahun) {
                $q->whereYear('p.tanggal_daftar', $tahun);
            })
            ->leftJoin('mtr_kabupatens as kab', 'p.kab_kota', '=', 'kab.kabupaten')
            ->select([
                'p.no_pendaftaran',
                'p.nama_lengkap',
                'p.no_nik',
                'kab.latitude as lat',
                'kab.longitude as long'
            ])
            ->get();

        return response()->json($pentaru);
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

        // Calculate total counts
        $total_golongan_count = $golongan_counts->sum('count');
        $total_program_studi_count = $program_studi_counts->sum('count');
        $total_jabatan_count = $jabatan_counts->sum('count');
        $total_status_sertifikasi_count = $status_sertifikasi_counts->sum('count');

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
        ];

        // Return a JSON response
        return response()->json($data);
    }

    public function getGrandSummaryPentaru(Request $request)
    {
        $tahun = $request->query('tahun');
        $type = $request->query('type'); // SUPM or POLTEK

        $query = DB::table('pendaftar');

        // Filter by year (based on Tanggal_Data)
        if ($tahun) {
            $query->whereYear('Tanggal_Data', $tahun);
        }



        $total_data = $query->count();

        $total_lolos = (clone $query)->where('Status_Lolos', 2)->count();
        $total_lolos_adm = (clone $query)->where('Status_Lolos', 1)->count();

        $gender_summary = (clone $query)
            ->select('Jenis_Kelamin', DB::raw('count(*) as total'))
            ->groupBy('Jenis_Kelamin')
            ->get();

        $kampus_summary_raw = (clone $query)
            ->select('Kampus_1', DB::raw('count(*) as total'))
            ->groupBy('Kampus_1')
            ->get();

        $kampus_summary = [];

        foreach ($kampus_summary_raw as $kampus) {
            $prodi_summary = (clone $query)
                ->where('Kampus_1', $kampus->Kampus_1)
                ->select('Prodi_1', DB::raw('count(*) as total'))
                ->groupBy('Prodi_1')
                ->get();

            $kampus_summary[] = [
                'Kampus_1' => $kampus->Kampus_1,
                'total' => $kampus->total,
                'prodis' => $prodi_summary,
            ];
        }

        return response()->json([
            'total_data' => $total_data,
            'total_lolos' => $total_lolos,
            'total_lolos_adm' => $total_lolos_adm,
            'gender_summary' => $gender_summary,
            'kampus_summary' => $kampus_summary,
        ]);
    }
}
