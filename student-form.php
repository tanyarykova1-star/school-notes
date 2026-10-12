<?php
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Отправка конспекта</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="form-page">
    <a href="index.php" class="back-link">← Назад</a>
    <h1>Отправить конспект</h1>

    <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

        <label>Имя и фамилия
            <input type="text" name="fullname" required maxlength="100" placeholder="Иванов Иван">
        </label>

        <label>Класс
            <select name="grade" required>
                <option value="">— выберите —</option>
                <?php for ($i = 5; $i <= 11; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> класс</option>
                <?php endfor; ?>
            </select>
        </label>

        <label>День недели
            <select name="weekday" required>
                <option value="">— выберите —</option>
                <option value="monday">Понедельник</option>
                <option value="tuesday">Вторник</option>
                <option value="wednesday">Среда</option>
                <option value="thursday">Четверг</option>
                <option value="friday">Пятница</option>
            </select>
        </label>

        <div class="time-row">
            <label>Уроки с
                <input type="time" name="time_from" required>
            </label>
            <label>до
                <input type="time" name="time_to" required>
            </label>
        </div>

        <!-- Зона загрузки -->
        <!-- Зона загрузки -->
<div class="upload-zone" id="uploadZone">
    <p class="upload-hint">Перетащите файлы для загрузки (до 5 штук)</p>
    <button type="button" class="upload-btn" id="uploadBtn">
        <span class="upload-icon">⬇</span> Загрузить файлы
    </button>
    <input type="file" name="files[]" id="fileInput"
           accept=".png,.jpg,.jpeg,.pdf,.svg" multiple hidden required>
    <p class="upload-filename" id="fileName"></p>
</div>
<p class="upload-note">
    Форматы: PDF, PNG, JPEG, SVG. До 5 файлов, каждый — не более 10 МБ.
</p>

        <!-- Согласия -->
        <div class="consents">
            <label class="checkbox">
                <input type="checkbox" name="consent_privacy" required>
                <span>Я ознакомлен(а) с <a href="privacy.php" target="_blank">Политикой конфиденциальности</a></span>
            </label>

            <label class="checkbox">
                <input type="checkbox" name="consent_pd" required>
                <span>Я даю <a href="consent.php" target="_blank">согласие на обработку персональных данных</a></span>
            </label>
        </div>

        <button type="submit" class="btn-primary">Отправить конспект</button>
        <p class="form-error" id="formError"></p>
    </form>
</main>

<div class="toast" id="successToast">
    <span class="check">✓</span>
    <span>Ваш конспект отправлен!</span>
</div>

<script src="assets/script.js"></script>
</body>
</html>