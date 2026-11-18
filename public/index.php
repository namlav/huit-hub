<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

$branchId = (int) ($_GET['branch_id'] ?? 0);
$teacherId = (int) ($_GET['teacher_id'] ?? 0);
$day = (int) ($_GET['day_of_week'] ?? 0);

$query = http_build_query(array_filter([
    'branch_id' => $branchId ?: null,
    'teacher_id' => $teacherId ?: null,
    'day_of_week' => $day ?: null,
]));
$query = $query === '' ? '' : '?' . $query;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = db()->prepare('DELETE FROM schedules WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $stmt->rowCount() > 0
            ? flash('success', 'Đã xóa lịch dạy khỏi hệ thống.')
            : flash('warning', 'Không tìm thấy lịch dạy cần xóa.');
    }

    redirect('index.php' . $query);
}

$sql = 'SELECT s.id, s.subject_name, s.room, s.day_of_week, s.shift_or_periods,
               s.teacher_id, s.branch_id,
               t.full_name, t.expertise,
               b.branch_name, b.address
        FROM schedules s
        JOIN teachers t ON t.id = s.teacher_id
        JOIN branches b ON b.id = s.branch_id
        WHERE 1 = 1';
$params = [];

if ($branchId > 0) {
    $sql .= ' AND s.branch_id = ?';
    $params[] = $branchId;
}
if ($teacherId > 0) {
    $sql .= ' AND s.teacher_id = ?';
    $params[] = $teacherId;
}
if ($day > 0) {
    $sql .= ' AND s.day_of_week = ?';
    $params[] = $day;
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

usort($schedules, static function (array $a, array $b): int {
    return [(int) $a['day_of_week'], SHIFT_INDEX[$a['shift_or_periods']] ?? 0, $a['branch_name'], $a['full_name']]
        <=> [(int) $b['day_of_week'], SHIFT_INDEX[$b['shift_or_periods']] ?? 0, $b['branch_name'], $b['full_name']];
});

$branches = all_branches();
$teachers = all_teachers();

$branchCounts = [];
foreach ($branches as $branch) {
    $branchCounts[(int) $branch['id']] = 0;
}
$teacherCounts = [];
foreach ($schedules as $row) {
    $branchCounts[(int) $row['branch_id']]++;
    $teacherIdKey = (int) $row['teacher_id'];
    $teacherCounts[$teacherIdKey] ??= ['name' => $row['full_name'], 'count' => 0];
    $teacherCounts[$teacherIdKey]['count']++;
}
uasort($teacherCounts, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
$maxTeacherLoad = max([1, ...array_column($teacherCounts, 'count')]);
$maxBranchLoad = max([1, ...array_values($branchCounts)]);

$colorHex = [
    'primary' => '#0d6efd',
    'success' => '#198754',
    'danger' => '#dc3545',
    'info' => '#0dcaf0',
    'warning' => '#ffc107',
    'dark' => '#212529',
];
$chartLabels = array_map(static fn (array $branch): string => $branch['branch_name'], $branches);
$chartData = array_map(static fn (array $branch): int => $branchCounts[(int) $branch['id']], $branches);
$chartColors = array_map(static fn (array $branch): string => $colorHex[branch_color((int) $branch['id'])], $branches);

$title = 'Tổng quan lịch giảng dạy';
$active = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Bảng điều khiển lịch giảng dạy</h1>
        <p class="text-secondary mb-0">Theo dõi, lọc và cân đối lịch dạy giữa các cơ sở của HUIT.</p>
    </div>
    <a class="btn btn-primary" href="assign.php">
        <i class="bi bi-plus-lg me-1"></i>Phân lịch thông minh
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="filter-branch" class="form-label small text-secondary mb-1">Cơ sở</label>
                <select class="form-select" id="filter-branch" name="branch_id">
                    <option value="">Tất cả cơ sở</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= (int) $branch['id'] ?>" <?= $branchId === (int) $branch['id'] ? 'selected' : '' ?>>
                            <?= e($branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="filter-teacher" class="form-label small text-secondary mb-1">Giảng viên</label>
                <select class="form-select" id="filter-teacher" name="teacher_id">
                    <option value="">Tất cả giảng viên</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id'] ?>" <?= $teacherId === (int) $teacher['id'] ? 'selected' : '' ?>>
                            <?= e($teacher['full_name']) ?> — <?= e($teacher['expertise']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-day" class="form-label small text-secondary mb-1">Thứ</label>
                <select class="form-select" id="filter-day" name="day_of_week">
                    <option value="">Cả tuần</option>
                    <?php foreach (DAYS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $day === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-dark flex-fill" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-outline-secondary" href="index.php" title="Bỏ bộ lọc"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-bar-chart-line me-1 text-primary"></i>Số ca dạy theo cơ sở</h2>
                <div style="height: 240px;">
                    <canvas id="branchChart" aria-label="Biểu đồ số ca dạy theo cơ sở" role="img"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-people me-1 text-success"></i>Khối lượng giảng dạy của giảng viên</h2>
                <?php if ($teacherCounts === []): ?>
                    <p class="text-secondary small mb-0">Chưa có dữ liệu phù hợp bộ lọc.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2" style="max-height: 240px; overflow-y: auto;">
                        <?php foreach ($teacherCounts as $stat): ?>
                            <div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-truncate me-2"><?= e($stat['name']) ?></span>
                                    <span class="fw-semibold"><?= (int) $stat['count'] ?> ca</span>
                                </div>
                                <div class="progress" style="height: 6px;" role="progressbar"
                                     aria-valuenow="<?= (int) $stat['count'] ?>" aria-valuemin="0" aria-valuemax="<?= $maxTeacherLoad ?>">
                                    <div class="progress-bar bg-success" style="width: <?= (int) round($stat['count'] / $maxTeacherLoad * 100) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0"><i class="bi bi-calendar3 me-1"></i>Danh sách lịch dạy</h2>
            <span class="badge text-bg-light border"><?= count($schedules) ?> ca</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Thứ</th>
                        <th>Ca / Tiết</th>
                        <th>Môn học</th>
                        <th>Giảng viên</th>
                        <th>Cơ sở</th>
                        <th>Phòng</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($schedules === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Không có lịch dạy nào khớp bộ lọc.
                            <a href="assign.php">Thêm lịch dạy mới</a>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($schedules as $row): ?>
                    <tr>
                        <td class="fw-semibold text-nowrap"><?= e(DAYS[(int) $row['day_of_week']] ?? $row['day_of_week']) ?></td>
                        <td class="text-nowrap">
                            <div class="small text-secondary"><?= e(SHIFTS[$row['shift_or_periods']]['period'] ?? '') ?></div>
                            <?= e(shift_short($row['shift_or_periods'])) ?>
                        </td>
                        <td class="fw-semibold"><?= e($row['subject_name']) ?></td>
                        <td>
                            <div><?= e($row['full_name']) ?></div>
                            <div class="small text-secondary"><?= e($row['expertise']) ?></div>
                        </td>
                        <td><?= branch_badge(['id' => $row['branch_id'], 'branch_name' => $row['branch_name']]) ?></td>
                        <td><span class="badge text-bg-light border"><?= e($row['room']) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="assign.php?id=<?= (int) $row['id'] ?>" title="Sửa lịch dạy">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form method="post" action="index.php<?= e($query) ?>" class="d-inline"
                                  onsubmit="return confirm('Xóa lịch dạy môn <?= e(addslashes($row['subject_name'])) ?>?');">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Xóa lịch dạy">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const branchCanvas = document.getElementById('branchChart');
    if (branchCanvas && window.Chart) {
        new Chart(branchCanvas, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    label: 'Số ca dạy',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: <?= json_encode($chartColors) ?>,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
