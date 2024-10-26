<?php

use App\Http\Controllers\Api\PendidikController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/pendidiks', [PendidikController::class, 'index']);
