<?php
session_start();
if (empty($_SESSION['is_teacher'])) {
    header('Location: teacher-login.php');
    exit;
}

$baseDir = __DIR__ . '/uploads';

/**
 * Собираем список "партий" файлов (батчей).
 * Один батч = один ученик, одна отправка.
 */
function collectNotes(string $baseDir): array
{
    $result = [];
    if (!is_dir($baseDir)) return $result;

    foreach (scandir($baseDir) as $grade) {
        if ($grade === '.' || $grade === '..') continue;
        $gradePath = $baseDir . '/' . $grade;
        if (!is_dir($gradePath)) continue;

        foreach (scandir($gradePath) as $weekday) {
            if ($weekday === '.' || $weekday === '..') continue;
            $dayPath = $gradePath . '/' . $weekday;
            if (!is_dir($dayPath)) continue;

            foreach (scandir($dayPath) as $jsonFile) {
                if (pathinfo($jsonFile, PATHINFO_EXTENSION) !== 'json') continue;

                $metaPath = $dayPath . '/' . $jsonFile;
                $meta = json_decode(file_get_contents($metaPath), true);
                if (!$meta || empty($meta['files'])) continue;

                $files = [];
                foreach ($meta['files'] as $f) {
                    $filePath = $dayPath . '/' . $f['name'];
                    if (file_exists($filePath)) {
                        $files[] = [
                            'name' => $f['name'],
                            'original' => $f['original'] ?? $f['name'],
                            'path' => "uploads/$grade/$weekday/" . $f['name'],
                            'size' => filesize($filePath),
                        ];
                    }
                }

                if (empty($files)) continue;

                $result[$grade][$weekday][] = [
                    'meta' => $meta,
                    'files' => $files,
                    'mtime' => filemtime($metaPath),
                ];
            }
        }
    }

    foreach ($result as &$days) {
        foreach ($days as &$batches) {
            usort($batches, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        }
    }
    return $result;
}

$notes = collectNotes($baseDir);

$weekdayNames = [
    'monday' => 'Понедельник',
    'tuesday' => 'Вторник',
    'wednesday' => 'Среда',
    'thursday' => 'Четверг',
    'friday' => 'Пятница',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель учителя</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="dash-header">
    <h1>Присланные конспекты</h1>
    <a href="logout.php" class="btn-logout">Выйти</a>
</header>

<main class="dash">
    <?php if (empty($notes)): ?>
        <p class="empty">Пока нет ни одного конспекта.</p>
    <?php else: ?>
        <?php foreach ($notes as $grade => $days): ?>
            <section class="grade-block">
                <h2><?= (int)$grade ?> класс</h2>

                <?php foreach ($days as $weekday => $batches): ?>
                    <div class="day-block">
                        <h3><?= $weekdayNames[$weekday] ?? $weekday ?></h3>

                        <?php foreach ($batches as $batch): ?>
                            <?php
                                $m = $batch['meta'];
                                $name = htmlspecialchars($m['fullname'] ?? 'Без имени');
                                $from = htmlspecialchars($m['time_from'] ?? '');
                                $to = htmlspecialchars($m['time_to'] ?? '');
                                $uploaded = htmlspecialchars($m['uploaded'] ?? '');
                            ?>
                            <div class="batch">
                                <div class="batch-header">
                                    <strong><?= $name ?></strong>
                                    <span class="file-time"><?= $from ?>–<?= $to ?></span>
                                    <span class="file-uploaded">загружен: <?= $uploaded ?></span>
                                </div>
                                <ul class="file-list">
                                    <?php foreach ($batch['files'] as $f): ?>
                                        <li>
                                            <div class="file-info">
                                                <span>📎 <?= htmlspecialchars($f['original']) ?></span>
                                                <span class="file-uploaded"><?= round($f['size']/1024/1024, 2) ?> МБ</span>
                                            </div>
                                            <div class="file-actions">
                                                <a href="<?= htmlspecialchars($f['path']) ?>" target="_blank">Открыть</a>
                                                <a href="<?= htmlspecialchars($f['path']) ?>" download>Скачать</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <p>Неофициальный прикладной сайт. Не аффилирован с Фоксфордом.</p>
</footer>
</body>
</html>