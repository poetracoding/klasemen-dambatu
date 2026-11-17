<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Poster extends Model
{
    protected $fillable = [
        'liga_id',
        'judul',
        'deskripsi',
        'file_path',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function getUrlAttribute(): string
    {
        return asset($this->file_path);
    }

    public function getDownloadNameAttribute(): string
    {
        $ext = pathinfo($this->file_path, PATHINFO_EXTENSION) ?: 'png';
        return Str::slug($this->judul ?: 'informasi-' . $this->id) . '.' . $ext;
    }
}
