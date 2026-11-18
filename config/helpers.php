<?php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** @return list<array{type: string, message: string}> */
function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_verify(): void
{
    $sent = (string) ($_POST['csrf'] ?? '');

    if ($sent === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $sent)) {
        http_response_code(403);
        exit('Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

/** @return list<array{id: int, branch_name: string, address: string}> */
function all_branches(): array
{
    static $branches = null;

    return $branches ??= db()->query('SELECT id, branch_name, address FROM branches ORDER BY branch_name')->fetchAll();
}

/** @return list<array{id: int, full_name: string, email: string, phone: string, expertise: string}> */
function all_teachers(): array
{
    static $teachers = null;

    return $teachers ??= db()->query('SELECT id, full_name, email, phone, expertise FROM teachers ORDER BY full_name')->fetchAll();
}

function branch_color(int $branchId): string
{
    return BRANCH_COLORS[($branchId - 1) % count(BRANCH_COLORS)];
}

function branch_badge(array $branch): string
{
    $color = branch_color((int) $branch['id']);
    $text = in_array($color, ['warning', 'info'], true) ? 'text-dark' : 'text-white';

    return sprintf('<span class="badge bg-%s %s">%s</span>', $color, $text, e($branch['branch_name']));
}

function shift_short(string $shift): string
{
    $meta = SHIFTS[$shift] ?? null;

    return $meta === null ? $shift : $meta['label'] . ' (' . $meta['range'] . ')';
}

/** @return list<int> */
function shift_gaps(array $busyIndexes, int $newIndex): array
{
    $indexes = array_values(array_unique(array_merge($busyIndexes, [$newIndex])));
    sort($indexes);

    $gaps = [];
    for ($i = $indexes[0]; $i <= $indexes[count($indexes) - 1]; $i++) {
        if (!in_array($i, $indexes, true)) {
            $gaps[] = $i;
        }
    }

    return $gaps;
}

function gap_label(array $gapIndexes): string
{
    $labels = [];
    foreach ($gapIndexes as $index) {
        $labels[] = 'Ca ' . $index;
    }

    return implode(', ', $labels);
}

function room_conflict(int $branchId, string $room, int $day, string $shift, ?int $excludeId = null): ?array
{
    if ($branchId <= 0 || $room === '') {
        return null;
    }

    $sql = 'SELECT s.id, s.subject_name, s.room, t.full_name
            FROM schedules s
            JOIN teachers t ON t.id = s.teacher_id
            WHERE s.branch_id = ? AND s.room = ? AND s.day_of_week = ? AND s.shift_or_periods = ?';
    $params = [$branchId, $room, $day, $shift];

    if ($excludeId !== null) {
        $sql .= ' AND s.id <> ?';
        $params[] = $excludeId;
    }

    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Xếp hạng giảng viên theo chuyên môn, ca trống và mức tối ưu di chuyển.
 *
 * @return list<array<string, mixed>>
 */
function suggest_teachers(string $expertise, int $branchId, int $day, string $shift): array
{
    $stmt = db()->prepare('SELECT id, full_name, email, expertise FROM teachers WHERE expertise = ? ORDER BY full_name');
    $stmt->execute([$expertise]);
    $teachers = $stmt->fetchAll();

    if ($teachers === []) {
        return [];
    }

    $ids = array_map('intval', array_column($teachers, 'id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT s.teacher_id, s.branch_id, s.shift_or_periods, s.day_of_week, b.branch_name
         FROM schedules s
         JOIN branches b ON b.id = s.branch_id
         WHERE s.teacher_id IN ($placeholders)"
    );
    $stmt->execute($ids);

    $load = [];
    foreach ($stmt as $row) {
        $load[(int) $row['teacher_id']][] = $row;
    }

    $newIndex = SHIFT_INDEX[$shift] ?? 0;
    $results = [];

    foreach ($teachers as $teacher) {
        $all = $load[(int) $teacher['id']] ?? [];
        $sameDay = array_values(array_filter(
            $all,
            static fn (array $row): bool => (int) $row['day_of_week'] === $day
        ));

        foreach ($sameDay as $row) {
            if ($row['shift_or_periods'] === $shift) {
                continue 2; // Trùng ca: loại ngay.
            }
        }

        $level = 'normal';
        $score = 60;
        $reason = 'Trống lịch cả ngày — có thể nhận lớp ngay.';
        $dayBranches = [];
        $gapIndexes = [];

        if ($sameDay !== []) {
            $busyIndexes = array_map(static fn (array $row): int => SHIFT_INDEX[$row['shift_or_periods']] ?? 0, $sameDay);
            $adjacentSameBranch = false;
            $otherBranches = [];

            foreach ($sameDay as $row) {
                $rowBranchId = (int) $row['branch_id'];
                $rowIndex = SHIFT_INDEX[$row['shift_or_periods']] ?? 0;

                if (abs($rowIndex - $newIndex) === 1 && $rowBranchId === $branchId) {
                    $adjacentSameBranch = true;
                }

                if ($rowBranchId !== $branchId) {
                    $otherBranches[$row['branch_name']] = true;
                }
            }

            $gapIndexes = shift_gaps($busyIndexes, $newIndex);

            if ($adjacentSameBranch) {
                $level = 'optimal';
                $score = 100;
                $reason = 'Đã có ca liền kề tại cùng cơ sở — không mất thời gian di chuyển.';
            } elseif ($otherBranches !== []) {
                $level = 'warning';
                $score = 30;
                $reason = 'Trong ngày đã dạy tại ' . implode(', ', array_keys($otherBranches))
                    . ' — phải di chuyển giữa các cơ sở.';
                if ($gapIndexes !== []) {
                    $reason .= ' Lịch bị trống ' . gap_label($gapIndexes) . '.';
                }
            } elseif ($gapIndexes !== []) {
                $level = 'warning';
                $score = 20;
                $reason = 'Cùng cơ sở nhưng lịch bị cách quãng (trống ' . gap_label($gapIndexes) . ').';
            } else {
                $level = 'optimal';
                $score = 100;
                $reason = 'Cùng cơ sở, không phát sinh thời gian di chuyển.';
            }
        }

        $meta = [
            'optimal' => ['color' => 'success', 'label' => 'Tối ưu cao'],
            'normal' => ['color' => 'primary', 'label' => 'Bình thường'],
            'warning' => ['color' => 'warning', 'label' => 'Cần cân nhắc'],
        ];

        usort($sameDay, static fn (array $a, array $b): int => (SHIFT_INDEX[$a['shift_or_periods']] ?? 0) <=> (SHIFT_INDEX[$b['shift_or_periods']] ?? 0));

        $results[] = [
            'id' => (int) $teacher['id'],
            'full_name' => $teacher['full_name'],
            'email' => $teacher['email'],
            'expertise' => $teacher['expertise'],
            'level' => $level,
            'level_label' => $meta[$level]['label'],
            'level_color' => $meta[$level]['color'],
            'reason' => $reason,
            'weekly_load' => count($all),
            'day_slots' => array_map(static fn (array $row): array => [
                'label' => shift_short($row['shift_or_periods']),
                'branch' => $row['branch_name'],
                'color' => branch_color((int) $row['branch_id']),
            ], $sameDay),
            'score' => $score,
        ];
    }

    usort($results, static function (array $a, array $b): int {
        return [$b['score'], $a['weekly_load'], $a['full_name']]
            <=> [$a['score'], $b['weekly_load'], $b['full_name']];
    });

    return $results;
}
