<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJurnal extends Model
{
    protected $fillable = [
        'user_id',
        'jenis_upload',
        'entitas_id',
        'nama_file',
        'path_file',
        'status',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}