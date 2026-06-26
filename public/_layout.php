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

function normalizeTheme(mixed $theme): string
{
    return $theme === 'dark' ? 'dark' : 'light';
}

function normalizeThemeColor(mixed $color): string
{
    $color = trim((string) $color);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '#246bfd';
}

function storeUserInSession(array $user): void
{
    $_SESSION['usuario'] = [
        'id' => (int) $user['id'],
        'nome' => (string) $user['nome'],
        'email' => (string) $user['email'],
        'perfil' => ($user['perfil'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario',
        'tema' => normalizeTheme($user['tema'] ?? 'light'),
        'cor_tema' => normalizeThemeColor($user['cor_tema'] ?? '#246bfd'),
    ];
}

function syncCurrentUser(): ?array
{
    $user = currentUser();
    $id = is_array($user) ? positiveInt($user['id'] ?? null) : null;

    if (!$id) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, nome, email, perfil, tema, cor_tema FROM usuario WHERE id = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$id]);
    $freshUser = $stmt->fetch();

    if (!$freshUser) {
        $_SESSION = [];
        return null;
    }

    storeUserInSession($freshUser);

    return currentUser();
}

function requireLogin(): void
{
    if (syncCurrentUser() === null) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin(): bool
{
    return (currentUser()['perfil'] ?? 'usuario') === 'admin';
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        setFlash('error', 'Acesso não permitido para o seu perfil.');
        redirect('index.php');
    }
}

function profileLabel(?array $user = null): string
{
    return (($user ?? currentUser())['perfil'] ?? 'usuario') === 'admin' ? 'Administrador' : 'Usuário';
}

function userInitials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials ?: 'U';
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
    return $value ? date('Y-m-d\\TH:i', strtotime($value)) : '';
}

function formatDateTime(?string $value): string
{
    return $value ? date('d/m/Y H:i', strtotime($value)) : '-';
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
    $user = currentUser() ?? [];
    $admin = isAdmin();
    $links = [
        'dashboard' => ['index.php', 'Dashboard', 'fa-chart-line', true],
        'treinamentos' => ['treinamentos.php', 'Treinamentos', 'fa-graduation-cap', true],
        'pessoas' => ['pessoas.php', 'Pessoas', 'fa-address-book', $admin],
        'participantes' => ['participantes.php', 'Participantes', 'fa-users', true],
        'certificados' => ['certificados.php', 'Certificados', 'fa-certificate', true],
        'relatorios' => ['relatorios.php', 'Relatórios', 'fa-table-list', true],
        'contato' => ['contato.php', $admin ? 'Contatos' : 'Contato', 'fa-envelope', true],
        'configuracoes' => ['configuracoes.php', 'Configurações', 'fa-gear', true],
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
<body class="theme-<?= e(normalizeTheme($user['tema'] ?? 'light')) ?>" style="--primary: <?= e(normalizeThemeColor($user['cor_tema'] ?? '#246bfd')) ?>;">
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <a class="brand" href="index.php">
            <span class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <span>Controle de<br><strong>Treinamentos</strong></span>
        </a>
        <nav class="menu" aria-label="Menu principal">
            <?php foreach ($links as $key => [$href, $label, $icon, $visible]): ?>
                <?php if ($visible): ?>
                    <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>">
                        <i class="fa-solid <?= e($icon) ?>"></i><span><?= e($label) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <button class="profile-trigger" type="button" data-profile-menu-toggle aria-expanded="false" aria-controls="profile-menu">
                <span class="user-summary">
                    <span class="user-avatar"><?= e(userInitials($user['nome'] ?? null)) ?></span>
                    <span><strong><?= e($user['nome'] ?? '') ?></strong><small><?= e(profileLabel($user)) ?></small></span>
                </span>
                <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
            </button>
            <div class="profile-menu" id="profile-menu" data-profile-menu hidden>
                <a href="configuracoes.php"><i class="fa-regular fa-user"></i> Meu perfil</a>
                <a href="configuracoes.php"><i class="fa-solid fa-gear"></i> Configurações</a>
                <button type="button" data-theme-toggle><i class="fa-solid fa-moon"></i> Alternar tema</button>
                <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
            </div>
        </div>
    </aside>
    <main class="content">
        <header class="topbar">
            <button class="menu-toggle" type="button" data-menu-toggle aria-label="Abrir menu"><i class="fa-solid fa-bars"></i></button>
            <div><span class="topbar-label">Sistema de treinamentos</span><strong><?= e($title) ?></strong></div>
            <button class="topbar-theme" type="button" data-theme-toggle aria-label="Alternar tema"><i class="fa-solid fa-moon"></i></button>
        </header>
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
<script src="js/app.js" defer></script>
</body>
</html>
    <?php
}
