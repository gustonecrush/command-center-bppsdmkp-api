<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblOutputKinerja extends Model
{
    protected $table = 'tbl_k_output';

    protected $fillable = [
        'id_iku',
        'nama',
        'kode',
        'alokasi_anggaran',
        'realisasi_anggaran',
        'satuan_target',
        't_tw',
        'r_tw',
        'tw',
        'tahun',
    ];

    protected $casts = [
        'alokasi_anggaran' => 'decimal:2',
        'realisasi_anggaran' => 'decimal:2',
        't_tw' => 'decimal:2',
        'r_tw' => 'decimal:2',
    ];

    public function iku()
    {
        return $this->belongsTo(TblIndikatorKinerja::class, 'id_iku');
    }
}
