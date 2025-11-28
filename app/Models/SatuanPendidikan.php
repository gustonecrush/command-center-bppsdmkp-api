<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatuanPendidikan extends Model
{
    use HasFactory;

    protected $primaryKey = 'RowID';
    protected $table = "satuan_pendidikan";
    public $timestamps = false;

    protected $fillable = [
        'Tanggal_Data',
        'Is_Manual_Update',
        'Nama',
        'Jenjang',
        'Alamat',
        'Pimpinan',
        'Website',
        'Jumlah_Peserta_Didik',
        'Jumlah_Alumni',
        'Jumlah_Pendidik',
        'Jumlah_Tenaga_Kependidikan',
        'Lokasi_Lintang',
        'Lokasi_Bujur',
        'Akreditasi_Lembaga',
        'Akreditasi_Prodi',
        'Kode_Satker',
    ];
}
