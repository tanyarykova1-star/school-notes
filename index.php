<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сдача конспектов — Главная</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="landing">
    <h1>Сдача конспектов</h1>
    <p class="subtitle">Выберите режим входа</p>

    <div class="mode-cards">
        <a href="student-form.php" class="mode-card student">
            <div class="icon">📚</div>
            <h2>Я ученик</h2>
            <p>Отправить конспект учителю</p>
        </a>

        <a href="teacher-login.php" class="mode-card teacher">
            <div class="icon">🔐</div>
            <h2>Я учитель</h2>
            <p>Просмотр присланных работ</p>
        </a>
    </div>
</main>

<footer class="site-footer">
    <p>Неофициальный прикладной сайт для организации учебного процесса.
       Не аффилирован с Фоксфордом или иными образовательными платформами.</p>
    <p>© <?= date('Y') ?> Учительский проект. Все права защищены.</p>
</footer>
</body>
</html>