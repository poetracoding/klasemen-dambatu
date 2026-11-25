<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liga extends Model
{
    protected $fillable = [
        'nama_liga',
        'musim',
        'google_analytics_id',
        'app_icon',
        'app_tagline',
        'footer_text',
        'kontak_panitia',
        'theme_color',
        'tentang_aplikasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'jumlah_meja',
        'hari_pertandingan',
        'tanggal_libur',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
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

    /**
     * Kembalikan URL aman untuk icon aplikasi kustom jika berkas valid tersedia di public/uploads/app/.
     */
    public function appIconUrl(): ?string
    {
        $path = trim((string) ($this->app_icon ?? ''));
        if ($path === '' || str_contains($path, '..') || !str_starts_with($path, 'uploads/app/')) {
            return null;
        }

        $full = public_path($path);
        if (!is_file($full)) {
            return null;
        }

        return asset($path) . '?v=' . filemtime($full);
    }
}

