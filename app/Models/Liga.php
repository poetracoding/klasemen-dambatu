<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liga extends Model
{
    protected $fillable = [
        'nama_liga',
        'musim',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'jumlah_meja',
        'hari_pertandingan',
        'tanggal_libur',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jumlah_meja' => 'integer',
    ];

    public function tims(): HasMany
    {
        return $this->hasMany(Tim::class);
    }

    public function pertandingans(): HasMany
    {
        return $this->hasMany(Pertandingan::class);
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'nama_liga' => 'LIGA DAM BATU',
            'musim' => date('Y'),
            'status' => 'draft',
            'jumlah_meja' => 2,
            'hari_pertandingan' => '1,2,3,4,5,6,7',
        ]);
    }
}

