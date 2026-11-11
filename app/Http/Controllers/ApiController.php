<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use App\Models\Pertandingan;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function checkUpdate(): JsonResponse
    {
        $liga = Liga::current();

        $agg = Pertandingan::where('liga_id', $liga->id)
            ->selectRaw('COUNT(*) as total,
                         SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai,
                         COALESCE(SUM(skor_home), 0) as sh,
                         COALESCE(SUM(skor_away), 0) as sa,
                         COALESCE(MAX(updated_at), "") as last_update')
            ->first();

        $hashString = implode('|', [
            $liga->status,
            $liga->updated_at,
            $agg->total ?? 0,
            $agg->selesai ?? 0,
            $agg->sh ?? 0,
            $agg->sa ?? 0,
            $agg->last_update ?? '',
        ]);

        return response()->json([
            'success' => true,
            'hash' => md5($hashString),
            'timestamp' => time(),
        ]);
    }
}

