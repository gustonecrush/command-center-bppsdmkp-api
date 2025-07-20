<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyuluh extends Model
{
    protected $table = 'penyuluh';
    public $timestamps = false;

    protected $fillable = [
        'no',
        'kode_provinsi',
        'provinsi',
        'kode_kab_kota',
        'kab_kota',
        'nama',
        'status',
        'nip_nik',
        'kelamin',
        'usia',
        'jabatan',
        'pendidikan',
        'prodi',
        'satminkal',
        'asn',
        'kelompok_usia',
        'kode_provinsi2',
        'kode_kab_kota2',
        'triwulan'
    ];
}
