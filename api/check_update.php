<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
    $l = $pdo->query('SELECT id, status FROM liga ORDER BY id LIMIT 1')->fetch();
    if (!$l) {
        echo json_encode(['status' => 'empty', 'hash' => 'none']);
        exit;
    }

    $st = $pdo->prepare("SELECT 
        COUNT(*) as total, 
        COALESCE(SUM(status='selesai'), 0) as selesai,
        COALESCE(SUM(skor_home), 0) + COALESCE(SUM(skor_away), 0) as total_skor,
        COALESCE(MAX(updated_at), '') as last_pertandingan,
        COALESCE(MAX(waktu), '') as last_waktu
        FROM pertandingan WHERE id_liga = ?");
    $st->execute([$l['id']]);
    $p = $st->fetch();

    $timCount = (int) $pdo->query('SELECT COUNT(*) FROM tim WHERE id_liga=' . (int)$l['id'])->fetchColumn();

    $stateString = implode('|', [
        $l['status'],
        $timCount,
        $p['total'],
        $p['selesai'],
        $p['total_skor'],
        $p['last_pertandingan'],
        $p['last_waktu']
    ]);

    $hash = md5($stateString);

    echo json_encode([
        'success'       => true,
        'hash'          => $hash,
        'status_liga'   => $l['status'],
        'total_selesai' => (int) $p['selesai'],
        'total_match'   => (int) $p['total'],
        'timestamp'     => time()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
