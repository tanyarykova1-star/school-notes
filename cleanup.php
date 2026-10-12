<?php
$config = require __DIR__ . '/config.php';

$isCli = php_sapi_name() === 'cli';
$isAuthorized = $isCli || (($_GET['key'] ?? '') === $config['cleanup_key']);

if (!$isAuthorized) {
    http_response_code(403);
    exit('Forbidden');
}

$UPLOAD_DIR = __DIR__ . '/uploads';
$MAX_AGE = $config['max_age_days'] * 24 * 60 * 60;

$deletedFiles = 0;
$deletedDirs = 0;
$freedBytes = 0;

function cleanupDir(string $dir, int $MAX_AGE, int &$df, int &$dd, int &$fb): void
{
    if (!is_dir($dir)) return;

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            cleanupDir($path, $MAX_AGE, $df, $dd, $fb);
            $remaining = array_diff(scandir($path), ['.', '..']);
            if (empty($remaining)) {
                @rmdir($path);
                $dd++;
            }
        } elseif (is_file($path)) {
            if (time() - filemtime($path) > $MAX_AGE) {
                $fb += filesize($path);
                if (@unlink($path)) $df++;
            }
        }
    }
}

cleanupDir($UPLOAD_DIR, $MAX_AGE, $deletedFiles, $deletedDirs, $freedBytes);

$freedMb = round($freedBytes / 1024 / 1024, 2);
$log = date('Y-m-d H:i:s')
     . " | Файлов: $deletedFiles"
     . " | Папок: $deletedDirs"
     . " | Освобождено: {$freedMb} МБ\n";

file_put_contents(__DIR__ . '/cleanup.log', $log, FILE_APPEND);

if ($isCli) echo $log;
else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $log;
}