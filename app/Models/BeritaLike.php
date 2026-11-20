<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaLike extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'berita_id',
        'visitor_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class, 'berita_id');
    }
}
