<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkunSaldoSetting extends Model
{
    protected $table = 'm_akun_saldo_setting';

    protected $fillable = [
        'entitas_id',
        'akun_gl_id',
        'metode_saldo',
        'perlakuan_tahun_buku',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function entitas()
    {
        return $this->belongsTo(
            Entitas::class,
            'entitas_id'
        );
    }

    public function akun()
    {
        return $this->belongsTo(
            MAkunMaster::class,
            'akun_gl_id'
        );
    }
}
