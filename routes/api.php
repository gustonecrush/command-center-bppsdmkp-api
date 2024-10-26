<?php

use App\Http\Controllers\Api\PendidikController;

Route::get('/pendidiks', [PendidikController::class, 'index']);
