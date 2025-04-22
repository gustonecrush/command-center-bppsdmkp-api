<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    // 1. Verify kode_akses
    public function verify($kode_akses)
    {
        $exists = Backup::where('kode_akses', $kode_akses)->exists();
        return response()->json(['exists' => $exists]);
    }

    // 2. Get backup data by kode_akses
    public function getBackup($kode_akses)
    {
        $backup = Backup::where('kode_akses', $kode_akses)->first();

        if (!$backup) {
            return response()->json(['error' => 'Kode akses not found'], 404);
        }

        return response()->json([
            'kode_akses' => $backup->kode_akses,
            'backup' => json_decode($backup->backup), // assuming backup is stored as JSON string
        ]);
    }
}
