<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

$editId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = null;

if ($editId > 0) {
    $stmt = db()->prepare(
        'SELECT s.*, t.full_name AS teacher_name, t.expertise
         FROM schedules s
         JOIN teachers t ON t.id = s.teacher_id
         WHERE s.id = ?'
    );
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;

    if ($editing === null) {
        flash('warning', 'Không tìm thấy lịch dạy cần sửa.');
        redirect('index.php');
    }
}

$form = [
    'subject_name' => $editing['subject_name'] ?? '',
    'expertise' => $editing['expertise'] ?? '',
    'branch_id' => (string) ($editing['branch_id'] ?? ''),
    'room' => $editing['room'] ?? '',
    'day_of_week' => (string) ($editing['day_of_week'] ?? ''),
    'shift_or_periods' => $editing['shift_or_periods'] ?? '',
    'teacher_id' => (string) ($editing['teacher_id'] ?? ''),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $branchInput = (int) $form['branch_id'];
    $dayInput = (int) $form['day_of_week'];
    $teacherInput = (int) $form['teacher_id'];

    if ($form['subject_name'] === '') {
        $errors[] = 'Vui lòng nhập tên môn học.';
    } elseif (mb_strlen($form['subject_name']) > 150) {
        $errors[] = 'Tên môn học không được vượt quá 150 ký tự.';
    }

    if (!in_array($form['expertise'], EXPERTISES, true)) {
        $errors[] = 'Vui lòng chọn nhóm chuyên môn hợp lệ.';
    }

    $branchExists = false;
    foreach (all_branches() as $branch) {
        if ((int) $branch['id'] === $branchInput) {
            $branchExists = true;
            break;
        }
    }
    if (!$branchExists) {
        $errors[] = 'Vui lòng chọn cơ sở hợp lệ.';
    }

    if ($form['room'] === '') {
        $errors[] = 'Vui lòng nhập phòng học.';
    } elseif (mb_strlen($form['room']) > 50) {
        $errors[] = 'Tên phòng không được vượt quá 50 ký tự.';
    }

    if (!isset(DAYS[$dayInput])) {
        $errors[] = 'Vui lòng chọn thứ trong tuần.';
    }

    if (!isset(SHIFTS[$form['shift_or_periods']])) {
        $errors[] = 'Vui lòng chọn ca dạy chuẩn.';
    }

    $teacher = null;
    if ($teacherInput <= 0) {
        $errors[] = 'Vui lòng chọn giảng viên từ danh sách gợi ý.';
    } else {
        $stmt = db()->prepare('SELECT id, full_name, expertise FROM teachers WHERE id = ?');
        $stmt->execute([$teacherInput]);
        $teacher = $stmt->fetch() ?: null;

        if ($teacher === null) {
            $errors[] = 'Giảng viên không tồn tại.';
        } elseif ($teacher['expertise'] !== $form['expertise']) {
            $errors[] = 'Giảng viên này không thuộc nhóm chuyên môn đã chọn.';
        }
    }

    if ($errors === [] && $teacher !== null) {
        $sql = 'SELECT id FROM schedules
                WHERE teacher_id = ? AND day_of_week = ? AND shift_or_periods = ?';
        $params = [$teacherInput, $dayInput, $form['shift_or_periods']];

        if ($editId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $editId;
        }

        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'Giảng viên đã có lịch dạy đúng thứ và ca này.';
        }

        $roomConflict = room_conflict($branchInput, $form['room'], $dayInput, $form['shift_or_periods'], $editId ?: null);
        if ($roomConflict !== null) {
            $errors[] = sprintf(
                'Phòng %s đã có lớp "%s" của giảng viên %s trong cùng thứ và ca.',
                $form['room'],
                $roomConflict['subject_name'],
                $roomConflict['full_name']
            );
        }
    }

    if ($errors === []) {
        if ($editId > 0) {
            $stmt = db()->prepare(
                'UPDATE schedules
                 SET teacher_id = ?, branch_id = ?, subject_name = ?, room = ?, day_of_week = ?, shift_or_periods = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $teacherInput,
                $branchInput,
                $form['subject_name'],
                $form['room'],
                $dayInput,
                $form['shift_or_periods'],
                $editId,
            ]);
            flash('success', 'Đã cập nhật lịch dạy.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO schedules (teacher_id, branch_id, subject_name, room, day_of_week, shift_or_periods)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $teacherInput,
                $branchInput,
                $form['subject_name'],
                $form['room'],
                $dayInput,
                $form['shift_or_periods'],
            ]);
            flash('success', 'Đã xếp lịch dạy thành công.');
        }

        redirect('index.php');
    }
}

$selectedTeacherName = '';
if ((int) $form['teacher_id'] > 0) {
    foreach (all_teachers() as $teacher) {
        if ((int) $teacher['id'] === (int) $form['teacher_id']) {
            $selectedTeacherName = $teacher['full_name'];
            break;
        }
    }
}

$title = $editId > 0 ? 'Sửa lịch dạy' : 'Phân lịch thông minh';
$active = 'assign';
require __DIR__ . '/../partials/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><?= $editId > 0 ? 'Sửa lịch dạy #' . (int) $editId : 'Phân lịch giảng dạy thông minh' ?></h1>
        <p class="text-secondary mb-0">Hệ thống tự lọc giảng viên theo chuyên môn, ca trống và mức tối ưu di chuyển.</p>
    </div>
    <a class="btn btn-outline-secondary" href="index.php"><i class="bi bi-arrow-left me-1"></i>Về tổng quan</a>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Không thể lưu lịch dạy:</div>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="post" action="assign.php" id="assignForm">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $editId ?: '' ?>">

            <div class="row g-3">
                <div class="col-md-5">
                    <label for="subject_name" class="form-label">Tên môn học <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="subject_name" name="subject_name" maxlength="150"
                           value="<?= e($form['subject_name']) ?>" placeholder="VD: Lập trình PHP" required>
                </div>
                <div class="col-md-3">
                    <label for="expertise" class="form-label">Chuyên môn <span class="text-danger">*</span></label>
                    <select class="form-select" id="expertise" name="expertise" required>
                        <option value="">— Chọn —</option>
                        <?php foreach (EXPERTISES as $expertise): ?>
                            <option value="<?= e($expertise) ?>" <?= $form['expertise'] === $expertise ? 'selected' : '' ?>>
                                <?= e($expertise) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="branch_id" class="form-label">Cơ sở <span class="text-danger">*</span></label>
                    <select class="form-select" id="branch_id" name="branch_id" required>
                        <option value="">— Chọn cơ sở —</option>
                        <?php foreach (all_branches() as $branch): ?>
                            <option value="<?= (int) $branch['id'] ?>" <?= (string) $branch['id'] === $form['branch_id'] ? 'selected' : '' ?>>
                                <?= e($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="room" class="form-label">Phòng học <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="room" name="room" maxlength="50"
                           value="<?= e($form['room']) ?>" placeholder="VD: A.301" required>
                </div>
                <div class="col-md-3">
                    <label for="day_of_week" class="form-label">Thứ <span class="text-danger">*</span></label>
                    <select class="form-select" id="day_of_week" name="day_of_week" required>
                        <option value="">— Chọn —</option>
                        <?php foreach (DAYS as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $form['day_of_week'] === (string) $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="shift_or_periods" class="form-label">Ca / Tiết <span class="text-danger">*</span></label>
                    <select class="form-select" id="shift_or_periods" name="shift_or_periods" required>
                        <option value="">— Chọn ca —</option>
                        <?php foreach (array_keys(SHIFTS) as $shift): ?>
                            <option value="<?= e($shift) ?>" <?= $form['shift_or_periods'] === $shift ? 'selected' : '' ?>>
                                <?= e($shift) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid align-self-end">
                    <button type="button" class="btn btn-dark" id="suggestBtn">
                        <i class="bi bi-stars me-1"></i>Gợi ý GV
                    </button>
                </div>
            </div>

            <hr class="my-4">

            <input type="hidden" name="teacher_id" id="teacher_id" value="<?= e($form['teacher_id']) ?>">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h2 class="h6 mb-0"><i class="bi bi-person-check me-1 text-primary"></i>Giảng viên phù hợp</h2>
                <span class="small text-secondary">
                    Đang chọn: <span class="fw-semibold text-dark" id="selectedTeacher"><?= e($selectedTeacherName ?: 'chưa chọn') ?></span>
                </span>
            </div>

            <div id="roomWarning"></div>
            <div id="suggestions" class="row g-2">
                <div class="col-12 text-secondary small">
                    Điền đủ chuyên môn, cơ sở, thứ và ca rồi bấm <strong>Gợi ý GV</strong> để xem danh sách xếp hạng.
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i><?= $editId > 0 ? 'Cập nhật lịch dạy' : 'Lưu lịch dạy' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('assignForm');
    const box = document.getElementById('suggestions');
    const roomBox = document.getElementById('roomWarning');
    const teacherInput = document.getElementById('teacher_id');
    const selectedLabel = document.getElementById('selectedTeacher');
    const fields = ['expertise', 'branch_id', 'day_of_week', 'shift_or_periods', 'room'];

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[char]);

    const render = (data) => {
        roomBox.innerHTML = '';
        if (data.room_conflict) {
            const conflict = data.room_conflict;
            roomBox.innerHTML = `<div class="alert alert-warning py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Phòng <strong>${esc(conflict.room)}</strong> đã có lớp <strong>${esc(conflict.subject_name)}</strong>
                của GV ${esc(conflict.full_name)} trong cùng thứ và ca.
            </div>`;
        }

        if (!data.teachers || data.teachers.length === 0) {
            box.innerHTML = `<div class="col-12"><div class="alert alert-info mb-0">
                Không còn giảng viên phù hợp: tất cả đều đã có lịch trùng ca hoặc không đúng chuyên môn.
            </div></div>`;
            return;
        }

        box.innerHTML = data.teachers.map((teacher) => {
            const slots = teacher.day_slots.length
                ? teacher.day_slots.map((slot) => `<span class="badge bg-${esc(slot.color)} ${['warning', 'info'].includes(slot.color) ? 'text-dark' : 'text-white'} me-1">${esc(slot.label)} · ${esc(slot.branch)}</span>`).join('')
                : '<span class="text-secondary">Chưa có ca nào trong ngày</span>';

            return `<div class="col-md-6">
                <div class="card h-100 suggestion-card ${esc(teacher.level)}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">${esc(teacher.full_name)}</div>
                                <div class="small text-secondary">${esc(teacher.email)}</div>
                            </div>
                            <span class="badge bg-${esc(teacher.level_color)} ${['warning', 'info'].includes(teacher.level_color) ? 'text-dark' : 'text-white'}">${esc(teacher.level_label)}</span>
                        </div>
                        <p class="small my-2 mb-2">${esc(teacher.reason)}</p>
                        <div class="small mb-3">${slots}</div>
                        <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                            <span class="small text-secondary">Tải tuần: <strong>${teacher.weekly_load}</strong> ca</span>
                            <button type="button" class="btn btn-sm btn-primary"
                                    data-teacher-id="${teacher.id}" data-teacher-name="${esc(teacher.full_name)}">
                                Chọn &amp; lưu
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    };

    document.getElementById('suggestBtn').addEventListener('click', async () => {
        for (const name of fields.slice(0, 4)) {
            if (!form.elements[name].value) {
                form.elements[name].focus();
                box.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">Vui lòng chọn đủ chuyên môn, cơ sở, thứ và ca trước khi gợi ý.</div></div>';
                return;
            }
        }

        const params = new URLSearchParams();
        fields.forEach((name) => params.set(name, form.elements[name].value));
        box.innerHTML = '<div class="col-12 text-secondary small"><span class="spinner-border spinner-border-sm me-1"></span>Đang tìm giảng viên phù hợp…</div>';

        try {
            const response = await fetch('api_suggest.php?' + params.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            render(await response.json());
        } catch (error) {
            box.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Không tải được gợi ý. Vui lòng kiểm tra kết nối và thử lại.</div></div>';
        }
    });

    box.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-teacher-id]');
        if (!button) {
            return;
        }
        teacherInput.value = button.dataset.teacherId;
        selectedLabel.textContent = button.dataset.teacherName;
        form.requestSubmit();
    });
})();
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
