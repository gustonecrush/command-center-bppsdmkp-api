<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblSasaran extends Model
{
    use HasFactory;

    protected $table = "tbl_sasaran";
    protected $guarded = [];

    public function indikatorKinerja()
    {
        return $this->hasMany(TblIndikatorKinerja::class, 'id_sasaran');
    }
}
