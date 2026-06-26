<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$pdo = db();
$admin = isAdmin();
$trainings = $pdo->query('SELECT id, titulo, data_inicio FROM treinamento WHERE ativo = 1 ORDER BY data_inicio DESC')->fetchAll();
$people = $pdo->query('SELECT p.id, p.nome, p.cargo, a.nome AS area_nome FROM pessoa p JOIN area a ON a.id = p.area_id WHERE p.ativo = 1 ORDER BY p.nome')->fetchAll();
$participationStatuses = $pdo->query('SELECT id, nome FROM status_participacao ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$admin) {
        requireAdmin();
    }
    requireCsrf('participantes.php');
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'inscrever') {
            $trainingId = positiveInt($_POST['treinamento_id'] ?? null);
            $personId = positiveInt($_POST['pessoa_id'] ?? null);
            $statusId = positiveInt($_POST['status_participacao_id'] ?? null);
            $progress = filter_var($_POST['progresso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);

            if (!$trainingId || !$personId || !$statusId || $progress === false) {
                throw new InvalidArgumentException('Informe treinamento, participante, status e um progresso entre 0 e 100.');
            }

            $exists = $pdo->prepare('SELECT COUNT(*) FROM treinamento WHERE id = ? AND ativo = 1');
            $exists->execute([$trainingId]);
            if (!(int) $exists->fetchColumn()) {
                throw new InvalidArgumentException('O treinamento selecionado não está disponível.');
            }

            $stmt = $pdo->prepare('INSERT INTO treinamento_participante (pessoa_id, treinamento_id, status_participacao_id, progresso) VALUES (?, ?, ?, ?)');
            $stmt->execute([$personId, $trainingId, $statusId, $progress]);
            setFlash('success', 'Participante inscrito com sucesso.');
        }

        if ($action === 'atualizar') {
            $id = positiveInt($_POST['id'] ?? null);
            $statusId = positiveInt($_POST['status_participacao_id'] ?? null);
            $progress = filter_var($_POST['progresso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            if (!$id || !$statusId || $progress === false) {
                throw new InvalidArgumentException('O progresso deve estar entre 0 e 100.');
            }

            $stmt = $pdo->prepare('UPDATE treinamento_participante SET status_participacao_id = ?, progresso = ? WHERE id = ? AND ativo = 1');
            $stmt->execute([$statusId, $progress, $id]);
            setFlash('success', 'Progresso do participante atualizado.');
        }
    } catch (InvalidArgumentException $e) {
        setFlash('error', $e->getMessage());
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            setFlash('error', 'Esta pessoa já está inscrita no treinamento selecionado.');
        } else {
            setFlash('error', 'Não foi possível concluir a operação.');
        }
    }

    redirect('participantes.php');
}

$trainingFilter = positiveInt($_GET['treinamento'] ?? null);
$params = [];
$where = ['tp.ativo = 1'];
if ($trainingFilter) {
    $where[] = 'tp.treinamento_id = ?';
    $params[] = $trainingFilter;
}
$stmt = $pdo->prepare('SELECT tp.*, p.nome AS pessoa_nome, p.email AS pessoa_email, t.titulo AS treinamento_titulo, t.carga_horaria_minutos, sp.nome AS status_nome FROM treinamento_participante tp JOIN pessoa p ON p.id = tp.pessoa_id JOIN treinamento t ON t.id = tp.treinamento_id JOIN status_participacao sp ON sp.id = tp.status_participacao_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.data_inicio DESC, p.nome');
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

renderHeader('Participantes', 'participantes');
?>
<header class="page-header"><div><span class="eyebrow"><?= $admin ? 'Acompanhamento' : 'Consulta' ?></span><h1>Participantes</h1><p><?= $admin ? 'Inscreva pessoas e acompanhe a evolução em cada treinamento.' : 'Consulte os participantes e o progresso dos treinamentos.' ?></p></div></header>

<?php if ($admin): ?>
<section class="panel">
    <div class="panel-header"><div><h2>Nova inscrição</h2><p>Vincule uma pessoa a um treinamento institucional.</p></div></div>
    <form method="post" class="data-form compact">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="inscrever">
        <label>Treinamento *<select name="treinamento_id" required><option value="">Selecione</option><?php foreach ($trainings as $training): ?><option value="<?= (int) $training['id'] ?>"><?= e($training['titulo']) ?> — <?= e(formatDateTime($training['data_inicio'])) ?></option><?php endforeach; ?></select></label>
        <label>Participante *<select name="pessoa_id" required><option value="">Selecione</option><?php foreach ($people as $person): ?><option value="<?= (int) $person['id'] ?>"><?= e($person['nome']) ?> — <?= e($person['cargo']) ?></option><?php endforeach; ?></select></label>
        <label>Status *<select name="status_participacao_id" required><?php foreach ($participationStatuses as $status): ?><option value="<?= (int) $status['id'] ?>"<?= selected($status['nome'], 'Inscrito') ?>><?= e($status['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Progresso (%) *<input type="number" name="progresso" min="0" max="100" value="0" required></label>
        <div class="form-actions"><button class="btn primary" type="submit"><i class="fa-solid fa-user-plus"></i> Inscrever participante</button></div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><h2>Inscrições</h2><p>Atualize o status e o progresso de cada participante.</p></div></div>
    <form method="get" class="filter-form"><label>Treinamento<select name="treinamento"><option value="">Todos</option><?php foreach ($trainings as $training): ?><option value="<?= (int) $training['id'] ?>"<?= selected($trainingFilter, $training['id']) ?>><?= e($training['titulo']) ?></option><?php endforeach; ?></select></label><div class="form-actions"><a class="btn secondary" href="participantes.php">Limpar</a><button class="btn primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button></div></form>
    <?php if (!$enrollments): ?><div class="empty-state"><i class="fa-solid fa-users"></i><strong>Nenhuma inscrição encontrada</strong><p>Inscreva uma pessoa para começar o acompanhamento.</p></div>
    <?php else: ?><div class="table-wrapper"><table><thead><tr><th>Participante</th><th>Treinamento</th><th>Carga horária</th><th>Inscrito em</th><th>Status</th><th>Progresso</th><?php if ($admin): ?><th>Ações</th><?php endif; ?></tr></thead><tbody><?php foreach ($enrollments as $enrollment): ?><tr>
        <td><strong><?= e($enrollment['pessoa_nome']) ?></strong><br><small><?= e($enrollment['pessoa_email']) ?></small></td><td><?= e($enrollment['treinamento_titulo']) ?></td><td><?= e(formatHours($enrollment['carga_horaria_minutos'])) ?></td><td><?= e(formatDateTime($enrollment['data_inscricao'])) ?></td>
        <?php if ($admin): ?>
            <td><select form="atualizar-<?= (int) $enrollment['id'] ?>" class="status-input" name="status_participacao_id"><?php foreach ($participationStatuses as $status): ?><option value="<?= (int) $status['id'] ?>"<?= selected($enrollment['status_participacao_id'], $status['id']) ?>><?= e($status['nome']) ?></option><?php endforeach; ?></select></td>
            <td><input form="atualizar-<?= (int) $enrollment['id'] ?>" class="progress-input" type="number" name="progresso" min="0" max="100" value="<?= (int) $enrollment['progresso'] ?>">%</td><td><form id="atualizar-<?= (int) $enrollment['id'] ?>" method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="atualizar"><input type="hidden" name="id" value="<?= (int) $enrollment['id'] ?>"><div class="actions"><button class="btn secondary small" type="submit"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button><?php if ((int) $enrollment['progresso'] === 100): ?><a class="btn primary small" href="certificados.php?participante_id=<?= (int) $enrollment['id'] ?>"><i class="fa-solid fa-certificate"></i> Certificado</a><?php endif; ?></div></form></td>
        <?php else: ?>
            <td><span class="badge"><?= e($enrollment['status_nome']) ?></span></td><td><?= (int) $enrollment['progresso'] ?>%</td>
        <?php endif; ?>
    </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php renderFooter(); ?>
