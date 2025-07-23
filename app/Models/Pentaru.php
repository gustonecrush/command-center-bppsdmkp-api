<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pentaru extends Model
{
    protected $table = 'pentaru';
    public $timestamps = false;
    protected $primaryKey = 'no_pendaftaran';
    protected $guarded = [];
}
