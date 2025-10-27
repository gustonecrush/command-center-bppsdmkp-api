<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtrProvinsi extends Model
{
    use HasFactory;
    protected $table = 'mtr_provinsis';
    protected $guard = ['id'];
    public $timestamps = false;
}
