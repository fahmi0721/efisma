<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPartner extends Model
{
    use HasFactory;
    protected $table = 'm_partner';

    protected $fillable = [
        'nama',
        'alamat',
        'no_telpon',
        'entitas_id',
    ];

    
}
