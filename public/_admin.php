<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

function adminBadge(string $text, string $variant = ''): string
{
    $class = trim('badge ' . $variant);

    return '<span class="' . e($class) . '">' . e($text) . '</span>';
}

function adminActiveBadge(mixed $active): string
{
    return (int) $active === 1
        ? adminBadge('Ativo', 'success')
        : adminBadge('Inativo', 'danger');
}

function adminProfileBadge(mixed $profile): string
{
    return (string) $profile === 'admin'
        ? adminBadge('Admin', 'warning')
        : adminBadge('Usuário', 'muted');
}

function refreshCurrentAdminSession(int $userId): void
{
    if ((int) (currentUser()['id'] ?? 0) !== $userId) {
        return;
    }

    $stmt = db()->prepare('SELECT id, nome, email, perfil, tema, cor_tema, foto, cargo, telefone FROM usuario WHERE id = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$userId]);
    if ($user = $stmt->fetch()) {
        storeUserInSession($user);
    }
}

function adminDuplicateMessage(PDOException $exception, string $fallback): string
{
    return (string) $exception->getCode() === '23000' ? $fallback : 'Não foi possível salvar o registro.';
}
