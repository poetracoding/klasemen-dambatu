<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tim extends Model
{
    protected $fillable = [
        'id',
        'liga_id',
        'nama_tim',
        'pemain1',
        'pemain2',
        'status',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(Pertandingan::class, 'tim_home');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(Pertandingan::class, 'tim_away');
    }
}

