<?php /** @var string $title */ /** @var string $active */ ?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? APP_NAME) ?> · <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6fb; }
        .navbar-brand { letter-spacing: .2px; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(16, 24, 40, .08); }
        .timetable td, .timetable th { vertical-align: top; min-width: 140px; }
        .timetable .cell-subject { font-weight: 600; line-height: 1.25; }
        .suggestion-card { border-left: 4px solid transparent; }
        .suggestion-card.optimal { border-left-color: #198754; }
        .suggestion-card.normal { border-left-color: #0d6efd; }
        .suggestion-card.warning { border-left-color: #ffc107; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand fw-semibold" href="index.php">
            <i class="bi bi-mortarboard-fill me-1"></i>HUIT Schedule Hub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-grid-1x2 me-1"></i>Tổng quan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'assign' ? 'active' : '' ?>" href="assign.php">
                        <i class="bi bi-magic me-1"></i>Phân lịch thông minh
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'portal' ? 'active' : '' ?>" href="teacher_portal.php">
                        <i class="bi bi-person-badge me-1"></i>Cổng giảng viên
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container-fluid px-lg-5 py-4">
    <?php foreach (flashes() as $message): ?>
        <div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($message['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endforeach; ?>
