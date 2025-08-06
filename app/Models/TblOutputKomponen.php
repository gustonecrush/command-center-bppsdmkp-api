<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblOutputKomponen extends Model
{
    use HasFactory;

    protected $table = "tbl_k_output";
    protected $guarded = [];

    public function indikatorKinerja()
    {
        return $this->belongsTo(TblIndikatorKinerja::class, 'id_iku');
    }
}
