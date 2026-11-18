<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['action'] ?? '') === 'logout') {
        unset($_SESSION['teacher_id']);
        session_regenerate_id(true);
        flash('info', 'Bạn đã đăng xuất khỏi cổng giảng viên.');
        redirect('teacher_portal.php');
    }

    if (($_POST['action'] ?? '') === 'login') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $teacherRow = null;

        if ($email !== '') {
            $stmt = db()->prepare('SELECT id, full_name FROM teachers WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $teacherRow = $stmt->fetch() ?: null;
        } elseif ($teacherId > 0) {
            $stmt = db()->prepare('SELECT id, full_name FROM teachers WHERE id = ? LIMIT 1');
            $stmt->execute([$teacherId]);
            $teacherRow = $stmt->fetch() ?: null;
        }

        if ($teacherRow === null) {
            flash('danger', 'Không tìm thấy giảng viên. Vui lòng chọn từ danh sách hoặc kiểm tra lại email.');
        } else {
            session_regenerate_id(true);
            $_SESSION['teacher_id'] = (int) $teacherRow['id'];
            flash('success', 'Xin chào ' . $teacherRow['full_name'] . '!');
        }

        redirect('teacher_portal.php');
    }
}

$teacher = null;
$teacherSessionId = (int) ($_SESSION['teacher_id'] ?? 0);
if ($teacherSessionId > 0) {
    $stmt = db()->prepare('SELECT id, full_name, email, phone, expertise FROM teachers WHERE id = ?');
    $stmt->execute([$teacherSessionId]);
    $teacher = $stmt->fetch() ?: null;

    if ($teacher === null) {
        unset($_SESSION['teacher_id']);
    }
}

$title = 'Cổng thông tin giảng viên';
$active = 'portal';
require __DIR__ . '/../partials/header.php';

if ($teacher === null):
?>
    <div class="row justify-content-center py-4">
        <div class="col-md-7 col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="display-6 text-primary"><i class="bi bi-person-badge"></i></div>
                        <h1 class="h5 mt-2 mb-1">Đăng nhập cổng giảng viên</h1>
                        <p class="text-secondary small mb-0">Tra cứu thời khóa biểu cá nhân theo tuần.</p>
                    </div>
                    <form method="post" action="teacher_portal.php">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="login">

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Chọn giảng viên</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">— Chọn từ danh sách —</option>
                                <?php foreach (all_teachers() as $row): ?>
                                    <option value="<?= (int) $row['id'] ?>">
                                        <?= e($row['full_name']) ?> — <?= e($row['email']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="text-center text-secondary small my-2">hoặc</div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email giảng viên</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="giangvien@huit.edu.vn">
                            <div class="form-text">Nhập email nếu bạn không muốn chọn từ danh sách.</div>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
    require __DIR__ . '/../partials/footer.php';
    exit;
endif;

$stmt = db()->prepare(
    'SELECT s.id, s.day_of_week, s.shift_or_periods, s.subject_name, s.room,
            b.id AS branch_id, b.branch_name, b.address
     FROM schedules s
     JOIN branches b ON b.id = s.branch_id
     WHERE s.teacher_id = ?'
);
$stmt->execute([(int) $teacher['id']]);
$rows = $stmt->fetchAll();

$grid = [];
$branchSummary = [];
foreach ($rows as $row) {
    $grid[(int) $row['day_of_week']][$row['shift_or_periods']] = $row;
    $branchId = (int) $row['branch_id'];
    $branchSummary[$branchId] ??= [
        'branch_name' => $row['branch_name'],
        'address' => $row['address'],
        'count' => 0,
    ];
    $branchSummary[$branchId]['count']++;
}
ksort($branchSummary);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Thời khóa biểu của <?= e($teacher['full_name']) ?></h1>
        <p class="text-secondary mb-0">
            <?= e($teacher['expertise']) ?> · <?= e($teacher['email']) ?> · <?= e($teacher['phone']) ?>
        </p>
    </div>
    <form method="post" action="teacher_portal.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-outline-secondary" type="submit">
            <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
        </button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 timetable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 130px;">Ca / Tiết</th>
                        <?php foreach (DAYS as $label): ?>
                            <th><?= e($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (SHIFTS as $shift => $meta): ?>
                    <tr>
                        <th class="table-light">
                            <div class="fw-semibold"><?= e($meta['label']) ?></div>
                            <div class="small text-secondary"><?= e($meta['period']) ?> · <?= e($meta['range']) ?></div>
                        </th>
                        <?php foreach (DAYS as $day => $dayLabel): ?>
                            <?php $cell = $grid[$day][$shift] ?? null; ?>
                            <td>
                                <?php if ($cell !== null): ?>
                                    <div class="cell-subject mb-1"><?= e($cell['subject_name']) ?></div>
                                    <div class="small text-secondary mb-2">
                                        <i class="bi bi-door-open me-1"></i><?= e($cell['room']) ?>
                                    </div>
                                    <?= branch_badge(['id' => $cell['branch_id'], 'branch_name' => $cell['branch_name']]) ?>
                                <?php else: ?>
                                    <span class="text-secondary small">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">Tổng số ca dạy trong tuần</div>
                <div class="display-5 fw-semibold"><?= count($rows) ?></div>
                <div class="small text-secondary">
                    Trải trên <?= count(array_unique(array_column($rows, 'day_of_week'))) ?> ngày
                    và <?= count($branchSummary) ?> cơ sở.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-geo-alt me-1 text-danger"></i>Các cơ sở cần đến</h2>
                <?php if ($branchSummary === []): ?>
                    <p class="text-secondary small mb-0">Bạn chưa có ca dạy nào trong tuần này.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <?php foreach ($branchSummary as $branchId => $summary): ?>
                            <li class="d-flex flex-wrap align-items-center gap-2">
                                <?= branch_badge(['id' => $branchId, 'branch_name' => $summary['branch_name']]) ?>
                                <span class="small text-secondary"><?= e($summary['address']) ?></span>
                                <span class="badge text-bg-light border ms-auto"><?= (int) $summary['count'] ?> ca</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
