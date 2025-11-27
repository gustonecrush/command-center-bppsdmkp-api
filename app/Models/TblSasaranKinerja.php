<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblSasaranKinerja extends Model
{
    protected $table = 'tbl_k_sasaran';

    protected $fillable = [
        'nama',
        'tahun',
    ];

    public function indikatorKinerja()
    {
        return $this->hasMany(TblIndikatorKinerja::class, 'id_sasaran');
    }
}
