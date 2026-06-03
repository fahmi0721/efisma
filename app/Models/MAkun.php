<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MAkun extends Model
{
    use HasFactory;
    protected $table = 'view_akun_transaksi_only';

    protected $fillable = [
        'no_akun',
        'nama',
    ];

    
}
