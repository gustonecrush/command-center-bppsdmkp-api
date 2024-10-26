<?php

use App\Http\Controllers\Api\PendidikController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('/pendidiks', PendidikController::class);
