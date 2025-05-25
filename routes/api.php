<?php

use App\Http\Controllers\Api\AlumniController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\ManagerialController;
use App\Http\Controllers\Api\PendidikController;
use App\Http\Controllers\Api\PentaruController;
use App\Http\Controllers\Api\PesertaDidikController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\Api\SatuanPendidikanController;
use App\Http\Controllers\Api\TenagaKependidikanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/pendidiks', [PendidikController::class, 'index']);
Route::get('/pendidiks/summary', [PendidikController::class, 'summary']);

Route::get('/tenaga-kependidikans', [TenagaKependidikanController::class, 'index']);
Route::get('/tenaga-kependidikans/summary', [TenagaKependidikanController::class, 'summary']);


Route::get('/peserta-didiks', [PesertaDidikController::class, 'index']);
Route::get('/alumnis', [AlumniController::class, 'index']);
Route::get('/alumnis/summary', [AlumniController::class, 'summary']);
Route::get('/peserta-didiks/summary', [PesertaDidikController::class, 'summary']);

Route::get('/', []);

Route::get('/pentaru', [PentaruController::class, 'index']);
Route::get('/pentaru/pendaftar/{id}', [PentaruController::class, 'showByRowID']);

Route::get('/satuan-pendidikan', [SatuanPendidikanController::class, 'index']);

Route::get('/rekap-per-pusat', [ManagerialController::class, 'rekapPerSatker']);
Route::get('/tanggal-omspan', [ManagerialController::class, 'getDistinctTanggalOmspan']);
Route::get('/tanggal-omspan-pendapatan', [ManagerialController::class, 'getDistinctTanggalOmspanPendapatan']);
Route::get('/tanggal-omspan-pbj', [ManagerialController::class, 'getDistinctTanggalOmspanPBJ']);


Route::get('/pendapatan/rekap-per-satker', [ManagerialController::class, 'rekapPerSatkerPendapatan']);
Route::get('/pendapatan/realisasi-sisa', [ManagerialController::class, 'getRealisasiDanSisaPendapatan']);
Route::get('/pendapatan/realisasi-akun', [ManagerialController::class, 'getRealisasiPendapatanPerAkun']);

Route::get('/summary', [ManagerialController::class, 'summary']);

Route::get('/anggaran/rincian-realisasi', [ManagerialController::class, 'getRincianRealisasiAnggaran']);

Route::get('/anggaran/realisasi-sisa', [ManagerialController::class, 'getRealisasiDanSisa']);
Route::get('/anggaran/realisasi-grouped', [ManagerialController::class, 'getRealisasiGrouped']);
Route::get('/anggaran/realisasi-per-day', [ManagerialController::class, 'getRealisasiBelanjaPerDay']);
Route::get('/pendapatan/realisasi-per-day', [ManagerialController::class, 'getRealisasiPendapatanPerDay']);

Route::get('/pendapatan/per-satker-per-akun', [ManagerialController::class, 'getRealisasiPendapatanPerSatkerPerAkun']);

Route::get('/pentaru/summary', [PentaruController::class, 'getGrandSummaryPentaru']);


Route::get('/verify/{kode_akses}', [BackupController::class, 'verify']);
Route::get('/backup/{kode_akses}', [BackupController::class, 'getBackup']);
Route::post('/backup', [BackupController::class, 'storeOrUpdate']);
Route::delete('/backup/{kode_akses}', [BackupController::class, 'destroyByKodeAkses']);

Route::get('/pbj/getAllDataPbj', [ManagerialController::class, 'getAllDataPbj']);
Route::get('/pbj/getGroupedPbjBySatker', [ManagerialController::class, 'getGroupedPbjBySatker']);
Route::get('/pbj/getPBJGroupedByAkun', [ManagerialController::class, 'getPBJGroupedByAkun']);

Route::get('/kerja-sama/getSummaryChartKS', [ManagerialController::class, 'getSummaryChartKS']);
Route::get('/kerja-sama/getRincianDataKS', [ManagerialController::class, 'getRincianDataKS']);


Route::get('publikasi', [PublicationController::class, 'index']);             // Get all
Route::get('publikasi/{slug}', [PublicationController::class, 'show']);       // Get by slug
Route::post('publikasi', [PublicationController::class, 'store']);            // Create new
Route::put('publikasi/{id}', [PublicationController::class, 'update']);       // Update by ID

// Update counters
Route::patch('publikasi/{id}/access_count', [PublicationController::class, 'updateAccessCount']);
Route::patch('publikasi/{id}/tanggal_count', [PublicationController::class, 'updateTanggalCount']);

Route::get('/satuan-pendidikan/profile', [SatuanPendidikanController::class, 'updateWebsite']);
