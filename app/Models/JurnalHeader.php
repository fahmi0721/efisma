<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalHeader extends Model
{
    use HasFactory;
    protected $table = 'jurnal_header';

    protected $fillable = [
        'kode_jurnal',
        'no_invoice',
        'jenis',
        'is_multi_cabang',
        'tanggal',
        'tanggal_invoice',
        'keterangan',
        'no_resi',
        'entitas_id',
        'partner_id',
        'jurnal_id_jkk',
        'cabang_id',
        'total_debit',
        'total_kredit',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'tanggal'         => 'date',
        'tanggal_invoice' => 'date',
        'posted_at'       => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'total_debit'     => 'decimal:2',
        'total_kredit'    => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(JurnalDetail::class, 'jurnal_id', 'id');
    }

   

    public function partner()
    {
        return $this->belongsTo(MPartner::class, 'partner_id', 'id');
    }

    public function cabang()
    {
        return $this->belongsTo(MCabang::class, 'cabang_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by', 'id');
    }

    
}
