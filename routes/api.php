<?php

use App\Http\Controllers\Api\AlumniController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\GapokkanDidampingiController;
use App\Http\Controllers\Api\KelompokDibentukController;
use App\Http\Controllers\Api\KelompokDisuluhController;
use App\Http\Controllers\Api\KelompokDitingkatkanController;
use App\Http\Controllers\Api\ManagerialController;
use App\Http\Controllers\Api\PendidikController;
use App\Http\Controllers\Api\PentaruController;
use App\Http\Controllers\Api\PenyuluhController;
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
Route::get('/peserta-didiks/getStudentWithLocation', [PesertaDidikController::class, 'getStudentWithLocation']);
Route::get('/alumnis', [AlumniController::class, 'index']);
Route::get('/alumnis/summary', [AlumniController::class, 'summary']);
Route::get('/alumnis/location', [AlumniController::class, 'location']);
Route::get('/alumnis/{id}', [AlumniController::class, 'show']);


Route::get('/peserta-didiks/summary', action: [PesertaDidikController::class, 'summary']);
Route::get('/peserta-didiks/{id}', action: [PesertaDidikController::class, 'show']);


Route::get('/', []);

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
Route::get('/anggaran/rincian-pusat', [ManagerialController::class, 'getRincianDipaPerPusat']);


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
Route::post('/kerja-sama/{rowId}/postDocumentKS', [ManagerialController::class, 'postDocumentKS']);
Route::post('/kerja-sama/{rowId}/postDocumentKSDataOnly', [ManagerialController::class, 'postDocumentKSOnlyData']);



Route::get('publikasi', [PublicationController::class, 'index']);             // Get all
Route::get('publikasi/{slug}', [PublicationController::class, 'show']);       // Get by slug
Route::post('publikasi', [PublicationController::class, 'store']);            // Create new
Route::post('publikasi/{id}', [PublicationController::class, 'update']);       // Update by ID

// Update counters
Route::post('publikasi/{id}/access_count', [PublicationController::class, 'updateAccessCount']);
Route::post('publikasi/{id}/tanggal_count', [PublicationController::class, 'updateTanggalCount']);

Route::post('/satuan-pendidikan/{rowId}/profile', [SatuanPendidikanController::class, 'updateWebsite']);

// Penyuluhan
Route::prefix('penyuluh')->group(function () {
    Route::get('/group-by-status',        [PenyuluhController::class, 'groupByStatus']);
    Route::get('/group-by-jabatan',       [PenyuluhController::class, 'groupByJabatan']);
    Route::get('/group-by-pendidikan',    [PenyuluhController::class, 'groupByPendidikan']);
    Route::get('/group-by-kelompok-usia', [PenyuluhController::class, 'groupByKelompokUsia']);
    Route::get('/group-by-kelamin',       [PenyuluhController::class, 'groupByKelamin']);
    Route::get('/count-by-satminkal', [PenyuluhController::class, 'countBySatminkal']);
    Route::get('/grouped-by-satminkal', [PenyuluhController::class, 'getGroupedBySatminkalDetails']);
});

Route::prefix('kelompok-disuluh')->group(function () {
    Route::get('/jumlah-per-satminkal', [KelompokDisuluhController::class, 'jumlahPerSatminkal']);
    Route::get('/jumlah-per-provinsi', [KelompokDisuluhController::class, 'jumlahPerProvinsi']);
    Route::get('/bidang-usaha-per-provinsi', [KelompokDisuluhController::class, 'bidangUsahaPerProvinsi']);
    Route::get('/kelas-per-provinsi', [KelompokDisuluhController::class, 'kelasKelompokPerProvinsi']);
    Route::get('/bidang-usaha-per-satminkal', [KelompokDisuluhController::class, 'bidangUsahaPerSatminkal']);
});


Route::prefix('kelompok-ditingkatkan')->group(function () {
    Route::get('/jumlah-per-satminkal', [KelompokDitingkatkanController::class, 'jumlahPerSatminkal']);
    Route::get('/jumlah-per-provinsi', [KelompokDitingkatkanController::class, 'jumlahPerProvinsi']);
    Route::get('/bidang-usaha-per-provinsi', [KelompokDitingkatkanController::class, 'bidangUsahaPerProvinsi']);
    Route::get('/kelas-per-provinsi', [KelompokDitingkatkanController::class, 'kelasPerProvinsi']);
    Route::get('/bidang-usaha-per-satminkal', [KelompokDitingkatkanController::class, 'bidangUsahaPerSatminkal']);
});

Route::prefix('kelompok-dibentuk')->group(function () {
    Route::get('/bidang-usaha-per-satminkal', [KelompokDibentukController::class, 'bidangUsahaPerSatminkal']);
    Route::get('/bidang-usaha-per-provinsi', [KelompokDibentukController::class, 'bidangUsahaPerProvinsi']);
});

Route::get('/gapokkan/per-satminkal', [GapokkanDidampingiController::class, 'perSatminkal']);
Route::get('/gapokkan/per-provinsi', [GapokkanDidampingiController::class, 'perProvinsi']);


Route::get('/pentaru', [PentaruController::class, 'index']);
Route::get('/pentaru/{no_pendaftaran}', [PentaruController::class, 'show']);
Route::get('/pentaru/location', [PentaruController::class, 'location']);
