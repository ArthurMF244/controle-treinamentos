<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/database/connection.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('controle_treinamentos');
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function currentUser(): ?array
{
    $user = $_SESSION['usuario'] ?? null;

    return is_array($user) ? $user : null;
}

function requireLogin(): void
{
    if (currentUser() === null) {
        header('Location: login.php');
        exit;
    }
}

function redirect(string $location): never
{
    header("Location: {$location}");
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function isValidCsrf(): bool
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');

    return $submitted !== ''
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $submitted);
}

function requireCsrf(string $fallback): void
{
    if (!isValidCsrf()) {
        setFlash('error', 'Sua sessão expirou. Tente enviar o formulário novamente.');
        redirect($fallback);
    }
}

function positiveInt(mixed $value): ?int
{
    $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $number === false ? null : (int) $number;
}

function dateTimeToSql(mixed $value): ?string
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', trim((string) $value));
    $errors = DateTimeImmutable::getLastErrors();

    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }

    return $date->format('Y-m-d H:i:s');
}

function inputDateTime(?string $value): string
{
    if (!$value) {
        return '';
    }

    return date('Y-m-d\\TH:i', strtotime($value));
}

function formatDateTime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    return date('d/m/Y H:i', strtotime($value));
}

function formatHours(int|string $minutes): string
{
    $hours = ((int) $minutes) / 60;
    $formatted = rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',');

    return "{$formatted}h";
}

function selected(mixed $current, mixed $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function renderHeader(string $title, string $active): void
{
    $user = currentUser();
    $links = [
        'dashboard' => ['index.php', 'Dashboard', 'fa-chart-line'],
        'treinamentos' => ['treinamentos.php', 'Treinamentos', 'fa-graduation-cap'],
        'participantes' => ['participantes.php', 'Participantes', 'fa-users'],
        'relatorios' => ['relatorios.php', 'Relatórios', 'fa-table-list'],
        'certificados' => ['certificados.php', 'Certificados', 'fa-certificate'],
        'contato' => ['contato.php', 'Contato', 'fa-envelope'],
    ];
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | Controle de Treinamentos</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <span>Controle de<br><strong>Treinamentos</strong></span>
        </a>
        <nav class="menu" aria-label="Menu principal">
            <?php foreach ($links as $key => [$href, $label, $icon]): ?>
                <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>">
                    <i class="fa-solid <?= e($icon) ?>"></i><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-summary">
                <span class="user-avatar"><?= e(strtoupper(substr((string) ($user['nome'] ?? 'A'), 0, 1))) ?></span>
                <span><strong><?= e($user['nome'] ?? '') ?></strong><small>Administrador</small></span>
            </div>
            <a class="logout-link" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
        </div>
    </aside>
    <main class="content">
        <?php if ($flash = pullFlash()): ?>
            <div class="alert <?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function renderFooter(): void
{
    ?>
    </main>
</div>
</body>
</html>
    <?php
}
