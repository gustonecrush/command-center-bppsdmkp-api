<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LembagaPelatihan extends Model
{
    use HasFactory;

    protected $primaryKey = 'RowID';
    protected $table = "lembaga_pelatihan";
    public $timestamps = true;

    protected $fillable = [
        'Tanggal_Data',
        'Is_Manual_Update',
        'Nama',
        'Jenis',
        'Alamat',
        'Pimpinan',
        'Website',
        'Email',
        'Telepon',
        'Jumlah_Instruktur',
        'Jumlah_Widyaiswara',
        'Lokasi_Lintang',
        'Lokasi_Bujur',
        'Kode_Satker',
    ];

    protected $casts = [
        'Tanggal_Data' => 'date',
        'Lokasi_Lintang' => 'decimal:7',
        'Lokasi_Bujur' => 'decimal:7',
    ];
}
