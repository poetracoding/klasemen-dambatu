<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaLike extends Model
{
    public const REACTIONS = [
        'love' => ['emoji' => '❤️', 'label' => 'Suka'],
        'smile' => ['emoji' => '😊', 'label' => 'Senyum'],
        'haha' => ['emoji' => '😂', 'label' => 'Tertawa'],
        'fire' => ['emoji' => '🔥', 'label' => 'Seru'],
        'clap' => ['emoji' => '👏', 'label' => 'Mantap'],
    ];

    public $timestamps = false;

    protected $fillable = [
        'berita_id',
        'visitor_hash',
        'reaction',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function emptyCounts(): array
    {
        $counts = [];
        foreach (array_keys(self::REACTIONS) as $key) {
            $counts[$key] = 0;
        }

        return $counts;
    }

    public static function countsForBeritas(array $beritaIds): array
    {
        if ($beritaIds === []) {
            return [];
        }

        $result = [];
        foreach ($beritaIds as $id) {
            $result[(int) $id] = self::emptyCounts();
        }

        $rows = self::query()
            ->selectRaw('berita_id, reaction, COUNT(*) as total')
            ->whereIn('berita_id', $beritaIds)
            ->groupBy('berita_id', 'reaction')
            ->get();

        foreach ($rows as $row) {
            $bId = (int) $row->berita_id;
            $key = array_key_exists((string) $row->reaction, self::REACTIONS)
                ? (string) $row->reaction
                : 'love';
            $result[$bId][$key] = ($result[$bId][$key] ?? 0) + (int) $row->total;
        }

        return $result;
    }

    public static function visitorReactionsForBeritas(string $visitorHash, array $beritaIds): array
    {
        if ($beritaIds === []) {
            return [];
        }

        $rows = self::query()
            ->where('visitor_hash', $visitorHash)
            ->whereIn('berita_id', $beritaIds)
            ->get(['berita_id', 'reaction']);

        $map = [];
        foreach ($rows as $row) {
            $key = array_key_exists((string) $row->reaction, self::REACTIONS)
                ? (string) $row->reaction
                : 'love';
            $map[(int) $row->berita_id] = $key;
        }

        return $map;
    }

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class, 'berita_id');
    }
}
