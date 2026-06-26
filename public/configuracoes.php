<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$user = currentUser() ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('configuracoes.php');

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $tema = normalizeTheme($_POST['tema'] ?? 'light');
    $corTema = normalizeThemeColor($_POST['cor_tema'] ?? '#246bfd');

    if ($nome === '' || strlen($nome) > 150 || $email === false) {
        setFlash('error', 'Informe um nome e um e-mail válidos.');
    } else {
        try {
            $stmt = db()->prepare('UPDATE usuario SET nome = ?, email = ?, tema = ?, cor_tema = ? WHERE id = ? AND ativo = 1');
            $stmt->execute([$nome, $email, $tema, $corTema, $user['id']]);
            $fresh = db()->prepare('SELECT id, nome, email, perfil, tema, cor_tema FROM usuario WHERE id = ? LIMIT 1');
            $fresh->execute([$user['id']]);
            $updatedUser = $fresh->fetch();

            if ($updatedUser) {
                storeUserInSession($updatedUser);
            }

            setFlash('success', 'Configurações salvas com sucesso.');
        } catch (PDOException $e) {
            setFlash('error', (string) $e->getCode() === '23000' ? 'Este e-mail já está em uso.' : 'Não foi possível salvar as configurações.');
        }
    }

    redirect('configuracoes.php');
}

renderHeader('Configurações', 'configuracoes');
?>
<header class="page-header">
    <div><span class="eyebrow">Preferências</span><h1>Configurações</h1><p>Personalize seu perfil e a aparência do sistema.</p></div>
</header>

<section class="panel">
    <div class="panel-header"><div><h2>Meu perfil</h2><p>Estas informações identificam sua sessão de acesso.</p></div></div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Nome<input name="nome" maxlength="150" value="<?= e($user['nome'] ?? '') ?>" required></label>
        <label>E-mail<input type="email" name="email" maxlength="150" value="<?= e($user['email'] ?? '') ?>" required></label>
        <label>Perfil<input value="<?= e(profileLabel($user)) ?>" readonly></label>
        <label>Tema<select name="tema"><option value="light"<?= selected($user['tema'] ?? 'light', 'light') ?>>Claro</option><option value="dark"<?= selected($user['tema'] ?? 'light', 'dark') ?>>Escuro</option></select></label>
        <label class="color-field">Cor principal<input type="color" name="cor_tema" value="<?= e(normalizeThemeColor($user['cor_tema'] ?? '#246bfd')) ?>"><small>Escolha a cor de botões, links e destaques.</small></label>
        <div class="form-actions full-row"><button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar configurações</button></div>
    </form>
</section>
<?php renderFooter(); ?>
