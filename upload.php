<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';
$MAX_FILES = 5;

// --- CSRF ---
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['error' => 'Ошибка безопасности. Обновите страницу.']));
}

// --- Согласия ---
if (empty($_POST['consent_privacy']) || empty($_POST['consent_pd'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Не отмечены обязательные согласия.']));
}

// --- Поля ---
$fullname = trim($_POST['fullname'] ?? '');
$grade = (int)($_POST['grade'] ?? 0);
$weekday = $_POST['weekday'] ?? '';
$timeFrom = $_POST['time_from'] ?? '';
$timeTo = $_POST['time_to'] ?? '';

if ($fullname === '' || $grade < 5 || $grade > 11 || $weekday === '' || $timeFrom === '' || $timeTo === '') {
    http_response_code(400);
    exit(json_encode(['error' => 'Заполните все поля.']));
}

$allowedWeekdays = ['monday','tuesday','wednesday','thursday','friday'];
if (!in_array($weekday, $allowedWeekdays, true)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Некорректный день недели.']));
}

// --- Файлы ---
if (empty($_FILES['files']) || !is_array($_FILES['files']['name'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Файлы не загружены.']));
}

$filesCount = count($_FILES['files']['name']);
if ($filesCount === 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'Прикрепите хотя бы один файл.']));
}
if ($filesCount > $MAX_FILES) {
    http_response_code(400);
    exit(json_encode(['error' => "Можно загрузить не более $MAX_FILES файлов."]));
}

// --- Папка назначения ---
$dir = __DIR__ . "/uploads/$grade/$weekday/";
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$savedFiles = [];

for ($i = 0; $i < $filesCount; $i++) {
    $error = $_FILES['files']['error'][$i];
    $tmpName = $_FILES['files']['tmp_name'][$i];
    $origName = $_FILES['files']['name'][$i];
    $size = $_FILES['files']['size'][$i];

    if ($error !== UPLOAD_ERR_OK) {
        continue; // пропускаем битый файл
    }

    if ($size > $config['max_file_size']) {
        // Удаляем уже сохранённые файлы и выходим
        foreach ($savedFiles as $sf) { @unlink($sf['path']); }
        http_response_code(400);
        exit(json_encode(['error' => "Файл «$origName» больше 10 МБ."]));
    }

    $mime = mime_content_type($tmpName);
    if (!in_array($mime, $config['allowed_mimes'], true)) {
        foreach ($savedFiles as $sf) { @unlink($sf['path']); }
        http_response_code(400);
        exit(json_encode(['error' => "Файл «$origName» — недопустимый формат."]));
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext);
    $baseName = time() . '_' . bin2hex(random_bytes(4));
    $newName = $baseName . '.' . $safeExt;

    $target = $dir . $newName;
    if (!move_uploaded_file($tmpName, $target)) {
        foreach ($savedFiles as $sf) { @unlink($sf['path']); }
        http_response_code(500);
        exit(json_encode(['error' => 'Не удалось сохранить файл.']));
    }

    $savedFiles[] = [
        'path' => $target,
        'base' => $baseName,
        'name' => $newName,
        'original' => $origName,
    ];
}

// --- Общий JSON с метаданными ---
$batchId = 'batch_' . time() . '_' . bin2hex(random_bytes(4));
$meta = [
    'batch_id' => $batchId,
    'fullname' => $fullname,
    'grade'    => $grade,
    'weekday'  => $weekday,
    'time_from'=> $timeFrom,
    'time_to'  => $timeTo,
    'uploaded' => date('Y-m-d H:i:s'),
    'files'    => array_map(fn($f) => [
        'name' => $f['name'],
        'original' => $f['original'],
    ], $savedFiles),
];

// Один .json на всю партию — чтобы учитель видел все файлы вместе
file_put_contents($dir . $batchId . '.json', json_encode($meta, JSON_UNESCAPED_UNICODE));

echo json_encode([
    'ok' => true,
    'message' => 'Ваш конспект отправлен!',
    'count' => count($savedFiles),
]);