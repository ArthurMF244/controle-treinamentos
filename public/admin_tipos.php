<?php

declare(strict_types=1);

require_once __DIR__ . '/_admin.php';
requireAdmin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin_tipos.php');

    $action = (string) ($_POST['action'] ?? 'salvar');

    if ($action === 'alternar_status') {
        $id = positiveInt($_POST['id'] ?? null);
        if (!$id) {
            setFlash('error', 'Tipo de treinamento inválido.');
        } else {
            $stmt = $pdo->prepare('UPDATE tipo_treinamento SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Status do tipo de treinamento atualizado.');
        }

        redirect('admin_tipos.php');
    }

    $id = positiveInt($_POST['id'] ?? null);
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $ativo = (string) ($_POST['ativo'] ?? '1') === '1' ? 1 : 0;

    if ($nome === '' || strlen($nome) > 100) {
        setFlash('error', 'Informe um nome de tipo com até 100 caracteres.');
        redirect($id ? "admin_tipos.php?editar={$id}" : 'admin_tipos.php');
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE tipo_treinamento SET nome = ?, descricao = ?, ativo = ? WHERE id = ?');
            $stmt->execute([$nome, $descricao ?: null, $ativo, $id]);
            setFlash('success', 'Tipo de treinamento atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO tipo_treinamento (nome, descricao, ativo) VALUES (?, ?, ?)');
            $stmt->execute([$nome, $descricao ?: null, $ativo]);
            setFlash('success', 'Tipo de treinamento cadastrado com sucesso.');
        }
    } catch (PDOException $e) {
        setFlash('error', adminDuplicateMessage($e, 'Já existe um tipo de treinamento com este nome.'));
        redirect($id ? "admin_tipos.php?editar={$id}" : 'admin_tipos.php');
    }

    redirect('admin_tipos.php');
}

$editingId = positiveInt($_GET['editar'] ?? null);
$editingType = null;
if ($editingId) {
    $stmt = $pdo->prepare('SELECT id, nome, descricao, ativo FROM tipo_treinamento WHERE id = ? LIMIT 1');
    $stmt->execute([$editingId]);
    $editingType = $stmt->fetch();

    if (!$editingType) {
        setFlash('error', 'Tipo de treinamento não encontrado.');
        redirect('admin_tipos.php');
    }
}

$types = $pdo->query('SELECT id, nome, descricao, ativo, created_at FROM tipo_treinamento ORDER BY ativo DESC, nome')->fetchAll();

renderHeader('Tipos de treinamento', 'admin');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Administração</span>
        <h1>Tipos de treinamento</h1>
        <p>Gerencie as categorias usadas para organizar os treinamentos.</p>
    </div>
    <a class="btn secondary" href="admin.php"><i class="fa-solid fa-arrow-left"></i> Administração</a>
</header>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $editingType ? 'Editar tipo' : 'Novo tipo' ?></h2>
            <p>Tipos inativos deixam de aparecer em novos treinamentos.</p>
        </div>
        <?php if ($editingType): ?><a class="text-link" href="admin_tipos.php">Cadastrar novo</a><?php endif; ?>
    </div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="salvar">
        <?php if ($editingType): ?><input type="hidden" name="id" value="<?= (int) $editingType['id'] ?>"><?php endif; ?>
        <label>Nome *
            <input name="nome" maxlength="100" value="<?= e($editingType['nome'] ?? '') ?>" placeholder="Ex.: Obrigatório" required>
        </label>
        <label>Ativo *
            <select name="ativo" required>
                <option value="1"<?= selected((string) ($editingType['ativo'] ?? '1'), '1') ?>>Ativo</option>
                <option value="0"<?= selected((string) ($editingType['ativo'] ?? '1'), '0') ?>>Inativo</option>
            </select>
        </label>
        <label class="full-row">Descrição
            <textarea name="descricao" placeholder="Explique quando este tipo deve ser usado."><?= e($editingType['descricao'] ?? '') ?></textarea>
        </label>
        <div class="form-actions full-row">
            <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            <?php if ($editingType): ?><a class="btn secondary" href="admin_tipos.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><h2>Tipos cadastrados</h2><p>Ative ou inative sem apagar histórico.</p></div></div>
    <?php if (!$types): ?>
        <div class="empty-state"><i class="fa-solid fa-tags"></i><strong>Nenhum tipo cadastrado</strong></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Nome</th><th>Descrição</th><th>Status</th><th>Criado em</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($types as $type): ?>
                        <tr>
                            <td><strong><?= e($type['nome']) ?></strong></td>
                            <td><?= e($type['descricao'] ?: '-') ?></td>
                            <td><?= adminActiveBadge($type['ativo']) ?></td>
                            <td><?= e(formatDateTime($type['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary small" href="admin_tipos.php?editar=<?= (int) $type['id'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="alternar_status">
                                        <input type="hidden" name="id" value="<?= (int) $type['id'] ?>">
                                        <button class="btn <?= (int) $type['ativo'] === 1 ? 'danger' : 'secondary' ?> small" type="submit" data-confirm="<?= (int) $type['ativo'] === 1 ? 'Inativar este tipo?' : 'Ativar este tipo?' ?>">
                                            <i class="fa-solid <?= (int) $type['ativo'] === 1 ? 'fa-ban' : 'fa-check' ?>"></i>
                                            <?= (int) $type['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
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
