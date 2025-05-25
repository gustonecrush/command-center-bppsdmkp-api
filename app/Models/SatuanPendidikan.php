<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatuanPendidikan extends Model
{
    use HasFactory;
    protected $primaryKey = 'RowID';
    protected $table = "satuan_pendidikan";
}
