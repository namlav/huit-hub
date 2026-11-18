<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Chỉ hỗ trợ phương thức GET.']);
    exit;
}

$expertise = trim((string) ($_GET['expertise'] ?? ''));
$branchId = (int) ($_GET['branch_id'] ?? 0);
$day = (int) ($_GET['day_of_week'] ?? 0);
$shift = trim((string) ($_GET['shift_or_periods'] ?? ''));
$room = trim((string) ($_GET['room'] ?? ''));

$branchIds = array_map('intval', array_column(all_branches(), 'id'));

if (!in_array($expertise, EXPERTISES, true)
    || !in_array($branchId, $branchIds, true)
    || !isset(DAYS[$day])
    || !isset(SHIFTS[$shift])
) {
    http_response_code(422);
    echo json_encode(['error' => 'Thiếu hoặc sai tham số: expertise, branch_id, day_of_week, shift_or_periods.']);
    exit;
}

echo json_encode([
    'teachers' => suggest_teachers($expertise, $branchId, $day, $shift),
    'room_conflict' => room_conflict($branchId, $room, $day, $shift),
], JSON_UNESCAPED_UNICODE);
