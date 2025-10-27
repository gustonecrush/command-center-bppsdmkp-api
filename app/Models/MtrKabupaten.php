<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtrKabupaten extends Model
{
    use HasFactory;
    protected $table = 'mtr_kabupatens';
    protected $guard = ['id'];
    public $timestamps = false;
}
