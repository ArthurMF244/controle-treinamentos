<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$admin = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('contato.php');
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));

    if ($nome === '' || $email === false || $mensagem === '') {
        setFlash('error', 'Preencha nome, e-mail válido e mensagem.');
    } else {
        $stmt = db()->prepare('INSERT INTO contato (nome, email, mensagem) VALUES (?, ?, ?)');
        $stmt->execute([$nome, $email, $mensagem]);
        setFlash('success', 'Mensagem enviada com sucesso. Obrigado pelo contato!');
    }
    redirect('contato.php');
}

$contacts = $admin ? db()->query('SELECT id, nome, email, mensagem, created_at FROM contato ORDER BY created_at DESC')->fetchAll() : [];

renderHeader('Contato', 'contato');
?>
<header class="page-header"><div><span class="eyebrow"><?= $admin ? 'Atendimento' : 'Fale conosco' ?></span><h1><?= $admin ? 'Contatos' : 'Contato' ?></h1><p><?= $admin ? 'Acompanhe as mensagens recebidas e envie um novo contato quando necessário.' : 'Envie uma mensagem para a equipe responsável pelo sistema.' ?></p></div></header>
<section class="panel">
    <div class="panel-header"><div><h2>Formulário de contato</h2><p>Sua mensagem será registrada para acompanhamento da equipe.</p></div></div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Nome *<input name="nome" maxlength="150" value="<?= e(currentUser()['nome'] ?? '') ?>" required></label>
        <label>E-mail *<input type="email" name="email" maxlength="150" value="<?= e(currentUser()['email'] ?? '') ?>" required></label>
        <label class="full-row">Mensagem *<textarea name="mensagem" maxlength="5000" placeholder="Como podemos ajudar?" required></textarea></label>
        <div class="form-actions full-row"><button class="btn primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar mensagem</button></div>
    </form>
</section>
<?php if ($admin): ?>
<section class="panel">
    <div class="panel-header"><div><h2>Mensagens recebidas</h2><p><?= count($contacts) ?> mensagem(ns) registrada(s).</p></div></div>
    <?php if (!$contacts): ?><div class="empty-state"><i class="fa-regular fa-envelope"></i><strong>Nenhuma mensagem recebida</strong><p>Os novos contatos aparecerão aqui.</p></div>
    <?php else: ?><div class="table-wrapper"><table><thead><tr><th>Remetente</th><th>Mensagem</th><th>Recebido em</th></tr></thead><tbody><?php foreach ($contacts as $contact): ?><tr><td><strong><?= e($contact['nome']) ?></strong><br><small><?= e($contact['email']) ?></small></td><td><?= nl2br(e($contact['mensagem'])) ?></td><td><?= e(formatDateTime($contact['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php renderFooter(); ?>
