<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pertandingan extends Model
{
    protected $fillable = [
        'id',
        'liga_id',
        'ronde',
        'nomor_pertandingan',
        'tanggal',
        'waktu',
        'lokasi',
        'tim_home',
        'tim_away',
        'skor_home',
        'skor_away',
        'pemenang_id',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'ronde' => 'integer',
        'nomor_pertandingan' => 'integer',
        'skor_home' => 'integer',
        'skor_away' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_home');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'tim_away');
    }

    public function pemenang(): BelongsTo
    {
        return $this->belongsTo(Tim::class, 'pemenang_id');
    }
}

