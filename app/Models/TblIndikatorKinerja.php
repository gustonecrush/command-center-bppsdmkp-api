<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblIndikatorKinerja extends Model
{
    use HasFactory;

    protected $table = "tbl_k_iku";
    protected $guarded = [];

    public function outputKomponen()
    {
        return $this->hasMany(TblOutputKomponen::class, 'id_iku');
    }

    public function sasaran()
    {
        return $this->belongsTo(TblSasaran::class, 'id_sasaran');
    }
}
