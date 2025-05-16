<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $table = 'publications';

    protected $fillable = [
        'pub_full_name',
        'pub_short_name',
        'description',
        'subject',
        'doc_type',
        'pub_file',
        'pub_file_type',
        'slug',
        'language',
        'access_count',
        'tanggal_count',
        'source',
    ];

    public $timestamps = true;
}
