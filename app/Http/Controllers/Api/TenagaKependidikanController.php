<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenagaKependidikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenagaKependidikanController extends Controller
{
    public function index(Request $request)
    {
        $satdik_id = $request->query('satdik_id');

        $columns = [
            'id',
            'nip',
            'n_ip',
            'nama_lengkap',
            'gelar_depan',
            'gelar_belakang',
            'tempat_lahir',
            'tanggal_lahir',
            'jenis_kelamin',
            'agama',
            'alamat',
            'kelurahan',
            'kecamatan',
            'kabupaten',
            'kabupaten2',
            'provinsi',
            'province_id',
            'city_id',
            'satdik_id',
            'kode_pos',
            'phone',
            'satuan_pendidikan',
            'lokasi_kampus',
            'tmt_menjabat',
            'jabatan',
            'status_jabatan',
            'status_pegawai',
            'aktif',
            'golongan',
            'pendidikan_terakhir',
            'perguruan_tinggi_terakhir',
            'tahun_lulus_terakhir',
        ];

        $query = TenagaKependidikan::select($columns);

        if ($satdik_id) {
            $query->where('satdik_id', $satdik_id);
        }

        $results = $query->get();

        return response()->json($results);
    }

    public function summary(Request $request)
    {
        $satdik_id = $request->query('satdik_id');
        $tingkatPendidikan = $request->query('tingkatPendidikan');

        $query = TenagaKependidikan::query();

        if ($satdik_id) {
            $query->where('satdik_id', $satdik_id);
        }

        // Filter by tingkatPendidikan
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

        $golongan_counts = (clone $query)->selectRaw('golongan, COUNT(*) as count')->groupBy('golongan')->orderByDesc('count')->get();

        $jabatan_counts = (clone $query)->selectRaw('jabatan, COUNT(*) as count')->groupBy('jabatan')->orderByDesc('count')->get();

        $gender_counts = (clone $query)->selectRaw('jenis_kelamin as gender, COUNT(*) as count')->groupBy->orderByDesc('count')('jenis_kelamin')->get();

        $status_jabatan_counts = (clone $query)->selectRaw('status_jabatan, COUNT(*) as count')->groupBy('status_jabatan')->orderByDesc('count')->get();

        $status_aktif_counts = (clone $query)->selectRaw('aktif, COUNT(*) as count')->groupBy('aktif')->orderByDesc('count')->get()
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

        $nama_satdik_count = TenagaKependidikan::join('satuan_pendidikan as sp', 'tenaga_kependidikans.satdik_id', '=', 'sp.RowID')
            ->when($tingkatPendidikan && $tingkatPendidikan !== 'All', function ($q) use ($tingkatPendidikan) {
                if ($tingkatPendidikan === 'SUPM') {
                    $q->where('sp.nama', 'LIKE', '%Sekolah%');
                } elseif ($tingkatPendidikan === 'Politeknik') {
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

        return response()->json([
            'golongan_count' => $golongan_counts,
            'total_golongan_count' => $golongan_counts->sum('count'),
            'jabatan_count' => $jabatan_counts,
            'total_jabatan_count' => $jabatan_counts->sum('count'),
            'status_jabatan_count' => $status_jabatan_counts,
            'gender_count' => $gender_counts,
            'status_aktif_count' => $status_aktif_counts,
            'total_status_aktif_count' => $status_aktif_counts->sum('count'),
            'nama_satdik_count' => $nama_satdik_count,
        ]);
    }
}
