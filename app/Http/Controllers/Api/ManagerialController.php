<?php

namespace App\Http\Controllers\Api;

use App\Models\TblPbj;
use App\Models\TblRealisasiBelanja;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TblKerjaSama;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $tanggalInput = $request->input('tanggal');

        if (empty($tanggalInput)) {
            $tanggal = DB::table('tbl_dipa_belanja')->max('tanggal_omspan');
        } else {
            $tanggal = \Carbon\Carbon::parse($tanggalInput)->format('Y-m-d');
        }

        $type = $request->input('type'); // optional parameter

        // Pre-aggregate DIPA to reduce row count and avoid duplication
        $subqueryPagu = DB::table('tbl_dipa_belanja')
            ->select('kdsatker', DB::raw('SUM(amount) as pagu'))
            ->where('tanggal_omspan', $tanggal)
            ->groupBy('kdsatker');

        // Pre-aggregate Outstanding + Blokir
        $subqueryOutstanding = DB::table('tbl_outstanding_blokir')
            ->select(
                'kdsatker',
                DB::raw('SUM(outstanding) as total_outstanding'),
                DB::raw('SUM(blokir) as total_blokir')
            )
            ->where('tanggal_omspan', $tanggal)
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
            ->whereYear('realisasi.tanggal_omspan', $tahun)->where('realisasi.tanggal_omspan', $tanggal);

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
        WHEN nama_satker LIKE '%Sekretariat%' THEN 1
        WHEN nama_satker LIKE '%Pusat%' THEN 2
          WHEN nama_satker LIKE '%Balai Besar%' THEN 3
        WHEN nama_satker LIKE '%Politeknik%' THEN 4
        WHEN nama_satker LIKE '%Akademi%' THEN 5
        WHEN nama_satker LIKE '%Sekolah%' THEN 6
        WHEN nama_satker LIKE '%Pelatihan%' THEN 7
        WHEN nama_satker LIKE '%Penyuluhan%' THEN 8
                WHEN nama_satker LIKE '%Balai Riset%' THEN 9
        WHEN nama_satker LIKE '%Loka%' THEN 10
        ELSE 11
    END ASC
");


        $result = $query->get();

        return response()->json($result);
    }

    public function rekapPerSatkerPendapatan(Request $request)
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = $request->input('tanggal');
        $type = $request->input('type');

        // Pre-aggregate DIPA Pendapatan
        $subqueryPagu = DB::table('tbl_dipa_pendapatan')
            ->select('kdsatker', DB::raw('SUM(amount) as pagu'))
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
            ->groupBy('kdsatker');

        // Main query
        $query = DB::table('tbl_realisasi_pendapatan as realisasi')
            ->leftJoinSub($subqueryPagu, 'dipa', 'realisasi.kdsatker', '=', 'dipa.kdsatker')
            ->select(
                'realisasi.kdsatker',
                'realisasi.nama_satker',
                DB::raw('COALESCE(dipa.pagu, 0) as pagu'),
                DB::raw('SUM(realisasi.amount) as realisasi'),
                DB::raw("SUM(CASE WHEN realisasi.tanggal_omspan = '{$tanggal}' THEN realisasi.amount ELSE 0 END) as realisasi_sampai_tanggal"),
                DB::raw("ROUND(
                CASE 
                    WHEN COALESCE(dipa.pagu, 0) > 0 
                    THEN (SUM(CASE WHEN realisasi.tanggal_omspan = '{$tanggal}' THEN realisasi.amount ELSE 0 END) / dipa.pagu) * 100
                    ELSE 0 
                END, 2
            ) as persen_realisasi")
            )
            ->whereYear('realisasi.tanggal_omspan', $tahun)->where('tanggal_omspan', $tanggal);

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
        WHEN nama_satker LIKE '%Sekretariat%' THEN 1
        WHEN nama_satker LIKE '%Pusat%' THEN 2
          WHEN nama_satker LIKE '%Balai Besar%' THEN 3
        WHEN nama_satker LIKE '%Politeknik%' THEN 4
        WHEN nama_satker LIKE '%Akademi%' THEN 5
        WHEN nama_satker LIKE '%Sekolah%' THEN 6
        WHEN nama_satker LIKE '%Pelatihan%' THEN 7
        WHEN nama_satker LIKE '%Penyuluhan%' THEN 8
                WHEN nama_satker LIKE '%Balai Riset%' THEN 9
        WHEN nama_satker LIKE '%Loka%' THEN 10
        ELSE 11
    END ASC
")
            ->get();

        return response()->json($query);
    }





    public function getRealisasiDanSisa(Request $request): JsonResponse
    {
        $tahun = $request->input('tahun', now()->year);
        $tanggal = $request->input('tanggal');

        // Total pagu from DIPA
        $totalPagu = DB::table('tbl_dipa_belanja')
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
            ->sum('amount');

        // Total realisasi until the given date and year
        $totalRealisasi = DB::table('tbl_realisasi_belanja')
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
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
        $tanggal = $request->input('tanggal');

        // Query for pagu (from tbl_dipa_belanja)
        $diparealisasiFiltered = DB::table('tbl_dipa_belanja')
            ->where('tanggal_omspan', $tanggal)
            ->select(DB::raw("LEFT(akun, 2) as kode_akun"), DB::raw("SUM(amount) as pagu"))
            ->groupBy(DB::raw("LEFT(akun, 2)"))
            ->get();

        // Query for realisasi (from tbl_realisasi_belanja)
        $realisasiFiltered = DB::table('tbl_realisasi_belanja')
            ->where('tanggal_omspan', $tanggal)
            ->select(DB::raw("LEFT(akun, 2) as kode_akun"), DB::raw("SUM(amount) as realisasi"))
            ->groupBy(DB::raw("LEFT(akun, 2)"))
            ->get();

        // Merge pagu and realisasi data manually
        $akunGroups = [];

        foreach ($diparealisasiFiltered as $dipa) {
            $kdAkun = $dipa->kode_akun;
            $pagu = (float) $dipa->pagu;
            $realisasi = 0;

            // Find corresponding realisasi
            foreach ($realisasiFiltered as $realisasiItem) {
                if ($realisasiItem->kode_akun == $kdAkun) {
                    $realisasi = (float) $realisasiItem->realisasi;
                    break;
                }
            }

            // Calculate percentages
            $persentase = $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;
            $persentaseSisa = $pagu > 0 ? round((($pagu - $realisasi) / $pagu) * 100, 2) : 0;

            // Add to result array
            $akunGroups[] = [
                'kode_akun' => $kdAkun,
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'sisa' => $pagu - $realisasi,
                'persentase' => $persentase,
                'persentase_sisa' => $persentaseSisa,
            ];
        }

        // Map akun prefix to category names
        $akunLabels = [
            '51' => 'Belanja Pegawai',
            '52' => 'Belanja Barang dan Jasa',
            '53' => 'Belanja Modal',
        ];

        // Prepare final response data
        $akun = [];

        foreach ($akunGroups as $group) {
            $kode = $group['kode_akun'];
            $akun[] = [
                'name' => $akunLabels[$kode] ?? 'Lainnya',
                'pagu' => $group['pagu'],
                'realisasi' => $group['realisasi'],
                'sisa' => $group['sisa'],
                'persentase' => $group['persentase'],
                'persentase_sisa' => $group['persentase_sisa'],
            ];
        }

        // Calculate totalPagu and totalRealisasi
        $totalPagu = $diparealisasiFiltered->sum('pagu');
        $totalRealisasi = $realisasiFiltered->sum('realisasi');

        // Return the response
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
        $tanggal = $request->input(key: 'tanggal');

        // Total pagu from DIPA Pendapatan
        $totalPagu = DB::table('tbl_dipa_pendapatan')
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
            ->sum('amount');

        // Total realisasi until the given date and year from Realisasi Pendapatan
        $totalRealisasi = DB::table('tbl_realisasi_pendapatan')
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
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
        $tanggal = $request->input('tanggal');

        // Pre-aggregate DIPA
        $subqueryPagu = DB::table('tbl_dipa_pendapatan')
            ->select('akun', 'nama_akun', DB::raw('SUM(amount) as pagu'))
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
            ->groupBy('akun', 'nama_akun');

        // Main query joining with realisasi
        $data = DB::table('tbl_realisasi_pendapatan as realisasi')
            ->rightJoinSub($subqueryPagu, 'dipa', 'realisasi.akun', '=', 'dipa.akun')
            ->select(
                'dipa.akun',
                'dipa.nama_akun',
                'dipa.pagu',
                DB::raw("SUM(CASE WHEN YEAR(realisasi.tanggal_omspan) = $tahun AND realisasi.tanggal_omspan = '$tanggal' THEN realisasi.amount ELSE 0 END) as realisasi")
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
        $tanggal = $request->input('tanggal');
        $tahun = $request->input('tahun', now()->year);

        // Subquery: Filtered DIPA
        $dipaSub = DB::table('tbl_dipa_pendapatan')
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
            ->select('kdsatker', 'akun', 'nama_satker', 'amount');

        // Subquery: Filtered Realisasi
        $realisasiSub = DB::table('tbl_realisasi_pendapatan')
            ->whereYear('tanggal_omspan', $tahun)
            ->where('tanggal_omspan', $tanggal)
            ->select('kdsatker', 'akun', 'amount');

        // Join the filtered subqueries
        $joinQuery = DB::table(DB::raw("({$dipaSub->toSql()}) as d"))
            ->leftJoin(DB::raw("({$realisasiSub->toSql()}) as r"), function ($join) {
                $join->on('d.kdsatker', '=', 'r.kdsatker')
                    ->on('d.akun', '=', 'r.akun');
            })
            ->select(
                'd.kdsatker',
                'd.nama_satker',
                DB::raw('SUM(d.amount) as pagu'),
                DB::raw('IFNULL(SUM(r.amount), 0) as realisasi')
            )
            ->groupBy('d.kdsatker', 'd.nama_satker');

        // Merge bindings from both subqueries
        $joinQuery->mergeBindings($dipaSub)->mergeBindings($realisasiSub);

        // Execute query
        $totalData = $joinQuery->get();



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
            ->whereYear('d.tanggal_omspan', $tahun)
            ->where('d.tanggal_omspan', $tanggal)
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

    // public function getRealisasiPendapatanPerDay(Request $request)
    // {
    //     $year = $request->input('tahun');
    //     $type = $request->input('type');
    //     $tanggalInput = $request->input('tanggal');

    //     // If tanggal is empty string, get the latest tanggal_omspan
    //     if ($tanggalInput === 'latest') {
    //         $tanggal = DB::table('tbl_realisasi_pendapatan')->max('tanggal_omspan');

    //         if (!$tanggal) {
    //             return response()->json(['error' => 'No data available to determine latest date'], 404);
    //         }
    //     } else {
    //         $tanggal = \Carbon\Carbon::parse($tanggalInput ?? now()->toDateString())->format('Y-m-d');
    //     }

    //     if (!$year) {
    //         return response()->json(['error' => 'year and tanggal are required'], 400);
    //     }

    //     $query = DB::table('tbl_realisasi_pendapatan')
    //         ->selectRaw('nama_satker, SUM(amount) as realisasi_amount')
    //         ->whereYear('tanggal_omspan', $year)
    //         ->where('tanggal_omspan', $tanggal);

    //     if ($type) {
    //         $query->where(function ($q) use ($type) {
    //             if ($type === 'pendidikan') {
    //                 $q->where('nama_satker', 'like', '%politeknik%')
    //                     ->orWhere('nama_satker', 'like', '%akademi%')
    //                     ->orWhere('nama_satker', 'like', '%kampus%')
    //                     ->orWhere('nama_satker', 'like', '%sekolah%');
    //             } elseif ($type === 'pelatihan') {
    //                 $q->where('nama_satker', 'like', '%pelatihan%');
    //             } elseif ($type === 'riset') {
    //                 $q->where('nama_satker', 'like', '%riset%');
    //             } elseif ($type === 'penyuluhan') {
    //                 $q->where('nama_satker', 'like', '%penyuluhan%');
    //             }
    //         });
    //     }

    //     $results = $query
    //         ->groupBy('nama_satker')
    //         ->orderByDesc('realisasi_amount')
    //         ->get();

    //     return response()->json($results);
    // }

    public function getRealisasiPendapatanPerDay(Request $request)
    {
        $year = $request->input('tahun');
        $type = $request->input('type');
        $tanggalInput = $request->input('tanggal');

        // If tanggal is empty string or 'latest', get the latest tanggal_omspan
        if ($tanggalInput === 'latest') {
            $tanggal = DB::table('tbl_realisasi_pendapatan')->max('tanggal_omspan');

            if (!$tanggal) {
                return response()->json(['error' => 'No data available to determine latest date'], 404);
            }
        } else {
            $tanggal = \Carbon\Carbon::parse($tanggalInput ?? now()->toDateString())->format('Y-m-d');
        }

        if (!$year) {
            return response()->json(['error' => 'year and tanggal are required'], 400);
        }

        // Pre-aggregate pagu from tbl_dipa_pendapatan
        $subqueryPagu = DB::table('tbl_dipa_pendapatan')
            ->select('kdsatker', DB::raw('SUM(amount) as pagu'))
            ->whereYear('tanggal_omspan', $year)
            ->where('tanggal_omspan', $tanggal)
            ->groupBy('kdsatker');

        $query = DB::table('tbl_realisasi_pendapatan as realisasi')
            ->leftJoinSub($subqueryPagu, 'dipa', 'realisasi.kdsatker', '=', 'dipa.kdsatker')
            ->select(
                'realisasi.nama_satker',
                DB::raw('COALESCE(dipa.pagu, 0) as pagu'),
                DB::raw('SUM(realisasi.amount) as realisasi_amount')
            )
            ->whereYear('realisasi.tanggal_omspan', $year)
            ->where('realisasi.tanggal_omspan', $tanggal);

        if ($type) {
            $query->where(function ($q) use ($type) {
                if ($type === 'pendidikan') {
                    $q->where('realisasi.nama_satker', 'like', '%politeknik%')
                        ->orWhere('realisasi.nama_satker', 'like', '%akademi%')
                        ->orWhere('realisasi.nama_satker', 'like', '%kampus%')
                        ->orWhere('realisasi.nama_satker', 'like', '%sekolah%');
                } elseif ($type === 'pelatihan') {
                    $q->where('realisasi.nama_satker', 'like', '%pelatihan%');
                } elseif ($type === 'riset') {
                    $q->where('realisasi.nama_satker', 'like', '%riset%');
                } elseif ($type === 'penyuluhan') {
                    $q->where('realisasi.nama_satker', 'like', '%penyuluhan%');
                }
            });
        }

        $results = $query
            ->groupBy('realisasi.nama_satker', 'dipa.pagu')
            ->orderByDesc('realisasi_amount')
            ->get();

        return response()->json($results);
    }


    public function getAllDataPbj(Request $request)
    {
        $tanggal = $request->query('tanggal');
        $tahun = $request->query('tahun');

        $query = TblPbj::query();

        if ($tanggal) {
            $query->where('tanggal_omspan', $tanggal);
        }

        if ($tahun) {
            $query->whereYear('tanggal_omspan', $tahun);
        }

        $data = $query->get();

        return response()->json($data);
    }

    // public function getGroupedPbjBySatker(Request $request)
    // {
    //     $tahun = $request->input('tahun');
    //     $tanggal = $request->input('tanggal');

    //     $subqueryOutstanding = DB::table('tbl_outstanding_blokir')
    //         ->select(
    //             'kdsatker',
    //             DB::raw('SUM(outstanding) as total_outstanding'),
    //             DB::raw('SUM(blokir) as total_blokir')
    //         )
    //         ->where('tanggal_omspan', $tanggal)
    //         ->groupBy('kdsatker');

    //     $query = DB::table('tbl_pbj')
    //         ->select(
    //             'nama_satker',
    //             DB::raw('SUM(nilai_kontrak) as nilai_kontrak'),
    //             DB::raw('SUM(nilai_realisasi) as nilai_realisasi'),
    //             DB::raw('SUM(nilai_sisa) as nilai_outstanding'),
    //             DB::raw('
    //             CASE 
    //                 WHEN SUM(nilai_kontrak) > 0 THEN ROUND((SUM(nilai_realisasi) / SUM(nilai_kontrak)) * 100, 2)
    //                 ELSE 0
    //             END as persentasi
    //         '),
    //             DB::raw('
    //             CASE 
    //                 WHEN SUM(nilai_kontrak) > 0 THEN ROUND((SUM(nilai_sisa) / SUM(nilai_kontrak)) * 100, 2)
    //                 ELSE 0
    //             END as persentasi_outstanding
    //         ')
    //         );

    //     // Apply filters if present
    //     if ($tahun) {
    //         $query->whereYear('tanggal_omspan', $tahun);
    //     }

    //     if ($tanggal) {
    //         $query->where('tanggal_omspan', $tanggal);
    //     }

    //     // Group after applying filters
    //     $query->groupBy('nama_satker');

    //     $result = $query->get();

    //     return response()->json($result);
    // }

    public function getGroupedPbjBySatker(Request $request)
    {
        $tahun = $request->input('tahun');
        $tanggal = $request->input('tanggal');

        // Subquery: Aggregate tbl_outstanding_blokir by kdsatker
        $outstandingSub = DB::table('tbl_outstanding_blokir')
            ->select(
                'kdsatker',
                DB::raw('SUM(outstanding) as total_outstanding'),
                DB::raw('SUM(blokir) as total_blokir')
            )
            ->where('tanggal_omspan', $tanggal)
            ->groupBy('kdsatker');

        // Main query: Aggregate tbl_pbj and join with subquery on kdsatker
        $query = DB::table('tbl_pbj as p')
            ->leftJoinSub($outstandingSub, 'o', function ($join) {
                $join->on('p.kdsatker', '=', 'o.kdsatker');
            })
            ->select(
                'p.kdsatker',
                'p.nama_satker',
                DB::raw('SUM(p.nilai_kontrak) as nilai_kontrak'),
                DB::raw('SUM(p.nilai_realisasi) as nilai_realisasi'),
                DB::raw('SUM(p.nilai_sisa) as nilai_outstanding_pbj'),

                // Persentase PBJ Realisasi
                DB::raw('
                CASE 
                    WHEN SUM(p.nilai_kontrak) > 0 THEN ROUND((SUM(p.nilai_realisasi) / SUM(p.nilai_kontrak)) * 100, 2)
                    ELSE 0
                END as persentasi_realisasi_pbj
            '),

                // Persentase PBJ Outstanding
                DB::raw('
                CASE 
                    WHEN SUM(p.nilai_kontrak) > 0 THEN ROUND((SUM(p.nilai_sisa) / SUM(p.nilai_kontrak)) * 100, 2)
                    ELSE 0
                END as persentasi_outstanding_pbj
            '),

                // From tbl_outstanding_blokir
                DB::raw('IFNULL(SUM(o.total_outstanding), 0) as total_outstanding_blokir'),
                DB::raw('IFNULL(SUM(o.total_blokir), 0) as total_blokir')
            );

        // Apply filters
        if ($tahun) {
            $query->whereYear('p.tanggal_omspan', $tahun);
        }

        if ($tanggal) {
            $query->whereDate('p.tanggal_omspan', $tanggal);
        }

        // Group by satker
        $query->groupBy('p.kdsatker', 'p.nama_satker')->orderByRaw("
    CASE
        WHEN nama_satker LIKE '%Sekretariat%' THEN 1
        WHEN nama_satker LIKE '%Pusat%' THEN 2
          WHEN nama_satker LIKE '%Balai Besar%' THEN 3
        WHEN nama_satker LIKE '%Politeknik%' THEN 4
        WHEN nama_satker LIKE '%Akademi%' THEN 5
        WHEN nama_satker LIKE '%Sekolah%' THEN 6
        WHEN nama_satker LIKE '%Pelatihan%' THEN 7
        WHEN nama_satker LIKE '%Penyuluhan%' THEN 8
                WHEN nama_satker LIKE '%Balai Riset%' THEN 9
        WHEN nama_satker LIKE '%Loka%' THEN 10
        ELSE 11
    END ASC
");

        $result = $query->get();

        return response()->json($result);
    }



    public function getPBJGroupedByAkun(Request $request)
    {
        $tanggal = $request->query('tanggal');
        $tahun = $request->query('tahun');

        $akunLabels = [
            '52' => 'Belanja Barang dan Jasa',
            '53' => 'Belanja Modal',
        ];

        $query = DB::table('tbl_pbj')
            ->select(
                DB::raw("SUBSTRING(akun, 1, 2) as akun_prefix"),
                DB::raw("SUM(nilai_kontrak) as nilai_kontrak"),
                DB::raw("SUM(nilai_realisasi) as nilai_realisasi"),
                DB::raw("SUM(nilai_sisa) as nilai_outstanding")
            );

        if ($tanggal) {
            $query->where('tanggal_omspan', $tanggal);
        }

        if ($tahun) {
            $query->whereYear('tanggal_omspan', $tahun);
        }

        $data = $query
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


    public function getSummaryChartKS(Request $request)
    {
        $tahunMulai = trim($request->input('tahunMulai', ''));
        $tahunSelesai = trim($request->input('tahunSelesai', ''));

        $buildQuery = function ($column) use ($tahunMulai, $tahunSelesai) {
            $query = DB::table('tbl_kerjasama')->select($column, DB::raw('COUNT(*) as total'));

            if ($tahunMulai !== '') {
                $query->where('Mulai', $tahunMulai);
            }

            if ($tahunSelesai !== '') {
                $query->where('Selesai', $tahunSelesai);
            }

            return $query
                ->groupBy($column)
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn($item) => [$item->$column ?? 'Tidak Diisi' => $item->total]);
        };

        $summary = [
            'substansi'     => $buildQuery('Substansi'),
            'lingkup'       => $buildQuery('Lingkup'),
            'pemrakarsa'    => $buildQuery('Pemrakarsa'),
            'jenis_dokumen' => $buildQuery('Jenis_Dokumen'),
            'tingkatan'     => $buildQuery('Tingkatan'),
        ];

        return response()->json($summary);
    }



    public function getRincianDataKS(Request $request)
    {
        $tahunMulai = trim($request->input('tahunMulai', ''));
        $tahunSelesai = trim($request->input('tahunSelesai', ''));

        $query = DB::table('tbl_kerjasama');

        if ($tahunMulai !== '') {
            $query->where('Mulai', '=', $tahunMulai);
        }

        if ($tahunSelesai !== '') {
            $query->where('Selesai', '=', $tahunSelesai);
        }

        $data = $query->get();

        return response()->json($data);
    }


    public function getDistinctTanggalOmspan(): JsonResponse
    {
        $tanggalList = DB::table('tbl_dipa_belanja')
            ->select('tanggal_omspan')
            ->distinct()
            ->orderBy('tanggal_omspan', 'desc')
            ->pluck('tanggal_omspan');

        return response()->json([
            'tanggal_omspan' => $tanggalList,
        ]);
    }

    public function getDistinctTanggalOmspanPendapatan(): JsonResponse
    {
        $tanggalList = DB::table('tbl_realisasi_pendapatan')
            ->select('tanggal_omspan')
            ->distinct()
            ->orderBy('tanggal_omspan', 'desc')
            ->pluck('tanggal_omspan');

        return response()->json([
            'tanggal_omspan' => $tanggalList,
        ]);
    }
    public function getDistinctTanggalOmspanPBJ(): JsonResponse
    {
        $tanggalList = DB::table('tbl_pbj')
            ->select('tanggal_omspan')
            ->whereNotNull('tanggal_omspan') // exclude nulls
            ->distinct()
            ->orderBy('tanggal_omspan', 'desc')
            ->pluck('tanggal_omspan');

        return response()->json([
            'tanggal_omspan' => $tanggalList,
        ]);
    }


    public function postDocumentKS(Request $request, $rowId)
    {
        $request->validate([
            'document' => 'required|mimes:pdf|max:20048', // max 2MB
        ]);

        $satuan = TblKerjaSama::where('ID', $rowId)->first();

        if (!$satuan) {
            return response()->json(['message' => 'Data not found.'], 404);
        }

        if ($request->hasFile('document')) {
            $filename = Str::uuid() . '.' . $request->file('document')->getClientOriginalExtension();
            $path = $request->file('document')->storeAs('ks', $filename, 'public');

            // Save the URL or relative path to the Website column
            $satuan->File_Dokumen = '/storage/' . $path;
            $satuan->save();

            return response()->json([
                'message' => 'Image uploaded and Website updated.',
                'data' => $satuan
            ]);
        }

        return response()->json(['message' => 'Image not uploaded.'], 400);
    }
}
