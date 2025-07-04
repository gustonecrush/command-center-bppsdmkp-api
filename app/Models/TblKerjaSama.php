<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblKerjaSama extends Model
{
    use HasFactory;

    protected $table = "tbl_kerjasama";
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'Mulai' => 'integer',
        'Selesai' => 'integer',
    ];

    protected $fillable = [
        'Judul_Kerja_Sama',
        'Ruang_Lingkup',
        'Substansi',
        'Pemrakarsa',
        'Jenis_Dokumen',
        'Lingkup',
        'Tingkatan',
        'Pihak_KKP',
        'Pihak_Mitra',
        'Informasi_Penandatanganan',
        'Mulai',
        'Selesai',
        'Pembiayaan',
        'Keterangan',
        'File_Dokumen',
        'Created_By',
        'When_Created',
        'Updated_By',
        'When_Updated',
    ];
}
