<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MDaftarUangMuka extends Model
{
    use HasFactory;
    protected $table = 'view_daftar_uang_muka';

    protected $fillable = [
        'jurnal_id',
        'nama',
        'kode_jurnal',
        'keterangan',
        'entitas_id',
        'partner_id',
        'partner_nama',
        'nominal',
        'terpakai',
        'sisa',
        'status',
    ];

    
}
