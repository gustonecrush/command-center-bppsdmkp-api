<?php

namespace App\Http\Controllers\Api;

use App\Models\TblPbj;
use App\Models\TblRealisasiBelanja;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ManagerialController extends Controller
{
    // public function rekapPerSatker(Request $request)
    // {
    //     $tahun = $request->input('tahun', now()->year);
    //     $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');

    //     // Pre-aggregate DIPA to reduce row count and avoid duplication
    //     $subqueryPagu = DB::table('tbl_dipa_belanja')
    //         ->select('kdsatker', DB::raw('SUM(amount) as pagu'))
    //         ->groupBy('kdsatker');

    //     // Main query
    //     $query = DB::table('tbl_realisasi_belanja as realisasi')
    //         ->leftJoinSub($subqueryPagu, 'dipa', 'realisasi.kdsatker', '=', 'dipa.kdsatker')
    //         ->select(
    //             'realisasi.kdsatker',
    //             'realisasi.nama_satker',
    //             DB::raw('COALESCE(dipa.pagu, 0) as pagu'),
    //             DB::raw('SUM(realisasi.amount) as realisasi'),
    //             DB::raw("SUM(CASE WHEN realisasi.tanggal_omspan <= '{$tanggal}' THEN realisasi.amount ELSE 0 END) as realisasi_sampai_tanggal"),
    //             DB::raw("ROUND(
    //             CASE 
    //                 WHEN COALESCE(dipa.pagu, 0) > 0 
    //                 THEN (SUM(CASE WHEN realisasi.tanggal_omspan <= '{$tanggal}' THEN realisasi.amount ELSE 0 END) / dipa.pagu) * 100
    //                 ELSE 0 
    //             END, 2
    //         ) as persen_realisasi")
    //         )
    //         ->whereYear('realisasi.tanggal_omspan', $tahun)
    //         ->groupBy('realisasi.kdsatker', 'realisasi.nama_satker', 'dipa.pagu')
    //         ->orderByRaw("
    //         CASE
    //             WHEN LOWER(nama_satker) LIKE '%sekretariat%' THEN 1
    //             WHEN LOWER(nama_satker) LIKE '%pusat%' THEN 2
    //             WHEN LOWER(nama_satker) LIKE '%politeknik%' THEN 3
    //             WHEN LOWER(nama_satker) LIKE '%sekolah%' THEN 4
    //             WHEN LOWER(nama_satker) LIKE '%loka%' THEN 5
    //             ELSE 6
    //         END, nama_satker ASC
    //     ")
    //         ->get();

    //     return response()->json($query);
    // }


    public function rekapPerSatker(Request $request)
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');
        $type = $request->input('type'); // optional parameter

        // Pre-aggregate DIPA to reduce row count and avoid duplication
        $subqueryPagu = DB::table('tbl_dipa_belanja')
            ->select('kdsatker', DB::raw('SUM(amount) as pagu'))
            ->whereDate('tanggal_omspan', $tanggal)
            ->groupBy('kdsatker');

        // Pre-aggregate Outstanding + Blokir
        $subqueryOutstanding = DB::table('tbl_outstanding_blokir')
            ->select(
                'kdsatker',
                DB::raw('SUM(outstanding) as total_outstanding'),
                DB::raw('SUM(blokir) as total_blokir')
            )
            ->whereDate('tanggal_omspan', $tanggal)
            ->groupBy('kdsatker');

        // Main query
        $query = DB::table('tbl_realisasi_belanja as realisasi')
            ->leftJoinSub($subqueryPagu, 'dipa', 'realisasi.kdsatker', '=', 'dipa.kdsatker')
            ->leftJoinSub($subqueryOutstanding, 'outstanding', 'realisasi.kdsatker', '=', 'outstanding.kdsatker')
            ->select(
                'realisasi.kdsatker',
                'realisasi.nama_satker',
                DB::raw('COALESCE(dipa.pagu, 0) as pagu'),
                DB::raw('SUM(realisasi.amount) as realisasi'),
                DB::raw("SUM(CASE WHEN realisasi.tanggal_omspan ='{$tanggal}' THEN realisasi.amount ELSE 0 END) as realisasi_sampai_tanggal"),
                DB::raw("ROUND(
                    CASE 
                        WHEN COALESCE(dipa.pagu, 0) > 0 
                        THEN (SUM(CASE WHEN realisasi.tanggal_omspan ='{$tanggal}' THEN realisasi.amount ELSE 0 END) / dipa.pagu) * 100
                        ELSE 0 
                    END, 2
                ) as persen_realisasi"),
                DB::raw('COALESCE(outstanding.total_outstanding, 0) as outstanding'),
                DB::raw('COALESCE(outstanding.total_blokir, 0) as blokir')
            )
            ->whereYear('realisasi.tanggal_omspan', $tahun);

        // Optional filter based on type
        if ($type === 'Pendidikan') {
            $query->where(function ($q) {
                $q->where('realisasi.nama_satker', 'like', '%Pendidikan%')
                    ->orWhere('realisasi.nama_satker', 'like', '%Politeknik%')
                    ->orWhere('realisasi.nama_satker', 'like', '%Akademi%')
                    ->orWhere('realisasi.nama_satker', 'like', '%Sekolah%');
            });
        } elseif ($type === 'Pelatihan') {
            $query->where('realisasi.nama_satker', 'like', '%Pelatihan%');
        }

        // Grouping and ordering
        $query->groupBy(
            'realisasi.kdsatker',
            'realisasi.nama_satker',
            'dipa.pagu',
            'outstanding.total_outstanding',
            'outstanding.total_blokir'
        )
            ->orderByRaw("
                CASE
                    WHEN LOWER(nama_satker) LIKE '%sekretariat%' THEN 1
                    WHEN LOWER(nama_satker) LIKE '%pusat%' THEN 2
                    WHEN LOWER(nama_satker) LIKE '%politeknik%' THEN 3
                    WHEN LOWER(nama_satker) LIKE '%sekolah%' THEN 4
                    WHEN LOWER(nama_satker) LIKE '%loka%' THEN 5
                    ELSE 6
                END, nama_satker ASC
            ");

        $result = $query->get();

        return response()->json($result);
    }


    public function rekapPerSatkerPendapatan(Request $request)
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');
        $type = $request->input('type');

        // Pre-aggregate DIPA Pendapatan
        $subqueryPagu = DB::table('tbl_dipa_pendapatan')
            ->select('kdsatker', DB::raw('SUM(amount) as pagu'))
            ->groupBy('kdsatker');

        // Main query
        $query = DB::table('tbl_realisasi_pendapatan as realisasi')
            ->leftJoinSub($subqueryPagu, 'dipa', 'realisasi.kdsatker', '=', 'dipa.kdsatker')
            ->select(
                'realisasi.kdsatker',
                'realisasi.nama_satker',
                DB::raw('COALESCE(dipa.pagu, 0) as pagu'),
                DB::raw('SUM(realisasi.amount) as realisasi'),
                DB::raw("SUM(CASE WHEN realisasi.tanggal_omspan <= '{$tanggal}' THEN realisasi.amount ELSE 0 END) as realisasi_sampai_tanggal"),
                DB::raw("ROUND(
                CASE 
                    WHEN COALESCE(dipa.pagu, 0) > 0 
                    THEN (SUM(CASE WHEN realisasi.tanggal_omspan <= '{$tanggal}' THEN realisasi.amount ELSE 0 END) / dipa.pagu) * 100
                    ELSE 0 
                END, 2
            ) as persen_realisasi")
            )
            ->whereYear('realisasi.tanggal_omspan', $tahun);

        // Add optional filtering by type
        if ($type === 'Pendidikan') {
            $query->where(function ($q) {
                $q->where('realisasi.nama_satker', 'like', '%Pendidikan%')
                    ->orWhere('realisasi.nama_satker', 'like', '%Politeknik%')
                    ->orWhere('realisasi.nama_satker', 'like', '%Akademi%')
                    ->orWhere('realisasi.nama_satker', 'like', '%Sekolah%');
            });
        } elseif ($type === 'Pelatihan') {
            $query->where('realisasi.nama_satker', 'like', '%Pelatihan%');
        }

        $query = $query
            ->groupBy('realisasi.kdsatker', 'realisasi.nama_satker', 'dipa.pagu')
            ->orderByRaw("
            CASE
                WHEN LOWER(nama_satker) LIKE '%sekretariat%' THEN 1
                WHEN LOWER(nama_satker) LIKE '%pusat%' THEN 2
                WHEN LOWER(nama_satker) LIKE '%politeknik%' THEN 3
                WHEN LOWER(nama_satker) LIKE '%sekolah%' THEN 4
                WHEN LOWER(nama_satker) LIKE '%loka%' THEN 5
                ELSE 6
            END, nama_satker ASC
        ")
            ->get();

        return response()->json($query);
    }





    public function getRealisasiDanSisa(Request $request): JsonResponse
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');

        // Total pagu from DIPA
        $totalPagu = DB::table('tbl_dipa_belanja')
            ->whereDate('tanggal_omspan', $tanggal)
            ->sum('amount');

        // Total realisasi until the given date and year
        $totalRealisasi = DB::table('tbl_realisasi_belanja')
            ->whereYear('tanggal_omspan', $tahun)
            ->whereDate('tanggal_omspan', $tanggal)
            ->sum('amount');

        $sisa = $totalPagu - $totalRealisasi;

        return response()->json([
            'tahun' => (int) $tahun,
            'tanggal' => $tanggal,
            'pagu' => $totalPagu,
            'realisasi' => $totalRealisasi,
            'sisa' => $sisa,
        ]);
    }

    public function getRincianRealisasiAnggaran(Request $request): JsonResponse
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');

        // Total pagu from DIPA
        $totalPagu = DB::table('tbl_dipa_belanja')->whereDate('tanggal_omspan', $tanggal)->sum('amount');

        // Total realisasi until the given date and year
        $totalRealisasi = DB::table('tbl_realisasi_belanja')
            ->whereYear('tanggal_omspan', $tahun)
            ->whereDate('tanggal_omspan', $tanggal)
            ->sum('amount');

        $sisa = $totalPagu - $totalRealisasi;

        $realisasiFiltered = DB::table('tbl_realisasi_belanja')
            ->whereYear('tanggal_omspan', $tahun)
            ->whereDate('tanggal_omspan', $tanggal);

        $akunGroups = DB::table(DB::raw("({$realisasiFiltered->toSql()}) as realisasi"))
            ->mergeBindings($realisasiFiltered) // Required to bind params
            ->join('tbl_dipa_belanja as dipa', function ($join) {
                $join->on('realisasi.kdsatker', '=', 'dipa.kdsatker')
                    ->on('realisasi.akun', '=', 'dipa.akun');
            })
            ->select(
                DB::raw("LEFT(realisasi.akun, 2) as kode_akun"),
                DB::raw("SUM(dipa.amount) as pagu"),
                DB::raw("SUM(realisasi.amount) as realisasi")
            )
            ->groupBy(DB::raw("LEFT(realisasi.akun, 2)"))
            ->get();


        // Map akun prefix to category names
        $akunLabels = [
            '51' => 'Belanja Pegawai',
            '52' => 'Belanja Barang dan Jasa',
            '53' => 'Belanja Modal',
        ];

        $akun = [];

        foreach ($akunGroups as $group) {
            $kode = $group->kode_akun;
            $pagu = (float) $group->pagu;
            $realisasi = (float) $group->realisasi;
            $persentase = $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;

            $persentaseSisa = $pagu > 0 ? round((($pagu - $realisasi) / $pagu) * 100, 2) : 0;

            $akun[] = [
                'name' => $akunLabels[$kode] ?? 'Lainnya',
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'sisa' => $pagu - $realisasi,
                'persentase' => $persentase,
                'persentase_sisa' => $persentaseSisa,
            ];
        }


        return response()->json([
            'tahun' => (int) $tahun,
            'tanggal' => $tanggal,
            'pagu' => $totalPagu,
            'realisasi' => $totalRealisasi,
            'sisa' => ($totalPagu - $totalRealisasi),
            'persentase' => $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 2) : 0,
            'persentase_sisa' => $totalPagu > 0 ? round((($totalPagu - $totalRealisasi) / $totalPagu) * 100, 2) : 0,
            'akun' => $akun
        ]);
    }


    public function getRealisasiDanSisaPendapatan(Request $request): JsonResponse
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');

        // Total pagu from DIPA Pendapatan
        $totalPagu = DB::table('tbl_dipa_pendapatan')
            ->sum('amount');

        // Total realisasi until the given date and year from Realisasi Pendapatan
        $totalRealisasi = DB::table('tbl_realisasi_pendapatan')
            ->whereYear('tanggal_omspan', $tahun)
            ->whereDate('tanggal_omspan', '=', $tanggal)
            ->sum('amount');

        // Calculate remaining amount (sisa)
        $sisa = $totalPagu - $totalRealisasi;

        // Calculate the percentage (presentasi)
        $presentasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 2) : 0;

        return response()->json([
            'tahun' => (int) $tahun,
            'tanggal' => $tanggal,
            'pagu' => $totalPagu,
            'realisasi' => $totalRealisasi,
            'sisa' => $sisa,
            'persentasi' => $presentasi,
        ]);
    }



    public function getRealisasiGrouped(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $tanggal = $request->input('tanggal', now()->toDateString());



        $result = Cache::remember("realisasi_grouped:{$tahun}:{$tanggal}", 300, function () use ($tanggal) {
            $query = "
        SELECT 
            dipa.kegiatan,
            dipa.kegiatan_name,
            dipa.output,
            dipa.output_name,
            SUM(dipa.amount) AS pagu,
            SUM(COALESCE(r.amount, 0)) AS realisasi
        FROM (
        SELECT * FROM tbl_dipa_belanja
        WHERE tanggal_omspan = ?
    ) dipa
        LEFT JOIN (
            SELECT * FROM tbl_realisasi_belanja 
            WHERE tanggal_omspan = ?
        ) r ON dipa.kdsatker = r.kdsatker 
            AND dipa.kegiatan = r.kegiatan
            AND dipa.output = r.output
        GROUP BY dipa.kegiatan, dipa.kegiatan_name, dipa.output, dipa.output_name
    ";

            $data = DB::select($query, [$tanggal, $tanggal]);

            $grouped = [];

            foreach ($data as $item) {
                $kegiatanKey = $item->kegiatan;
                $outputKey = $item->output;

                // Dummy calculation for outstanding and blokir
                // Replace with your real calculation if needed
                $outstandingAmount = 0; // <= UPDATE this logic if needed
                $blokirAmount = 0;       // <= UPDATE this logic if needed

                if (!isset($grouped[$kegiatanKey])) {
                    $grouped[$kegiatanKey] = [
                        'kegiatan' => $item->kegiatan,
                        'kegiatan_name' => $item->kegiatan_name,
                        'pagu' => 0,
                        'realisasi' => 0,
                        'outstanding' => 0,
                        'blokir' => 0,
                        'percentage' => 0,
                        'items' => [],
                    ];
                }

                // Add to kegiatan totals
                $grouped[$kegiatanKey]['pagu'] += (float) $item->pagu;
                $grouped[$kegiatanKey]['realisasi'] += (float) $item->realisasi;
                $grouped[$kegiatanKey]['outstanding'] += (float) $outstandingAmount;
                $grouped[$kegiatanKey]['blokir'] += (float) $blokirAmount;

                // Group by output inside kegiatan
                if (!isset($grouped[$kegiatanKey]['items'][$outputKey])) {
                    $grouped[$kegiatanKey]['items'][$outputKey] = [
                        'output' => $item->output,
                        'output_name' => $item->output_name,
                        'pagu' => 0,
                        'realisasi' => 0,
                        'outstanding' => 0,
                        'blokir' => 0,
                        'percentage' => 0,
                    ];
                }

                $grouped[$kegiatanKey]['items'][$outputKey]['pagu'] += (float) $item->pagu;
                $grouped[$kegiatanKey]['items'][$outputKey]['realisasi'] += (float) $item->realisasi;
                $grouped[$kegiatanKey]['items'][$outputKey]['outstanding'] += (float) $outstandingAmount;
                $grouped[$kegiatanKey]['items'][$outputKey]['blokir'] += (float) $blokirAmount;

                // Update output percentage
                $grouped[$kegiatanKey]['items'][$outputKey]['percentage'] = $grouped[$kegiatanKey]['items'][$outputKey]['pagu'] > 0
                    ? round(($grouped[$kegiatanKey]['items'][$outputKey]['realisasi'] / $grouped[$kegiatanKey]['items'][$outputKey]['pagu']) * 100, 2)
                    : 0;
            }

            // Finalize the result
            $result = [];
            foreach ($grouped as $data) {
                // Calculate percentage based on total pagu
                $data['percentage'] = $data['pagu'] > 0
                    ? round((($data['realisasi'] + $data['outstanding'] + $data['blokir']) / $data['pagu']) * 100, 2)
                    : 0;

                // Reset items to indexed array
                $data['items'] = array_values($data['items']);

                $result[] = $data;
            }

            return $result;
        });

        return response()->json($result);
    }


    public function getRealisasiPendapatanPerAkun(Request $request): JsonResponse
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = \Carbon\Carbon::parse($request->input('tanggal', now()->toDateString()))->format('Y-m-d');

        // Pre-aggregate DIPA
        $subqueryPagu = DB::table('tbl_dipa_pendapatan')
            ->select('akun', 'nama_akun', DB::raw('SUM(amount) as pagu'))
            ->groupBy('akun', 'nama_akun');

        // Main query joining with realisasi
        $data = DB::table('tbl_realisasi_pendapatan as realisasi')
            ->rightJoinSub($subqueryPagu, 'dipa', 'realisasi.akun', '=', 'dipa.akun')
            ->select(
                'dipa.akun',
                'dipa.nama_akun',
                'dipa.pagu',
                DB::raw("SUM(CASE WHEN YEAR(realisasi.tanggal_omspan) = $tahun AND realisasi.tanggal_omspan <= '$tanggal' THEN realisasi.amount ELSE 0 END) as realisasi")
            )
            ->groupBy('dipa.akun', 'dipa.nama_akun', 'dipa.pagu')
            ->orderBy('dipa.akun')
            ->get();

        // Format and calculate % in the response
        $result = [];
        foreach ($data as $index => $item) {
            $realisasi = $item->realisasi ?? 0;
            $presentasi = $item->pagu > 0 ? round(($realisasi / $item->pagu) * 100, 2) : 0;

            $result[] = [
                'no' => $index + 1,
                'akun' => $item->akun . ' - ' . $item->nama_akun,
                'pagu' => (float) $item->pagu,
                'realisasi' => (float) $realisasi,
                'persentasi' => $presentasi
            ];
        }

        return response()->json($result);
    }






    public function summary(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $tahun = $request->input('tahun', now()->year);

        $realisasiBelanja = DB::table('tbl_realisasi_belanja')
            ->select(
                'kdsatker',
                'nama_satker',
                DB::raw('SUM(amount) as realisasi'),
                DB::raw('MAX(tanggal_omspan) as tanggal_terakhir')
            )
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', '<=', $tanggal)
            ->groupBy('kdsatker', 'nama_satker')
            ->get();

        // Add similar logic for pendapatan if needed

        return response()->json([
            'realisasi_belanja' => $realisasiBelanja
        ]);
    }

    public function getRealisasiPendapatanPerSatkerPerAkun(Request $request)
    {
        // 1. Get total pagu & realisasi per kdsatker
        $totalData = DB::table('tbl_dipa_pendapatan as d')
            ->leftJoin('tbl_realisasi_pendapatan as r', function ($join) {
                $join->on('d.kdsatker', '=', 'r.kdsatker')
                    ->on('d.akun', '=', 'r.akun');
            })
            ->select(
                'd.kdsatker',
                'd.nama_satker',
                DB::raw('SUM(d.amount) as pagu'),
                DB::raw('IFNULL(SUM(r.amount), 0) as realisasi')
            )
            ->groupBy('d.kdsatker', 'd.nama_satker')
            ->get();

        // 2. Get detail per akun per kdsatker
        $details = DB::table('tbl_dipa_pendapatan as d')
            ->leftJoin('tbl_realisasi_pendapatan as r', function ($join) {
                $join->on('d.kdsatker', '=', 'r.kdsatker')
                    ->on('d.akun', '=', 'r.akun');
            })
            ->select(
                'd.kdsatker',
                'd.akun',
                'd.nama_akun',
                DB::raw('SUM(d.amount) as pagu'),
                DB::raw('IFNULL(SUM(r.amount), 0) as realisasi')
            )
            ->groupBy('d.kdsatker', 'd.akun', 'd.nama_akun')
            ->get();

        // 3. Format response
        $response = $totalData->map(function ($row) use ($details) {
            $filteredDetails = $details->where('kdsatker', $row->kdsatker)->map(function ($d) {
                $percentage = $d->pagu > 0 ? round(($d->realisasi / $d->pagu) * 100, 2) : 0;

                return [
                    'output' => $d->akun . ' - ' . $d->nama_akun,
                    'pagu' => (float) $d->pagu,
                    'realisasi' => (float) $d->realisasi,
                    'percentage' => $percentage
                ];
            });

            $percentage = $row->pagu > 0 ? round(($row->realisasi / $row->pagu) * 100, 2) : 0;

            return [
                'nama_satker' => $row->nama_satker,
                'pagu' => (float) $row->pagu,
                'realisasi' => (float) $row->realisasi,
                'percentage' => $percentage,
                'details' => $filteredDetails->values()
            ];
        });

        // 4. Custom sorting by keyword in nama_satker
        $sorted = $response->sortBy(function ($item) {
            $name = strtolower($item['nama_satker']);
            if (str_contains($name, 'sekretariat')) return 1;
            if (str_contains($name, 'pusat')) return 2;
            if (str_contains($name, 'politeknik')) return 3;
            if (str_contains($name, 'balai')) return 4;
            if (str_contains($name, 'sekolah')) return 5;
            return 6;
        })->values();

        return response()->json($sorted);
    }

    public function getRealisasiBelanjaPerDay(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        if (!$year || !$month) {
            return response()->json(['error' => 'month and year are required'], 400);
        }

        $results = DB::table('tbl_realisasi_belanja')
            ->selectRaw('
                DATE(tanggal_omspan) as date,
                LEFT(akun, 2) as kode_akun,
                SUM(amount) as realisasi_amount
            ')
            ->whereYear('tanggal_omspan', $year)
            ->whereMonth('tanggal_omspan', $month)
            ->groupBy(DB::raw('DATE(tanggal_omspan), LEFT(akun, 2)'))
            ->orderBy('date')
            ->get();

        // Map results into format grouped by date with types
        $grouped = [];

        $akunLabels = [
            '51' => 'Belanja Pegawai',
            '52' => 'Belanja Barang dan Jasa',
            '53' => 'Belanja Modal',
        ];

        foreach ($results as $row) {
            $date = $row->date;
            $type = $akunLabels[$row->kode_akun] ?? 'Lainnya';

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'Belanja Pegawai' => 0,
                    'Belanja Barang dan Jasa' => 0,
                    'Belanja Modal' => 0,
                    'Lainnya' => 0,
                ];
            }

            $grouped[$date][$type] += (float) $row->realisasi_amount;
        }

        return response()->json(array_values($grouped));
    }

    public function getRealisasiPendapatanPerDay(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        if (!$year || !$month) {
            return response()->json(['error' => 'month and year are required'], 400);
        }

        $results = DB::table('tbl_realisasi_pendapatan')
            ->selectRaw('nama_satker, SUM(amount) as realisasi_amount')
            ->whereYear('tanggal_omspan', $year)
            ->whereMonth('tanggal_omspan', $month)
            ->groupBy('nama_satker') // Group by 'nama_satker' instead of date
            ->orderByDesc('realisasi_amount')
            ->get();

        return response()->json($results);
    }

    public function getAllDataPbj(Request $request)
    {
        $tanggal = $request->query('tanggal'); // Example: '2025-04-29'
        $tahun = $request->query('tahun');     // Example: '2025'

        $query = TblPbj::query();

        if ($tanggal) {
            $query->whereDate('tgl_kontrak', $tanggal);
        }

        if ($tahun) {
            $query->whereYear('tgl_kontrak', $tahun);
        }

        $data = $query->get();

        return response()->json($data);
    }

    public function getGroupedPbjBySatker(Request $request)
    {
        $tahun = $request->input('tahun');
        $tanggal = $request->input('tanggal');

        $query = DB::table('tbl_pbj')
            ->select(
                'nama_satker',
                DB::raw('SUM(nilai_kontrak) as nilai_kontrak'),
                DB::raw('SUM(nilai_realisasi) as nilai_realisasi'),
                DB::raw('SUM(nilai_sisa) as nilai_outstanding'),
                DB::raw('
                CASE 
                    WHEN SUM(nilai_kontrak) > 0 THEN ROUND((SUM(nilai_realisasi) / SUM(nilai_kontrak)) * 100, 2)
                    ELSE 0
                END as persentasi
            '),
                DB::raw('
            CASE 
                WHEN SUM(nilai_sisa) > 0 THEN ROUND((SUM(nilai_sisa) / SUM(nilai_kontrak)) * 100, 2)
                ELSE 0
            END as persentasi_outstanding
        '),
            )
            ->groupBy('nama_satker');

        if ($tahun) {
            $query->whereYear('tgl_kontrak', $tahun);
        }

        if ($tanggal) {
            $query->whereDate('tgl_kontrak', $tanggal);
        }

        $result = $query->get();

        return response()->json($result);
    }

    public function getPBJGroupedByAkun()
    {
        $akunLabels = [
            '52' => 'Belanja Barang dan Jasa',
            '53' => 'Belanja Modal',
        ];

        $data = DB::table('tbl_pbj')
            ->select(
                DB::raw("SUBSTRING(akun, 1, 2) as akun_prefix"),
                DB::raw("SUM(nilai_kontrak) as nilai_kontrak"),
                DB::raw("SUM(nilai_realisasi) as nilai_realisasi"),
                DB::raw("SUM(nilai_sisa) as nilai_outstanding")
            )
            ->groupBy(DB::raw("SUBSTRING(akun, 1, 2)"))
            ->get()
            ->map(function ($item) use ($akunLabels) {
                $persentasiRealisasi = $item->nilai_kontrak > 0
                    ? ($item->nilai_realisasi / $item->nilai_kontrak) * 100
                    : 0;

                $persentasiOutstanding = $item->nilai_kontrak > 0
                    ? ($item->nilai_outstanding / $item->nilai_kontrak) * 100
                    : 0;

                return [
                    'uraian' => $akunLabels[$item->akun_prefix] ?? 'Lainnya',
                    'nilai_kontrak' => (float) $item->nilai_kontrak,
                    'nilai_realisasi' => (float) $item->nilai_realisasi,
                    'nilai_outstanding' => (float) $item->nilai_outstanding,
                    'persentasi' => round($persentasiRealisasi, 2),
                    'persentasi_outstanding' => round($persentasiOutstanding, 2),
                ];
            });

        return response()->json($data);
    }

    public function getSummaryChartKS()
    {
        $summary = [
            'substansi' => DB::table('tbl_kerjasama')
                ->select('Substansi', DB::raw('COUNT(*) as total'))
                ->groupBy('Substansi')
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn($item) => [$item->Substansi ?? 'Tidak Diisi' => $item->total]),

            'lingkup' => DB::table('tbl_kerjasama')
                ->select('Lingkup', DB::raw('COUNT(*) as total'))
                ->groupBy('Lingkup')
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn($item) => [$item->Lingkup ?? 'Tidak Diisi' => $item->total]),

            'pemrakarsa' => DB::table('tbl_kerjasama')
                ->select('Pemrakarsa', DB::raw('COUNT(*) as total'))
                ->groupBy('Pemrakarsa')
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn($item) => [$item->Pemrakarsa ?? 'Tidak Diisi' => $item->total]),

            'jenis_dokumen' => DB::table('tbl_kerjasama')
                ->select('Jenis_Dokumen', DB::raw('COUNT(*) as total'))
                ->groupBy('Jenis_Dokumen')
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn($item) => [$item->Jenis_Dokumen ?? 'Tidak Diisi' => $item->total]),

            'tingkatan' => DB::table('tbl_kerjasama')
                ->select('Tingkatan', DB::raw('COUNT(*) as total'))
                ->groupBy('Tingkatan')
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn($item) => [$item->Tingkatan ?? 'Tidak Diisi' => $item->total]),
        ];

        return response()->json($summary);
    }

    public function getRincianDataKS()
    {
        $data = DB::table('tbl_kerjasama')->get();
        return response()->json($data);
    }
}
