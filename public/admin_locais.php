<?php

declare(strict_types=1);

require_once __DIR__ . '/_admin.php';
requireAdmin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin_locais.php');

    $action = (string) ($_POST['action'] ?? 'salvar');

    if ($action === 'alternar_status') {
        $id = positiveInt($_POST['id'] ?? null);
        if (!$id) {
            setFlash('error', 'Local de treinamento inválido.');
        } else {
            $stmt = $pdo->prepare('UPDATE local_treinamento SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Status do local atualizado.');
        }

        redirect('admin_locais.php');
    }

    $id = positiveInt($_POST['id'] ?? null);
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $capacidade = filter_var($_POST['capacidade'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $ativo = (string) ($_POST['ativo'] ?? '1') === '1' ? 1 : 0;

    if ($nome === '' || strlen($nome) > 120 || $capacidade === false) {
        setFlash('error', 'Informe nome e capacidade válida para o local.');
        redirect($id ? "admin_locais.php?editar={$id}" : 'admin_locais.php');
    }

    try {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE local_treinamento SET nome = ?, descricao = ?, capacidade = ?, ativo = ? WHERE id = ?');
            $stmt->execute([$nome, $descricao ?: null, $capacidade, $ativo, $id]);
            setFlash('success', 'Local de treinamento atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO local_treinamento (nome, descricao, capacidade, ativo) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nome, $descricao ?: null, $capacidade, $ativo]);
            setFlash('success', 'Local de treinamento cadastrado com sucesso.');
        }
    } catch (PDOException $e) {
        setFlash('error', adminDuplicateMessage($e, 'Já existe um local de treinamento com este nome.'));
        redirect($id ? "admin_locais.php?editar={$id}" : 'admin_locais.php');
    }

    redirect('admin_locais.php');
}

$editingId = positiveInt($_GET['editar'] ?? null);
$editingLocation = null;
if ($editingId) {
    $stmt = $pdo->prepare('SELECT id, nome, descricao, capacidade, ativo FROM local_treinamento WHERE id = ? LIMIT 1');
    $stmt->execute([$editingId]);
    $editingLocation = $stmt->fetch();

    if (!$editingLocation) {
        setFlash('error', 'Local de treinamento não encontrado.');
        redirect('admin_locais.php');
    }
}

$locations = $pdo->query('SELECT id, nome, descricao, capacidade, ativo, created_at FROM local_treinamento ORDER BY ativo DESC, nome')->fetchAll();

renderHeader('Locais de treinamento', 'admin');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Administração</span>
        <h1>Locais de treinamento</h1>
        <p>Gerencie salas, auditórios e ambientes virtuais usados nos treinamentos.</p>
    </div>
    <a class="btn secondary" href="admin.php"><i class="fa-solid fa-arrow-left"></i> Administração</a>
</header>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $editingLocation ? 'Editar local' : 'Novo local' ?></h2>
            <p>Locais inativos deixam de aparecer em novos treinamentos.</p>
        </div>
        <?php if ($editingLocation): ?><a class="text-link" href="admin_locais.php">Cadastrar novo</a><?php endif; ?>
    </div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="salvar">
        <?php if ($editingLocation): ?><input type="hidden" name="id" value="<?= (int) $editingLocation['id'] ?>"><?php endif; ?>
        <label>Nome *
            <input name="nome" maxlength="120" value="<?= e($editingLocation['nome'] ?? '') ?>" placeholder="Ex.: Auditório Central" required>
        </label>
        <label>Capacidade *
            <input type="number" name="capacidade" min="1" value="<?= e($editingLocation['capacidade'] ?? '') ?>" placeholder="Ex.: 80" required>
        </label>
        <label>Ativo *
            <select name="ativo" required>
                <option value="1"<?= selected((string) ($editingLocation['ativo'] ?? '1'), '1') ?>>Ativo</option>
                <option value="0"<?= selected((string) ($editingLocation['ativo'] ?? '1'), '0') ?>>Inativo</option>
            </select>
        </label>
        <label class="full-row">Descrição
            <textarea name="descricao" placeholder="Detalhes do local, recursos disponíveis ou observações."><?= e($editingLocation['descricao'] ?? '') ?></textarea>
        </label>
        <div class="form-actions full-row">
            <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            <?php if ($editingLocation): ?><a class="btn secondary" href="admin_locais.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><h2>Locais cadastrados</h2><p>Ative ou inative sem apagar histórico.</p></div></div>
    <?php if (!$locations): ?>
        <div class="empty-state"><i class="fa-solid fa-location-dot"></i><strong>Nenhum local cadastrado</strong></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Nome</th><th>Descrição</th><th>Capacidade</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><strong><?= e($location['nome']) ?></strong></td>
                            <td><?= e($location['descricao'] ?: '-') ?></td>
                            <td><?= (int) $location['capacidade'] ?> pessoas</td>
                            <td><?= adminActiveBadge($location['ativo']) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary small" href="admin_locais.php?editar=<?= (int) $location['id'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="alternar_status">
                                        <input type="hidden" name="id" value="<?= (int) $location['id'] ?>">
                                        <button class="btn <?= (int) $location['ativo'] === 1 ? 'danger' : 'secondary' ?> small" type="submit" data-confirm="<?= (int) $location['ativo'] === 1 ? 'Inativar este local?' : 'Ativar este local?' ?>">
                                            <i class="fa-solid <?= (int) $location['ativo'] === 1 ? 'fa-ban' : 'fa-check' ?>"></i>
                                            <?= (int) $location['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
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
