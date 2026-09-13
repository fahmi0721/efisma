<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MAkunMaster extends Model
{
    use HasFactory;
    protected $table = 'm_akun_gl';

    protected $fillable = [
        'no_akun',
        'nama',
        'kategori',
    ];

    
}
