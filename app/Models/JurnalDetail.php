<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalDetail extends Model
{
    use HasFactory;
    protected $table = 'jurnal_detail';

    protected $fillable = [
        'jurnal_id',
        'akun_id',
        'cabang_id',
        'jurnal_id_secound',
        'deskripsi',
        'debit',
        'kredit',
    ];

    protected $casts = [
        'jurnal_id'          => 'integer',
        'akun_id'            => 'integer',
        'cabang_id'          => 'integer',
        'jurnal_id_secound'  => 'integer',
        'debit'              => 'decimal:2',
        'kredit'             => 'decimal:2',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(JurnalHeader::class, 'jurnal_id', 'id');
    }

    public function akun()
    {
        return $this->belongsTo(MAkun::class, 'akun_id', 'id');
    }

    public function cabang()
    {
        return $this->belongsTo(MCabang::class, 'cabang_id', 'id');
    }

    public function jurnalSecond()
    {
        return $this->belongsTo(JurnalHeader::class, 'jurnal_id_secound', 'id');
    }
}
