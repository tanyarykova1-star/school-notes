<?php
session_start();
$config = require __DIR__ . '/config.php';

$error = '';

// Уже залогинен — идём в панель
if (!empty($_SESSION['is_teacher'])) {
    header('Location: teacher-dashboard.php');
    exit;
}

// Ограничение попыток
$now = time();
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
$_SESSION['login_attempts'] = array_filter(
    $_SESSION['login_attempts'],
    fn($t) => $now - $t < $config['login_lockout_seconds']
);

$locked = count($_SESSION['login_attempts']) >= $config['max_login_attempts'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    $code = $_POST['code'] ?? '';
    if (password_verify($code, $config['teacher_code_hash'])) {
        $_SESSION['is_teacher'] = true;
        $_SESSION['login_attempts'] = [];
        session_regenerate_id(true);
        header('Location: teacher-dashboard.php');
        exit;
    } else {
        $_SESSION['login_attempts'][] = $now;
        $error = 'Неверный код.';
    }
}

if ($locked) {
    $error = 'Слишком много попыток. Подождите 5 минут.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход для учителя</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="form-page narrow">
    <a href="index.php" class="back-link">← Назад</a>
    <h1>Вход для учителя</h1>
    <p class="subtitle">Введите код доступа</p>

    <?php if ($error): ?>
        <p class="form-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Код доступа
            <input type="password" name="code" required autofocus
                   maxlength="32" autocomplete="off">
        </label>
        <button type="submit" class="btn-primary" <?= $locked ? 'disabled' : '' ?>>
            Войти
        </button>
    </form>
</main>
</body>
</html>