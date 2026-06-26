<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$user = currentUser() ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('configuracoes.php');

    $tema = normalizeTheme($_POST['tema'] ?? 'light');
    $corTema = normalizeThemeColor($_POST['cor_tema'] ?? '#246bfd');

    try {
        $stmt = db()->prepare('UPDATE usuario SET tema = ?, cor_tema = ? WHERE id = ? AND ativo = 1');
        $stmt->execute([$tema, $corTema, $user['id']]);
        $fresh = db()->prepare('SELECT id, nome, email, perfil, tema, cor_tema, foto, cargo, telefone FROM usuario WHERE id = ? LIMIT 1');
        $fresh->execute([$user['id']]);
        $updatedUser = $fresh->fetch();

        if ($updatedUser) {
            storeUserInSession($updatedUser);
        }

        setFlash('success', 'Configurações salvas com sucesso.');
    } catch (PDOException $e) {
        setFlash('error', 'Não foi possível salvar as configurações.');
    }

    redirect('configuracoes.php');
}

renderHeader('Configurações', 'configuracoes');
?>
<header class="page-header">
    <div><span class="eyebrow">Preferências</span><h1>Configurações</h1><p>Personalize a aparência do sistema.</p></div>
</header>

<section class="panel">
    <div class="panel-header"><div><h2>Aparência</h2><p>Escolha o tema e a cor principal da sua experiência.</p></div></div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Tema<select name="tema"><option value="light"<?= selected($user['tema'] ?? 'light', 'light') ?>>Claro</option><option value="dark"<?= selected($user['tema'] ?? 'light', 'dark') ?>>Escuro</option></select></label>
        <label class="color-field">Cor principal<input type="color" name="cor_tema" value="<?= e(normalizeThemeColor($user['cor_tema'] ?? '#246bfd')) ?>"><small>Escolha a cor de botões, links e destaques.</small></label>
        <div class="form-actions full-row"><button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar configurações</button></div>
    </form>
</section>
<?php renderFooter(); ?>
