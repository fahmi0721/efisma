<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MCabang extends Model
{
    use HasFactory;
    protected $table = 'm_cabang';

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    
}
