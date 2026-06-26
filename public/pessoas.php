<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireAdmin();

$pdo = db();
$areas = $pdo->query('SELECT id, nome FROM area WHERE ativo = 1 ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('pessoas.php');
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'salvar') {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $cargo = trim((string) ($_POST['cargo'] ?? ''));
            $areaId = positiveInt($_POST['area_id'] ?? null);

            if ($nome === '' || $cargo === '' || $email === false || !$areaId) {
                throw new InvalidArgumentException('Preencha nome, e-mail, cargo e área com valores válidos.');
            }

            $areaExists = $pdo->prepare('SELECT COUNT(*) FROM area WHERE id = ? AND ativo = 1');
            $areaExists->execute([$areaId]);
            if (!(int) $areaExists->fetchColumn()) {
                throw new InvalidArgumentException('A área selecionada não está disponível.');
            }

            $stmt = $pdo->prepare('INSERT INTO pessoa (nome, email, cargo, area_id, ativo) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$nome, $email, $cargo, $areaId]);
            setFlash('success', 'Pessoa cadastrada com sucesso.');
        }

        if ($action === 'inativar') {
            $id = positiveInt($_POST['id'] ?? null);
            if (!$id) {
                throw new InvalidArgumentException('Pessoa inválida.');
            }

            $pdo->prepare('UPDATE pessoa SET ativo = 0 WHERE id = ?')->execute([$id]);
            setFlash('success', 'Pessoa inativada com sucesso.');
        }
    } catch (InvalidArgumentException $e) {
        setFlash('error', $e->getMessage());
    } catch (PDOException $e) {
        setFlash('error', (string) $e->getCode() === '23000' ? 'Já existe uma pessoa cadastrada com este e-mail.' : 'Não foi possível concluir a operação.');
    }

    redirect('pessoas.php');
}

$people = $pdo->query('SELECT p.id, p.nome, p.email, p.cargo, a.nome AS area_nome FROM pessoa p JOIN area a ON a.id = p.area_id WHERE p.ativo = 1 ORDER BY p.nome')->fetchAll();

renderHeader('Pessoas', 'pessoas');
?>
<header class="page-header"><div><span class="eyebrow">Cadastros</span><h1>Pessoas</h1><p>Gerencie as pessoas disponíveis para treinamentos e responsabilidades.</p></div></header>

<section class="panel">
    <div class="panel-header"><div><h2>Nova pessoa</h2><p>Cadastre colaboradores para vinculá-los aos treinamentos.</p></div></div>
    <form method="post" class="data-form compact">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="salvar">
        <label>Nome *<input name="nome" maxlength="150" required></label>
        <label>E-mail *<input type="email" name="email" maxlength="150" required></label>
        <label>Cargo *<input name="cargo" maxlength="120" required></label>
        <label>Área *<select name="area_id" required><option value="">Selecione</option><?php foreach ($areas as $area): ?><option value="<?= (int) $area['id'] ?>"><?= e($area['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions"><button class="btn primary" type="submit"><i class="fa-solid fa-user-plus"></i> Cadastrar pessoa</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><h2>Pessoas ativas</h2><p><?= count($people) ?> pessoa(s) disponível(is).</p></div></div>
    <?php if (!$people): ?><div class="empty-state"><i class="fa-solid fa-address-book"></i><strong>Nenhuma pessoa cadastrada</strong><p>Adicione uma pessoa para começar.</p></div>
    <?php else: ?><div class="table-wrapper"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Cargo</th><th>Área</th><th>Ações</th></tr></thead><tbody><?php foreach ($people as $person): ?><tr><td><strong><?= e($person['nome']) ?></strong></td><td><?= e($person['email']) ?></td><td><?= e($person['cargo']) ?></td><td><?= e($person['area_nome']) ?></td><td><form method="post" class="inline-form" onsubmit="return confirm('Inativar esta pessoa?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="inativar"><input type="hidden" name="id" value="<?= (int) $person['id'] ?>"><button class="btn danger small" type="submit"><i class="fa-solid fa-ban"></i> Inativar</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php renderFooter(); ?>
