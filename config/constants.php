<?php

declare(strict_types=1);

const APP_NAME = 'HUIT Schedule Hub';

/** Thứ 2 (2) đến Thứ 7 (7) – lưu số để sắp xếp ổn định. */
const DAYS = [
    2 => 'Thứ 2',
    3 => 'Thứ 3',
    4 => 'Thứ 4',
    5 => 'Thứ 5',
    6 => 'Thứ 6',
    7 => 'Thứ 7',
];

/** Bốn ca chuẩn dùng xuyên suốt hệ thống để kiểm tra trùng lịch. */
const SHIFTS = [
    'Sáng - Ca 1 (Tiết 1-3)' => ['label' => 'Ca 1', 'period' => 'Sáng', 'range' => 'Tiết 1-3'],
    'Sáng - Ca 2 (Tiết 4-6)' => ['label' => 'Ca 2', 'period' => 'Sáng', 'range' => 'Tiết 4-6'],
    'Chiều - Ca 3 (Tiết 7-9)' => ['label' => 'Ca 3', 'period' => 'Chiều', 'range' => 'Tiết 7-9'],
    'Chiều - Ca 4 (Tiết 10-12)' => ['label' => 'Ca 4', 'period' => 'Chiều', 'range' => 'Tiết 10-12'],
];

const SHIFT_INDEX = [
    'Sáng - Ca 1 (Tiết 1-3)' => 1,
    'Sáng - Ca 2 (Tiết 4-6)' => 2,
    'Chiều - Ca 3 (Tiết 7-9)' => 3,
    'Chiều - Ca 4 (Tiết 10-12)' => 4,
];

const EXPERTISES = [
    'Lập trình Web',
    'Mạng máy tính',
    'Trí tuệ nhân tạo',
    'Cơ sở dữ liệu',
];

/** Màu badge cố định theo cơ sở để giảng viên nhận diện nhanh. */
const BRANCH_COLORS = ['primary', 'success', 'danger', 'info', 'warning', 'dark'];
