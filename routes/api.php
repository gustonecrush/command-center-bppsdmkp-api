<?php

use App\Http\Controllers\Api\AlumniController;
use App\Http\Controllers\Api\PendidikController;
use App\Http\Controllers\Api\PentaruController;
use App\Http\Controllers\Api\PesertaDidikController;
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

Route::get('/peserta-didiks', [PesertaDidikController::class, 'index']);
Route::get('/alumnis', [AlumniController::class, 'index']);
Route::get('/pendidiks/summary', [PendidikController::class, 'summary']);
Route::get('/alumnis/summary', [AlumniController::class, 'summary']);
Route::get('/peserta-didiks/summary', [PesertaDidikController::class, 'summary']);

Route::get('/', []);

Route::get('/pentaru', [PentaruController::class, 'index']);
Route::get('/pentaru/pendaftar/{id}', [PentaruController::class, 'showByRowID']);
