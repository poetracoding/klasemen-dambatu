<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PertandinganVote extends Model
{
    protected $table = 'pertandingan_votes';

    protected $fillable = [
        'pertandingan_id',
        'visitor_hash',
        'choice',
    ];

    public static function summaryForMatch(int $matchId, string $visitorHash): array
    {
        $rows = self::where('pertandingan_id', $matchId)
            ->selectRaw('choice, COUNT(*) as total')
            ->groupBy('choice')
            ->pluck('total', 'choice')
            ->all();

        $home = (int) ($rows['home'] ?? 0);
        $away = (int) ($rows['away'] ?? 0);
        $total = $home + $away;

        $homePct = $total > 0 ? (int) round(($home / $total) * 100) : 50;
        $awayPct = $total > 0 ? (100 - $homePct) : 50;

        $userChoice = self::where('pertandingan_id', $matchId)
            ->where('visitor_hash', $visitorHash)
            ->value('choice');

        return [
            'home' => $home,
            'away' => $away,
            'total' => $total,
            'home_pct' => $homePct,
            'away_pct' => $awayPct,
            'user_choice' => $userChoice,
        ];
    }
}
