<?php

declare(strict_types=1);

require_once __DIR__ . '/_admin.php';
requireAdmin();

$pdo = db();
$groups = [
    'treinamento' => ['label' => 'Status do treinamento', 'table' => 'status_treinamento', 'icon' => 'fa-graduation-cap'],
    'participacao' => ['label' => 'Status da participação', 'table' => 'status_participacao', 'icon' => 'fa-user-check'],
    'certificado' => ['label' => 'Status do certificado', 'table' => 'status_certificado', 'icon' => 'fa-certificate'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin_status.php');

    $groupKey = (string) ($_POST['grupo'] ?? '');
    $id = positiveInt($_POST['id'] ?? null);
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));

    if (!isset($groups[$groupKey])) {
        setFlash('error', 'Grupo de status inválido.');
        redirect('admin_status.php');
    }

    if ($nome === '' || strlen($nome) > 80) {
        setFlash('error', 'Informe um nome de status com até 80 caracteres.');
        redirect($id ? "admin_status.php?grupo={$groupKey}&editar={$id}" : 'admin_status.php');
    }

    $table = $groups[$groupKey]['table'];

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE {$table} SET nome = ?, descricao = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao ?: null, $id]);
            setFlash('success', 'Status atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO {$table} (nome, descricao) VALUES (?, ?)");
            $stmt->execute([$nome, $descricao ?: null]);
            setFlash('success', 'Status cadastrado com sucesso.');
        }
    } catch (PDOException $e) {
        setFlash('error', adminDuplicateMessage($e, 'Já existe um status com este nome neste grupo.'));
        redirect($id ? "admin_status.php?grupo={$groupKey}&editar={$id}" : 'admin_status.php');
    }

    redirect('admin_status.php');
}

$editingGroupKey = (string) ($_GET['grupo'] ?? '');
$editingId = positiveInt($_GET['editar'] ?? null);
$editingStatus = null;
if ($editingId) {
    if (!isset($groups[$editingGroupKey])) {
        setFlash('error', 'Grupo de status inválido.');
        redirect('admin_status.php');
    }

    $table = $groups[$editingGroupKey]['table'];
    $stmt = $pdo->prepare("SELECT id, nome, descricao FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->execute([$editingId]);
    $editingStatus = $stmt->fetch();

    if (!$editingStatus) {
        setFlash('error', 'Status não encontrado.');
        redirect('admin_status.php');
    }
}

$statuses = [];
foreach ($groups as $key => $group) {
    $statuses[$key] = $pdo->query("SELECT id, nome, descricao, created_at FROM {$group['table']} ORDER BY nome")->fetchAll();
}

renderHeader('Status do sistema', 'admin');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Administração</span>
        <h1>Status do sistema</h1>
        <p>Gerencie os status de treinamentos, participações e certificados.</p>
    </div>
    <a class="btn secondary" href="admin.php"><i class="fa-solid fa-arrow-left"></i> Administração</a>
</header>

<section class="panel" id="form-status">
    <div class="panel-header">
        <div>
            <h2><?= $editingStatus ? 'Editar status' : 'Novo status' ?></h2>
            <p>Os status ajudam a padronizar relatórios e fluxos do sistema.</p>
        </div>
        <?php if ($editingStatus): ?><a class="text-link" href="admin_status.php">Cadastrar novo</a><?php endif; ?>
    </div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($editingStatus): ?>
            <input type="hidden" name="id" value="<?= (int) $editingStatus['id'] ?>">
            <input type="hidden" name="grupo" value="<?= e($editingGroupKey) ?>">
            <label>Grupo
                <input value="<?= e($groups[$editingGroupKey]['label']) ?>" readonly>
            </label>
        <?php else: ?>
            <label>Grupo *
                <select name="grupo" required>
                    <?php foreach ($groups as $key => $group): ?>
                        <option value="<?= e($key) ?>"><?= e($group['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>Nome *
            <input name="nome" maxlength="80" value="<?= e($editingStatus['nome'] ?? '') ?>" placeholder="Ex.: Em andamento" required>
        </label>
        <label class="full-row">Descrição
            <textarea name="descricao" placeholder="Explique quando este status deve ser usado."><?= e($editingStatus['descricao'] ?? '') ?></textarea>
        </label>
        <div class="form-actions full-row">
            <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar status</button>
            <?php if ($editingStatus): ?><a class="btn secondary" href="admin_status.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
</section>

<?php foreach ($groups as $key => $group): ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2><i class="fa-solid <?= e($group['icon']) ?>"></i> <?= e($group['label']) ?></h2>
                <p><?= count($statuses[$key]) ?> status cadastrado<?= count($statuses[$key]) === 1 ? '' : 's' ?>.</p>
            </div>
        </div>
        <?php if (!$statuses[$key]): ?>
            <div class="empty-state"><i class="fa-solid <?= e($group['icon']) ?>"></i><strong>Nenhum status cadastrado</strong></div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Nome</th><th>Descrição</th><th>Criado em</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php foreach ($statuses[$key] as $status): ?>
                            <tr>
                                <td><strong><?= e($status['nome']) ?></strong></td>
                                <td><?= e($status['descricao'] ?: '-') ?></td>
                                <td><?= e(formatDateTime($status['created_at'])) ?></td>
                                <td><a class="btn secondary small" href="admin_status.php?grupo=<?= e($key) ?>&editar=<?= (int) $status['id'] ?>#form-status"><i class="fa-solid fa-pen"></i> Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
<?php renderFooter(); ?>
