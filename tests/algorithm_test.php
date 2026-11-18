<?php

declare(strict_types=1);

/**
 * Kiểm tra nhanh thuật toán gợi ý giảng viên.
 * Dữ liệu thử được tạo trong một transaction và luôn rollback.
 * Chạy: php tests/algorithm_test.php
 */

require_once __DIR__ . '/../config/helpers.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$branches = all_branches();
if (count($branches) < 2) {
    fwrite(STDERR, "Seed the database first: php database/seeder.php\n");
    exit(1);
}

$branchOne = (int) $branches[0]['id'];
$branchTwo = (int) $branches[1]['id'];
$expertise = EXPERTISES[0];
$day = 3;
$caOne = 'Sáng - Ca 1 (Tiết 1-3)';
$caTwo = 'Sáng - Ca 2 (Tiết 4-6)';
$caFour = 'Chiều - Ca 4 (Tiết 10-12)';
$testRoom = 'TEST.999';

$pdo = db();
$pdo->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));
    $insertTeacher = $pdo->prepare(
        'INSERT INTO teachers (full_name, email, phone, expertise) VALUES (?, ?, ?, ?)'
    );

    $teacherIds = [];
    foreach (['A' => 'adjacent-gap', 'B' => 'free-day', 'C' => 'adjacent', 'D' => 'room-hold'] as $key => $suffixName) {
        $insertTeacher->execute([
            'Test Teacher ' . $key,
            "test-{$suffixName}-{$suffix}@example.test",
            '0900000000',
            $expertise,
        ]);
        $teacherIds[$key] = (int) $pdo->lastInsertId();
    }

    $insertSchedule = $pdo->prepare(
        'INSERT INTO schedules (teacher_id, branch_id, subject_name, room, day_of_week, shift_or_periods)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    // A dạy Ca 4 cùng cơ sở, nhận Ca 2 sẽ tạo khoảng trống chết ở Ca 3.
    $insertSchedule->execute([$teacherIds['A'], $branchOne, 'Test Subject', 'T.104', $day, $caFour]);

    // C dạy Ca 1 cùng cơ sở, nhận Ca 2 là liền ca tối ưu.
    $insertSchedule->execute([$teacherIds['C'], $branchOne, 'Test Subject', 'T.101', $day, $caOne]);

    // D giữ phòng TEST.999 để kiểm tra cảnh báo trùng phòng.
    $insertSchedule->execute([$teacherIds['D'], $branchOne, 'Room Holder', $testRoom, $day, $caTwo]);

    // B cố ý không có lịch trong ngày.

    $caTwoSuggestions = suggest_teachers($expertise, $branchOne, $day, $caTwo);
    $byId = array_column($caTwoSuggestions, null, 'id');

    check(!isset($byId[$teacherIds['A']]) || $byId[$teacherIds['A']]['level'] === 'warning', 'A phải bị cảnh báo khoảng trống chết.');
    check(isset($byId[$teacherIds['C']]), 'C phải xuất hiện trong gợi ý Ca 2.');
    check($byId[$teacherIds['C']]['level'] === 'optimal', 'C phải đạt mức tối ưu vì dạy liền ca cùng cơ sở.');
    check(isset($byId[$teacherIds['B']]), 'B phải xuất hiện trong gợi ý Ca 2.');
    check($byId[$teacherIds['B']]['level'] === 'normal', 'B phải ở mức bình thường vì trống lịch cả ngày.');
    check(!isset($byId[$teacherIds['D']]), 'D phải bị loại vì đã dạy đúng Ca 2.');

    $caFourSuggestions = suggest_teachers($expertise, $branchOne, $day, $caFour);
    check(!in_array($teacherIds['A'], array_column($caFourSuggestions, 'id'), true), 'A phải bị loại khỏi Ca 4 vì trùng lịch.');

    $conflict = room_conflict($branchOne, $testRoom, $day, $caTwo);
    check($conflict !== null && $conflict['subject_name'] === 'Room Holder', 'Phải phát hiện trùng phòng.');

    // Cảnh báo di chuyển khác cơ sở: C dạy Ca 1 ở cơ sở 1, nhận Ca 2 ở cơ sở 2.
    $otherBranchSuggestions = suggest_teachers($expertise, $branchTwo, $day, $caTwo);
    $otherById = array_column($otherBranchSuggestions, null, 'id');
    check($otherById[$teacherIds['C']]['level'] === 'warning', 'C phải bị cảnh báo di chuyển khi sang cơ sở khác.');

    echo "OK: suggestion algorithm assertions passed.\n";
} finally {
    $pdo->rollBack();
}
