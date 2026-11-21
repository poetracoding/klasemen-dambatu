<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    protected $fillable = [
        'token',
        'token_hash',
        'notify_skor',
        'notify_berita',
        'user_agent',
        'last_seen_at',
    ];

    protected $casts = [
        'notify_skor' => 'boolean',
        'notify_berita' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public static function hashToken(string $token): string
    {
        return hash('sha256', trim($token));
    }
}
