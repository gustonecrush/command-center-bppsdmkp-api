<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlUploadManajerialController extends Controller
{
    private $allowedTables = [
        'tbl_dipa_pendapatan',
        'tbl_dipa_belanja',
        'tbl_realisasi_belanja',
        'tbl_realisasi_pendapatan',
        'tbl_pbj',
        'tbl_outstanding_blokir'
    ];

    public function upload(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|mimes:sql,txt|max:10240'
        ]);

        try {
            $file = $request->file('sql_file');
            $sqlContent = file_get_contents($file->getRealPath());

            // Validasi queries
            if (!$this->isValidQueries($sqlContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File hanya boleh berisi DELETE/INSERT/UPDATE untuk tabel yang diizinkan'
                ], 400);
            }

            DB::beginTransaction();

            $queries = $this->parseQueries($sqlContent);
            $stats = ['delete' => 0, 'insert' => 0, 'update' => 0];

            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;

                DB::statement($query);

                if (preg_match('/^\s*DELETE\s+/i', $query)) $stats['delete']++;
                elseif (preg_match('/^\s*INSERT\s+/i', $query)) $stats['insert']++;
                elseif (preg_match('/^\s*UPDATE\s+/i', $query)) $stats['update']++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'SQL berhasil dieksekusi',
                'statistics' => $stats,
                'total' => array_sum($stats)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SQL Upload Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isValidQueries($sqlContent)
    {
        // Hilangkan comments
        $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

        $queries = $this->parseQueries($sqlContent);

        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;

            // Cek hanya DELETE, INSERT, atau UPDATE
            if (!preg_match('/^\s*(DELETE|INSERT|UPDATE)\s+/i', $query)) {
                return false;
            }

            // Extract table name
            $tableName = $this->extractTableName($query);

            if (!in_array($tableName, $this->allowedTables)) {
                return false;
            }
        }

        return true;
    }

    private function extractTableName($query)
    {
        // DELETE FROM table_name
        if (preg_match('/DELETE\s+FROM\s+`?(\w+)`?/i', $query, $matches)) {
            return $matches[1];
        }

        // INSERT INTO table_name
        if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?/i', $query, $matches)) {
            return $matches[1];
        }

        // UPDATE table_name
        if (preg_match('/UPDATE\s+`?(\w+)`?/i', $query, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function parseQueries($sqlContent)
    {
        // Split by semicolon, filter empty
        return array_filter(
            array_map('trim', explode(';', $sqlContent)),
            fn($q) => !empty($q)
        );
    }
}
