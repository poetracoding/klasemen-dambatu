<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Berita extends Model
{
    public const KATEGORI_LIST = ['Berita', 'Rekap Laga', 'Pengumuman', 'Liputan'];

    protected $fillable = [
        'liga_id',
        'user_id',
        'judul',
        'slug',
        'kategori',
        'ringkasan',
        'isi',
        'gambar',
        'status',
        'published_at',
        'views_count',
        'likes_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    public static function generateUniqueSlug(string $judul, ?int $ignoreId = null): string
    {
        $base = Str::slug($judul);
        if ($base === '') {
            $base = 'berita-' . Str::lower(Str::random(6));
        }
        $base = Str::limit($base, 185, '');

        $slug = $base;
        $counter = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $q) => $q->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? asset($this->gambar) : null;
    }

    public function getExcerptAttribute(): string
    {
        if (!empty($this->ringkasan)) {
            return $this->ringkasan;
        }
        return Str::limit(strip_tags((string) $this->isi), 140);
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(BeritaLike::class, 'berita_id');
    }
}
