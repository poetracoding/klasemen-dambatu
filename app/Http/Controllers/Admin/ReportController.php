<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\TournamentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    private const DAY_NAMES = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    private const MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    private const SHORT_MONTHS = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        return view('admin.report', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->buildReportData($request);

        $pdf = Pdf::loadView('pdf.report', $data)
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'defaultFont' => 'Helvetica',
            ]);

        $filename = 'laporan-hasil-' . $data['periode'] . '-' . $data['periodeSlug'] . '-' . date('Ymd-His') . '.pdf';

        if ($request->has('download') || $request->get('action') === 'download') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    private function buildReportData(Request $request): array
    {
        $liga = Liga::current();

        $rawPeriode = (string) $request->get('periode', 'harian');
        $periode = in_array($rawPeriode, ['harian', 'mingguan', 'bulanan'], true) ? $rawPeriode : 'harian';

        // Kumpulkan timestamp updated_at laga selesai untuk opsi cepat per hari, minggu, dan bulan
        $completedTimestamps = Pertandingan::where('liga_id', $liga->id)
            ->where('status', 'selesai')
            ->whereNotNull('updated_at')
            ->orderByDesc('updated_at')
            ->pluck('updated_at');

        $dateCounts = [];
        $weekCounts = [];
        $monthCounts = [];

        foreach ($completedTimestamps as $ts) {
            $dt = $ts instanceof Carbon ? $ts : Carbon::parse((string) $ts);
            $dKey = $dt->format('Y-m-d');
            $wKey = $dt->format('o-\WW');
            $mKey = $dt->format('Y-m');

            $dateCounts[$dKey] = ($dateCounts[$dKey] ?? 0) + 1;
            $weekCounts[$wKey] = ($weekCounts[$wKey] ?? 0) + 1;
            $monthCounts[$mKey] = ($monthCounts[$mKey] ?? 0) + 1;
        }

        // Opsi input filter terpilih sesuai periode
        $rawTanggal = (string) $request->get('tanggal', '');
        $selectedTanggal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTanggal)
            ? $rawTanggal
            : now()->toDateString();

        $rawMinggu = (string) $request->get('minggu', '');
        $selectedMinggu = preg_match('/^(\d{4})-W(0[1-9]|[1-4]\d|5[0-3])$/', $rawMinggu)
            ? $rawMinggu
            : now()->format('o-\WW');

        $rawBulan = (string) $request->get('bulan', '');
        $selectedBulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rawBulan)
            ? $rawBulan
            : now()->format('Y-m');

        // Pastikan nilai terpilih + rentang minggu/bulan terkini ikut tersedia di dropdown
        $weekKeys = array_keys($weekCounts);
        for ($i = 0; $i < 6; $i++) {
            $weekKeys[] = now()->subWeeks($i)->format('o-\WW');
        }
        $weekKeys[] = $selectedMinggu;
        $weekKeys = array_values(array_unique($weekKeys));
        rsort($weekKeys);

        $availableWeeks = [];
        foreach ($weekKeys as $wk) {
            if (preg_match('/^(\d{4})-W(\d{2})$/', $wk, $wm)) {
                $wkStart = Carbon::now()->setISODate((int) $wm[1], (int) $wm[2], 1)->startOfDay();
                $wkEnd = $wkStart->copy()->endOfWeek(Carbon::SUNDAY);
                $cnt = $weekCounts[$wk] ?? 0;
                $availableWeeks[] = [
                    'value' => $wk,
                    'label' => "Minggu {$wm[2]} (" . self::formatRentangSingkat($wkStart, $wkEnd) . ')' . ($cnt > 0 ? " - {$cnt} laga" : ''),
                    'count' => $cnt,
                ];
            }
        }

        $monthKeys = array_keys($monthCounts);
        for ($i = 0; $i < 6; $i++) {
            $monthKeys[] = now()->startOfMonth()->subMonths($i)->format('Y-m');
        }
        $monthKeys[] = $selectedBulan;
        $monthKeys = array_values(array_unique($monthKeys));
        rsort($monthKeys);

        $availableMonths = [];
        foreach ($monthKeys as $mk) {
            $cnt = $monthCounts[$mk] ?? 0;
            $availableMonths[] = [
                'value' => $mk,
                'label' => self::formatBulanId($mk) . ($cnt > 0 ? " ({$cnt} laga)" : ''),
                'count' => $cnt,
            ];
        }

        $recentDates = [];
        foreach (array_slice($dateCounts, 0, 10, true) as $dKey => $cnt) {
            $recentDates[] = [
                'value' => $dKey,
                'label' => self::formatTanggalSingkatId($dKey),
                'count' => $cnt,
            ];
        }

        // Hitung rentang waktu pencarian berdasarkan updated_at
        if ($periode === 'mingguan') {
            preg_match('/^(\d{4})-W(\d{2})$/', $selectedMinggu, $wm);
            $startAt = Carbon::now()->setISODate((int) $wm[1], (int) $wm[2], 1)->startOfDay();
            $endAt = $startAt->copy()->endOfWeek(Carbon::SUNDAY);
            $periodeLabel = "Minggu ke-{$wm[2]} (" . self::formatRentangSingkat($startAt, $endAt) . ')';
            $periodeSlug = Str::slug($selectedMinggu);
            $periodeJudul = 'Laporan Mingguan';
        } elseif ($periode === 'bulanan') {
            $startAt = Carbon::createFromFormat('Y-m-d', $selectedBulan . '-01')->startOfMonth();
            $endAt = $startAt->copy()->endOfMonth();
            $periodeLabel = self::formatBulanId($selectedBulan);
            $periodeSlug = Str::slug($selectedBulan);
            $periodeJudul = 'Laporan Bulanan';
        } else {
            $startAt = Carbon::createFromFormat('Y-m-d', $selectedTanggal)->startOfDay();
            $endAt = $startAt->copy()->endOfDay();
            $periodeLabel = self::formatTanggalId($selectedTanggal);
            $periodeSlug = Str::slug($selectedTanggal);
            $periodeJudul = 'Laporan Harian';
        }

        $timId = max(0, (int) $request->get('tim', 0));
        $tims = Tim::where('liga_id', $liga->id)->orderBy('nama_tim')->get();
        $selectedTim = $timId > 0 ? $tims->firstWhere('id', $timId) : null;

        // Query mutlak berdasarkan updated_at (bukan tanggal jadwal)
        $query = Pertandingan::where('liga_id', $liga->id)
            ->where('status', 'selesai')
            ->whereBetween('updated_at', [$startAt->format('Y-m-d H:i:s'), $endAt->format('Y-m-d H:i:s')])
            ->with(['homeTeam', 'awayTeam', 'pemenang']);

        if ($timId > 0) {
            $query->where(function ($q) use ($timId) {
                $q->where('tim_home', $timId)->orWhere('tim_away', $timId);
            });
        }

        $matches = $query->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $matchesByUpdateDate = $matches->groupBy(function (Pertandingan $m) {
            return $m->updated_at ? $m->updated_at->format('Y-m-d') : 'unknown';
        });

        $totalLaga = $matches->count();
        $totalPoin = $matches->sum(fn (Pertandingan $m) => (int) $m->skor_home + (int) $m->skor_away);
        $avgSelisih = $totalLaga > 0
            ? round($matches->avg(fn (Pertandingan $m) => abs((int) $m->skor_home - (int) $m->skor_away)), 1)
            : 0.0;

        $rekapTim = $this->buildTeamRecap($matches);
        $uniqueTeamCount = count($rekapTim);
        $klasemen = TournamentService::hitungKlasemen($liga);

        return compact(
            'liga',
            'periode',
            'periodeJudul',
            'periodeLabel',
            'periodeSlug',
            'selectedTanggal',
            'selectedMinggu',
            'selectedBulan',
            'recentDates',
            'availableWeeks',
            'availableMonths',
            'timId',
            'tims',
            'selectedTim',
            'startAt',
            'endAt',
            'matches',
            'matchesByUpdateDate',
            'totalLaga',
            'totalPoin',
            'avgSelisih',
            'uniqueTeamCount',
            'rekapTim',
            'klasemen'
        );
    }

    private function buildTeamRecap($matches): array
    {
        $map = [];

        foreach ($matches as $m) {
            $pairs = [
                [
                    'id' => (int) $m->tim_home,
                    'tim' => $m->homeTeam,
                    'bm' => (int) $m->skor_home,
                    'bk' => (int) $m->skor_away,
                ],
                [
                    'id' => (int) $m->tim_away,
                    'tim' => $m->awayTeam,
                    'bm' => (int) $m->skor_away,
                    'bk' => (int) $m->skor_home,
                ],
            ];

            foreach ($pairs as $side) {
                $tId = $side['id'];
                if ($tId <= 0) {
                    continue;
                }

                if (!isset($map[$tId])) {
                    $map[$tId] = [
                        'id' => $tId,
                        'nama_tim' => $side['tim']?->nama_tim ?? ('Tim #' . $tId),
                        'pemain1' => $side['tim']?->pemain1 ?? '',
                        'pemain2' => $side['tim']?->pemain2 ?? '',
                        'mp' => 0,
                        'w' => 0,
                        'l' => 0,
                        'bm' => 0,
                        'bk' => 0,
                        'sb' => 0,
                        'pts' => 0,
                    ];
                }

                $won = (int) $m->pemenang_id === $tId;
                $map[$tId]['mp']++;
                $map[$tId]['w'] += $won ? 1 : 0;
                $map[$tId]['l'] += $won ? 0 : 1;
                $map[$tId]['bm'] += $side['bm'];
                $map[$tId]['bk'] += $side['bk'];
                $map[$tId]['sb'] = $map[$tId]['bm'] - $map[$tId]['bk'];
                $map[$tId]['pts'] = $map[$tId]['w'] * 3;
            }
        }

        $list = array_values($map);
        usort($list, function (array $a, array $b): int {
            return ($b['pts'] <=> $a['pts'])
                ?: ($b['w'] <=> $a['w'])
                ?: ($b['sb'] <=> $a['sb'])
                ?: ($b['bm'] <=> $a['bm'])
                ?: strcasecmp($a['nama_tim'], $b['nama_tim']);
        });

        foreach ($list as $idx => &$row) {
            $row['pos'] = $idx + 1;
        }

        return $list;
    }

    public static function formatTanggalId(string $ymd): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return $ymd;
        }
        $dt = Carbon::createFromFormat('Y-m-d', $ymd);
        $day = self::DAY_NAMES[$dt->dayOfWeek] ?? '';
        $month = self::MONTH_NAMES[$dt->month] ?? $dt->format('m');

        return trim("{$day}, {$dt->day} {$month} {$dt->year}");
    }

    public static function formatTanggalSingkatId(string $ymd): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return $ymd;
        }
        $dt = Carbon::createFromFormat('Y-m-d', $ymd);
        $day = self::DAY_NAMES[$dt->dayOfWeek] ?? '';
        $month = self::SHORT_MONTHS[$dt->month] ?? $dt->format('m');

        return trim("{$day}, {$dt->day} {$month} {$dt->year}");
    }

    public static function formatBulanId(string $ym): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            return $ym;
        }
        $month = self::MONTH_NAMES[(int) $m[2]] ?? $m[2];

        return "{$month} {$m[1]}";
    }

    public static function formatRentangSingkat(Carbon $start, Carbon $end): string
    {
        $mStart = self::SHORT_MONTHS[$start->month] ?? $start->format('m');
        $mEnd = self::SHORT_MONTHS[$end->month] ?? $end->format('m');

        if ($start->year === $end->year) {
            return "{$start->day} {$mStart} - {$end->day} {$mEnd} {$end->year}";
        }

        return "{$start->day} {$mStart} {$start->year} - {$end->day} {$mEnd} {$end->year}";
    }
}
