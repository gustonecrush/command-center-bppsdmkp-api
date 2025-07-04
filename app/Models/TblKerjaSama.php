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
}
