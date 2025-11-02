<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GapokkanDidampingi;
use App\Models\KelompokDibentuk;
use App\Models\KelompokDisuluh;
use App\Models\KelompokDitingkatkan;
use App\Models\Penyuluh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PenyuluhController extends Controller
{
    public function groupByStatus(Request $request)
    {
        $statusTypes = ['PNS', 'PPPK', 'PPB'];

        return $this->groupByFixedTypes($request, 'status', $statusTypes);
    }

    public function groupByJabatan(Request $request)
    {
        $jabatanTypes = [
            'PP PEMULA',
            'APP TERAMPIL',
            'APP MAHIR',
            'APP PENYELIA',
            'PP PERTAMA',
            'PP MUDA',
            'PP MADYA'
        ];

        return $this->groupByFixedTypes($request, 'jabatan', $jabatanTypes);
    }

    public function groupByPendidikan(Request $request)
    {
        $pendidikanTypes = ['S3', 'S2', 'S1/D4', 'D3', 'SMA'];

        return $this->groupByFixedTypes($request, 'pendidikan', $pendidikanTypes);
    }

    public function groupByKelompokUsia(Request $request)
    {
        $usiaTypes = ['<= 35', '36-50', '>= 51'];

        return $this->groupByFixedTypes($request, 'kelompok_usia', $usiaTypes);
    }

    public function groupByKelamin(Request $request)
    {
        $kelaminTypes = ['L', 'P'];

        return $this->groupByFixedTypes($request, 'kelamin', $kelaminTypes);
    }

    public function countBySatminkal()
    {
        $data = Penyuluh::select('satminkal', DB::raw('count(*) as total'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal')
            ->get();

        return response()->json($data);
    }

    public function getGroupedBySatminkalDetails(Request $request)
    {
        $keyword = $request->query('satminkal');

        if (!$keyword) {
            return response()->json(['error' => 'Missing satminkal parameter'], 400);
        }

        $baseQuery = Penyuluh::where('satminkal', $keyword);

        $total = $baseQuery->count();

        // Fixed category definitions
        $statusTypes = ['PNS', 'PPPK', 'PPB'];
        $jabatanTypes = [
            'PP PEMULA',
            'APP TERAMPIL',
            'APP MAHIR',
            'APP PENYELIA',
            'PP PERTAMA',
            'PP MUDA',
            'PP MADYA'
        ];
        $pendidikanTypes = ['S3', 'S2', 'S1/D4', 'D3', 'SMA'];
        $usiaTypes = ['<= 35', '36-50', '>= 51'];
        $kelaminTypes = ['L', 'P'];

        $groupedDetails = function ($column, $types) use ($baseQuery) {
            $data = (clone $baseQuery)
                ->select('provinsi', $column, DB::raw('count(*) as total'))
                ->whereNotNull('provinsi')
                ->groupBy('provinsi', $column)
                ->get();

            return $data->groupBy('provinsi')->map(function ($items, $provinsi) use ($types, $column) {
                $result = collect($types)->map(function ($type) use ($items, $column) {
                    $match = $items->firstWhere($column, $type);
                    return [
                        $column => $type,
                        'total' => $match ? $match->total : 0
                    ];
                });

                return [
                    'provinsi' => $provinsi,
                    $column => $result
                ];
            })->values();
        };

        return response()->json([
            'satminkal' => $keyword,
            'total' => $total,
            'details' => [
                'status' => $groupedDetails('status', $statusTypes),
                'jabatan' => $groupedDetails('jabatan', $jabatanTypes),
                'pendidikan' => $groupedDetails('pendidikan', $pendidikanTypes),
                'kelompok_usia' => $groupedDetails('kelompok_usia', $usiaTypes),
                'kelamin' => $groupedDetails('kelamin', $kelaminTypes),
            ]
        ]);
    }

    public function getDetailPenyuluh($no)
    {
        $sql = "
    SELECT 
        p.*,
        k.latitude,
        k.longitude
    FROM penyuluh p
    LEFT JOIN mtr_kabupatens k 
        ON k.kabupaten LIKE CONCAT('%', p.kab_kota, '%')
    WHERE p.no = ?
    LIMIT 1
";


        $data = DB::selectOne($sql, [$no]);

        if (!$data) {
            return response()->json(['message' => 'Penyuluh not found'], 404);
        }

        return response()->json($data);
    }


    /**
     * Helper untuk summary tanpa group provinsi (total nasional).
     */
    private function groupByFixedTypesRaw($query, string $column, array $fixedTypes)
    {
        $data = $query->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->get();

        return collect($fixedTypes)->map(function ($type) use ($data, $column) {
            $match = $data->firstWhere($column, $type);
            return [
                $column => $type,
                'total' => $match ? $match->total : 0
            ];
        });
    }

    public function getValueBox(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $provinsiCode = $request->query('provinsi'); // "1029"
        $kabupaten = $request->query('kabupaten');

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        $triwulanFilter = null;
        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulanFilter = "{$twMapping[$tw]} Tahun {$tahun}";
        }

        // Get provinsi name from code
        $provinsiName = null;
        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();
            $provinsiName = $provinsi ? $provinsi->provinsi : null;
        }

        // Base query
        $baseQuery = Penyuluh::query();

        // Apply filters
        if ($triwulanFilter) {
            $baseQuery->where('triwulan', $triwulanFilter);
        }
        if ($provinsiName) {
            $baseQuery->where('provinsi', $provinsiName);
        }
        if ($kabupaten) {
            $baseQuery->where('kab_kota', 'LIKE', "%{$kabupaten}%");
        }

        // Get counts
        $total_penyuluh = (clone $baseQuery)->count();
        $total_pns   = (clone $baseQuery)->where('status', 'PNS')->count();
        $total_pppk  = (clone $baseQuery)->where('status', 'PPPK')->count();
        $total_ppb   = (clone $baseQuery)->where('status', 'PPB')->count();

        return response()->json([
            'total_penyuluh' => $total_penyuluh,
            'total_pns'      => $total_pns,
            'total_pppk'     => $total_pppk,
            'total_ppb'      => $total_ppb,
        ]);
    }

    public function getSummaryPenyuluhan(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $provinsiCode = $request->query('provinsi');
        $kabupaten = $request->query('kabupaten');

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        $triwulanFilter = null;
        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulanFilter = "{$twMapping[$tw]} Tahun {$tahun}";
        }

        // Get provinsi name from code
        $provinsiName = null;
        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();
            $provinsiName = $provinsi ? $provinsi->provinsi : null;
        }

        // Base queries
        $baseQueryPenyuluh = Penyuluh::query();
        $baseQueryKelompokDisuluh = KelompokDisuluh::query();
        $baseQueryKelompokDitingkatkan = KelompokDitingkatkan::query();
        $baseQueryKelompokDibentuk = KelompokDibentuk::query();
        $baseQueryGapokkan = GapokkanDidampingi::query();

        // Apply filters
        $queries = [
            $baseQueryPenyuluh,
            $baseQueryKelompokDisuluh,
            $baseQueryKelompokDitingkatkan,
            $baseQueryKelompokDibentuk,
            $baseQueryGapokkan
        ];

        foreach ($queries as $query) {
            if ($triwulanFilter) {
                $query->where('triwulan', $triwulanFilter);
            }
            if ($provinsiName) {
                $query->where('provinsi', $provinsiName);
            }
            if ($kabupaten) {
                $query->where('kab_kota', 'LIKE', "%{$kabupaten}%");
            }
        }

        $summary = [
            'total_penyuluh' => $baseQueryPenyuluh->count(),
            'total_kelompok_disuluh' => $baseQueryKelompokDisuluh->count(),
            'total_kelompok_ditingkatkan' => $baseQueryKelompokDitingkatkan->count(),
            'total_kelompok_dibentuk' => $baseQueryKelompokDibentuk->count(),
            'total_gapokkan_didampingi' => $baseQueryGapokkan->count(),
        ];

        return response()->json($summary);
    }

    public function resultSummary(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $provinsiCode = $request->query('provinsi');
        $kabupaten = $request->query('kabupaten');

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        $triwulanFilter = null;
        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulanFilter = "{$twMapping[$tw]} Tahun {$tahun}";
        }

        // Get provinsi name from code
        $provinsiName = null;
        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();
            $provinsiName = $provinsi ? $provinsi->provinsi : null;
        }

        $baseQuery = Penyuluh::query();

        // Apply filters
        if ($triwulanFilter) {
            $baseQuery->where('triwulan', $triwulanFilter);
        }
        if ($provinsiName) {
            $baseQuery->where('provinsi', $provinsiName);
        }
        if ($kabupaten) {
            $baseQuery->where('kab_kota', 'LIKE', "%{$kabupaten}%");
        }

        // Get summaries
        $statusSummary = $this->groupByFixedTypesRaw(clone $baseQuery, 'status', ['PNS', 'PPPK', 'PPB']);
        $jabatanSummary = $this->groupByFixedTypesRaw(clone $baseQuery, 'jabatan', [
            'PP PEMULA',
            'APP TERAMPIL',
            'APP MAHIR',
            'APP PENYELIA',
            'PP PERTAMA',
            'PP MUDA',
            'PP MADYA'
        ]);
        $pendidikanSummary = $this->groupByFixedTypesRaw(clone $baseQuery, 'pendidikan', ['S3', 'S2', 'S1/D4', 'D3', 'SMA']);
        $usiaSummary = $this->groupByFixedTypesRaw(clone $baseQuery, 'kelompok_usia', ['<= 35', '36-50', '>= 51']);
        $kelaminSummary = $this->groupByFixedTypesRaw(clone $baseQuery, 'kelamin', ['L', 'P']);
        $satminkalSummary = $baseQuery->clone()
            ->select('satminkal', DB::raw('count(*) as total'))
            ->whereNotNull('satminkal')
            ->groupBy('satminkal')
            ->get();

        return response()->json([
            'status' => $statusSummary,
            'jabatan' => $jabatanSummary,
            'pendidikan' => $pendidikanSummary,
            'usia' => $usiaSummary,
            'kelamin' => $kelaminSummary,
            'satminkal' => $satminkalSummary,
        ]);
    }

    public function getLocationPenyuluh(Request $request)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $provinsiCode = $request->query('provinsi');
        $kabupaten = $request->query('kabupaten');
        $name = $request->input('name');

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        $triwulanFilter = null;
        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulanFilter = "{$twMapping[$tw]} Tahun {$tahun}";
        }

        // Get provinsi name from code
        $provinsiName = null;
        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();
            $provinsiName = $provinsi ? $provinsi->provinsi : null;
        }


        $sql = "
        SELECT
            p.no,
            p.nama,
            p.status,
            p.satminkal,
            k.latitude,
            k.longitude
        FROM penyuluh p
        LEFT JOIN mtr_kabupatens k
            ON k.kabupaten LIKE CONCAT('%', p.kab_kota, '%')
        WHERE 1=1
    ";

        $bindings = [];

        if ($triwulanFilter) {
            $sql .= " AND p.triwulan = ? ";
            $bindings[] = $triwulanFilter;
        }

        if ($provinsiName) {
            $sql .= " AND p.provinsi = ? ";
            $bindings[] = $provinsiName;
        }

        if ($kabupaten) {
            $sql .= " AND p.kab_kota LIKE ? ";
            $bindings[] = "%{$kabupaten}%";
        }

        if ($name) {
            $sql .= " AND p.nama LIKE ? ";
            $bindings[] = "%{$name}%";
        }

        $data = DB::select($sql, $bindings);

        return response()->json($data);
    }

    private function groupByFixedTypes(Request $request, string $column, array $fixedTypes)
    {
        $tahun = $request->query('tahun');
        $tw = $request->query('tw');
        $provinsiCode = $request->query('provinsi');
        $kabupaten = $request->query('kabupaten');

        $twMapping = [
            'TW I' => 'Triwulan 1',
            'TW II' => 'Triwulan 2',
            'TW III' => 'Triwulan 3',
            'TW IV' => 'Triwulan 4',
        ];

        $triwulanFilter = null;
        if ($tahun && $tw && isset($twMapping[$tw])) {
            $triwulanFilter = "{$twMapping[$tw]} Tahun {$tahun}";
        }

        // Get provinsi name from code
        $provinsiName = null;
        if ($provinsiCode) {
            $provinsi = DB::table('mtr_provinsis')
                ->where('id', $provinsiCode)
                ->first();
            $provinsiName = $provinsi ? $provinsi->provinsi : null;
        }

        $query = Penyuluh::select('provinsi', $column, DB::raw('count(*) as total'))
            ->whereNotNull('provinsi');

        // Apply filters
        if ($triwulanFilter) {
            $query->where('triwulan', $triwulanFilter);
        }
        if ($provinsiName) {
            $query->where('provinsi', $provinsiName);
        }
        if ($kabupaten) {
            $query->where('kab_kota', 'LIKE', "%{$kabupaten}%");
        }

        $data = $query->groupBy('provinsi', $column)
            ->orderBy('provinsi')
            ->get();

        $grouped = $data->groupBy('provinsi')->map(function ($items, $prov) use ($column, $fixedTypes) {
            $result = collect($fixedTypes)->map(function ($type) use ($items, $column) {
                $match = $items->firstWhere($column, $type);
                return [
                    $column => $type,
                    'total' => $match ? $match->total : 0
                ];
            });

            return [
                'provinsi' => $prov,
                $column => $result
            ];
        })->values();

        return response()->json($grouped);
    }
}
