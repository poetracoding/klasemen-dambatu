<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PertandinganCheer extends Model
{
    protected $table = 'pertandingan_cheers';

    public const REACTIONS = [
        'fire' => ['emoji' => '🔥', 'label' => 'Semangat'],
        'clap' => ['emoji' => '👏', 'label' => 'Mantap'],
        'target' => ['emoji' => '🎯', 'label' => 'Jitu'],
        'trophy' => ['emoji' => '🏆', 'label' => 'Juara'],
    ];

    protected $fillable = [
        'pertandingan_id',
        'visitor_hash',
        'reaction',
    ];

    public static function summaryForMatch(int $matchId, string $visitorHash): array
    {
        $rows = self::where('pertandingan_id', $matchId)
            ->selectRaw('reaction, COUNT(*) as total')
            ->groupBy('reaction')
            ->pluck('total', 'reaction')
            ->all();

        $counts = [];
        foreach (array_keys(self::REACTIONS) as $key) {
            $counts[$key] = (int) ($rows[$key] ?? 0);
        }

        $userReaction = self::where('pertandingan_id', $matchId)
            ->where('visitor_hash', $visitorHash)
            ->value('reaction');

        return [
            'counts' => $counts,
            'total' => array_sum($counts),
            'user_reaction' => $userReaction,
        ];
    }
}
