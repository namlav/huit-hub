<?php

declare(strict_types=1);

/**
 * Tạo schema và dữ liệu mẫu cho HUIT Schedule Hub.
 * Chạy: php database/seeder.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$database = env('DB_DATABASE', 'huit_db');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD');

if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
    fwrite(STDERR, "Invalid DB_DATABASE name: {$database}\n");
    exit(1);
}

$server = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$pdo = db();

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('DROP TABLE IF EXISTS schedules');
$pdo->exec('DROP TABLE IF EXISTS teachers');
$pdo->exec('DROP TABLE IF EXISTS branches');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$pdo->exec(
    'CREATE TABLE branches (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        branch_name VARCHAR(100) NOT NULL,
        address VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$pdo->exec(
    'CREATE TABLE teachers (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        expertise VARCHAR(100) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_teachers_email (email),
        KEY idx_teachers_expertise (expertise)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$pdo->exec(
    'CREATE TABLE schedules (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        teacher_id INT UNSIGNED NOT NULL,
        branch_id INT UNSIGNED NOT NULL,
        subject_name VARCHAR(150) NOT NULL,
        room VARCHAR(50) NOT NULL,
        day_of_week TINYINT UNSIGNED NOT NULL,
        shift_or_periods VARCHAR(50) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_teacher_slot (teacher_id, day_of_week, shift_or_periods),
        UNIQUE KEY uniq_room_slot (branch_id, room, day_of_week, shift_or_periods),
        KEY idx_schedules_branch (branch_id, day_of_week),
        CONSTRAINT fk_schedules_teacher FOREIGN KEY (teacher_id) REFERENCES teachers (id)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_schedules_branch FOREIGN KEY (branch_id) REFERENCES branches (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$faker = Faker\Factory::create('vi_VN');
$faker->seed(20261118);

$branches = [
    ['Cơ sở Tân Phú', '140 Lê Trọng Tấn, P. Tây Thạnh, Q. Tân Phú, TP.HCM'],
    ['Cơ sở Sơn Kỳ', '76 Sơn Kỳ, P. Tân Quý, Q. Tân Phú, TP.HCM'],
    ['Cơ sở Tân Bình', '98 Lý Thường Kiệt, P. 7, Q. Tân Bình, TP.HCM'],
    ['Trung tâm Thực hành', '8 Nguyễn Ảnh Thủ, P. Hiệp Thành, Q. 12, TP.HCM'],
];

$insertBranch = $pdo->prepare('INSERT INTO branches (branch_name, address) VALUES (?, ?)');
foreach ($branches as $branch) {
    $insertBranch->execute($branch);
}

$branchRows = $pdo->query('SELECT id, branch_name FROM branches ORDER BY id')->fetchAll();

$subjects = [
    'Lập trình Web' => ['Lập trình PHP', 'Thiết kế Web với HTML/CSS', 'Phát triển ứng dụng ReactJS'],
    'Mạng máy tính' => ['Quản trị mạng', 'An toàn thông tin', 'Hệ điều hành Linux'],
    'Trí tuệ nhân tạo' => ['Machine Learning', 'Xử lý ngôn ngữ tự nhiên', 'Trí tuệ nhân tạo ứng dụng'],
    'Cơ sở dữ liệu' => ['Hệ quản trị cơ sở dữ liệu', 'Thiết kế cơ sở dữ liệu', 'SQL nâng cao'],
];

$groupSizes = [
    'Lập trình Web' => 5,
    'Mạng máy tính' => 5,
    'Trí tuệ nhân tạo' => 4,
    'Cơ sở dữ liệu' => 4,
];

$insertTeacher = $pdo->prepare(
    'INSERT INTO teachers (full_name, email, phone, expertise) VALUES (?, ?, ?, ?)'
);
foreach ($groupSizes as $expertise => $size) {
    for ($i = 0; $i < $size; $i++) {
        $insertTeacher->execute([
            $faker->name(),
            strtolower($faker->unique()->safeEmail()),
            $faker->numerify('09########'),
            $expertise,
        ]);
    }
}

$teacherRows = $pdo->query('SELECT id, expertise FROM teachers')->fetchAll();
$shiftKeys = array_keys(SHIFTS);

$insertSchedule = $pdo->prepare(
    'INSERT INTO schedules (teacher_id, branch_id, subject_name, room, day_of_week, shift_or_periods)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$usedTeacherSlots = [];
$usedRoomSlots = [];
$scheduleCount = 0;
$attempts = 0;

while ($scheduleCount < 36 && $attempts < 5000) {
    $attempts++;

    $teacher = $faker->randomElement($teacherRows);
    $branch = $branchRows[$scheduleCount % count($branchRows)];
    $day = 2 + ($scheduleCount % count(DAYS));
    $shift = $shiftKeys[$scheduleCount % count($shiftKeys)];
    $room = sprintf('A.%d%02d', $faker->numberBetween(1, 4), $faker->numberBetween(1, 12));

    $teacherKey = $teacher['id'] . '|' . $day . '|' . $shift;
    $roomKey = $branch['id'] . '|' . $room . '|' . $day . '|' . $shift;

    if (isset($usedTeacherSlots[$teacherKey]) || isset($usedRoomSlots[$roomKey])) {
        continue;
    }

    $insertSchedule->execute([
        $teacher['id'],
        $branch['id'],
        $faker->randomElement($subjects[$teacher['expertise']]),
        $room,
        $day,
        $shift,
    ]);

    $usedTeacherSlots[$teacherKey] = true;
    $usedRoomSlots[$roomKey] = true;
    $scheduleCount++;
}

printf(
    "Seeded database `%s`: %d branches, %d teachers, %d schedules.\n",
    $database,
    count($branchRows),
    count($teacherRows),
    $scheduleCount
);
