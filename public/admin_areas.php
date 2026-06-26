<?php

declare(strict_types=1);

require_once __DIR__ . '/_admin.php';
requireAdmin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin_areas.php');

    $action = (string) ($_POST['action'] ?? 'salvar');

    if ($action === 'alternar_status') {
        $id = positiveInt($_POST['id'] ?? null);
        if (!$id) {
            setFlash('error', 'Área inválida.');
        } else {
            $stmt = $pdo->prepare('UPDATE area SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Status da área atualizado.');
        }

        redirect('admin_areas.php');
    }

    $id = positiveInt($_POST['id'] ?? null);
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $ativo = (string) ($_POST['ativo'] ?? '1') === '1' ? 1 : 0;

    if ($nome === '' || strlen($nome) > 120) {
        setFlash('error', 'Informe um nome de área com até 120 caracteres.');
        redirect($id ? "admin_areas.php?editar={$id}" : 'admin_areas.php');
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE area SET nome = ?, ativo = ? WHERE id = ?');
            $stmt->execute([$nome, $ativo, $id]);
            setFlash('success', 'Área atualizada com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO area (nome, ativo) VALUES (?, ?)');
            $stmt->execute([$nome, $ativo]);
            setFlash('success', 'Área cadastrada com sucesso.');
        }
    } catch (PDOException $e) {
        setFlash('error', adminDuplicateMessage($e, 'Já existe uma área com este nome.'));
        redirect($id ? "admin_areas.php?editar={$id}" : 'admin_areas.php');
    }

    redirect('admin_areas.php');
}

$editingId = positiveInt($_GET['editar'] ?? null);
$editingArea = null;
if ($editingId) {
    $stmt = $pdo->prepare('SELECT id, nome, ativo FROM area WHERE id = ? LIMIT 1');
    $stmt->execute([$editingId]);
    $editingArea = $stmt->fetch();

    if (!$editingArea) {
        setFlash('error', 'Área não encontrada.');
        redirect('admin_areas.php');
    }
}

$areas = $pdo->query('SELECT id, nome, ativo, created_at FROM area ORDER BY ativo DESC, nome')->fetchAll();

renderHeader('Áreas/Setores', 'admin');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Administração</span>
        <h1>Áreas/Setores</h1>
        <p>Gerencie áreas usadas nos cadastros de pessoas e participantes.</p>
    </div>
    <a class="btn secondary" href="admin.php"><i class="fa-solid fa-arrow-left"></i> Administração</a>
</header>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $editingArea ? 'Editar área' : 'Nova área' ?></h2>
            <p>Áreas inativas deixam de aparecer em novos cadastros.</p>
        </div>
        <?php if ($editingArea): ?><a class="text-link" href="admin_areas.php">Cadastrar nova</a><?php endif; ?>
    </div>
    <form method="post" class="data-form compact">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="salvar">
        <?php if ($editingArea): ?><input type="hidden" name="id" value="<?= (int) $editingArea['id'] ?>"><?php endif; ?>
        <label>Nome *
            <input name="nome" maxlength="120" value="<?= e($editingArea['nome'] ?? '') ?>" placeholder="Ex.: Recursos Humanos" required>
        </label>
        <label>Ativo *
            <select name="ativo" required>
                <option value="1"<?= selected((string) ($editingArea['ativo'] ?? '1'), '1') ?>>Ativo</option>
                <option value="0"<?= selected((string) ($editingArea['ativo'] ?? '1'), '0') ?>>Inativo</option>
            </select>
        </label>
        <div class="form-actions">
            <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            <?php if ($editingArea): ?><a class="btn secondary" href="admin_areas.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><h2>Áreas cadastradas</h2><p>Ative ou inative sem apagar histórico.</p></div></div>
    <?php if (!$areas): ?>
        <div class="empty-state"><i class="fa-solid fa-sitemap"></i><strong>Nenhuma área cadastrada</strong></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Nome</th><th>Status</th><th>Criada em</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($areas as $area): ?>
                        <tr>
                            <td><strong><?= e($area['nome']) ?></strong></td>
                            <td><?= adminActiveBadge($area['ativo']) ?></td>
                            <td><?= e(formatDateTime($area['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary small" href="admin_areas.php?editar=<?= (int) $area['id'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="alternar_status">
                                        <input type="hidden" name="id" value="<?= (int) $area['id'] ?>">
                                        <button class="btn <?= (int) $area['ativo'] === 1 ? 'danger' : 'secondary' ?> small" type="submit" data-confirm="<?= (int) $area['ativo'] === 1 ? 'Inativar esta área?' : 'Ativar esta área?' ?>">
                                            <i class="fa-solid <?= (int) $area['ativo'] === 1 ? 'fa-ban' : 'fa-check' ?>"></i>
                                            <?= (int) $area['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
