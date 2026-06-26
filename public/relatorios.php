<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$pdo = db();
$trainings = $pdo->query('SELECT id, titulo FROM treinamento WHERE ativo = 1 ORDER BY titulo')->fetchAll();
$trainingStatuses = $pdo->query('SELECT id, nome FROM status_treinamento ORDER BY nome')->fetchAll();
$people = $pdo->query('SELECT id, nome FROM pessoa WHERE ativo = 1 ORDER BY nome')->fetchAll();
$filters = [
    'treinamento' => positiveInt($_GET['treinamento'] ?? null),
    'status' => positiveInt($_GET['status'] ?? null),
    'participante' => positiveInt($_GET['participante'] ?? null),
    'responsavel' => positiveInt($_GET['responsavel'] ?? null),
];
$where = ['tp.ativo = 1'];
$params = [];
foreach (['treinamento' => 't.id', 'status' => 'st.id', 'participante' => 'p.id', 'responsavel' => 'r.id'] as $filter => $column) {
    if ($filters[$filter]) { $where[] = "{$column} = ?"; $params[] = $filters[$filter]; }
}
$stmt = $pdo->prepare('SELECT t.titulo, t.carga_horaria_minutos, p.nome AS participante_nome, sp.nome AS participacao_status, tp.progresso, r.nome AS responsavel_nome, l.nome AS local_nome, st.nome AS treinamento_status FROM treinamento_participante tp JOIN treinamento t ON t.id = tp.treinamento_id JOIN pessoa p ON p.id = tp.pessoa_id JOIN status_participacao sp ON sp.id = tp.status_participacao_id JOIN status_treinamento st ON st.id = t.status_treinamento_id JOIN local_treinamento l ON l.id = t.local_treinamento_id LEFT JOIN pessoa r ON r.id = t.responsavel_pessoa_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.titulo, p.nome');
$stmt->execute($params);
$rows = $stmt->fetchAll();

renderHeader('Relatórios', 'relatorios');
?>
<header class="page-header"><div><span class="eyebrow">Consulta</span><h1>Relatórios</h1><p>Consulte a participação e a evolução por treinamento.</p></div></header>
<section class="panel">
    <div class="panel-header"><div><h2>Filtros do relatório</h2><p>Combine os filtros para uma consulta mais precisa.</p></div></div>
    <form method="get" class="filter-form">
        <label>Treinamento<select name="treinamento"><option value="">Todos</option><?php foreach ($trainings as $training): ?><option value="<?= (int) $training['id'] ?>"<?= selected($filters['treinamento'], $training['id']) ?>><?= e($training['titulo']) ?></option><?php endforeach; ?></select></label>
        <label>Status do treinamento<select name="status"><option value="">Todos</option><?php foreach ($trainingStatuses as $status): ?><option value="<?= (int) $status['id'] ?>"<?= selected($filters['status'], $status['id']) ?>><?= e($status['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Participante<select name="participante"><option value="">Todos</option><?php foreach ($people as $person): ?><option value="<?= (int) $person['id'] ?>"<?= selected($filters['participante'], $person['id']) ?>><?= e($person['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Responsável<select name="responsavel"><option value="">Todos</option><?php foreach ($people as $person): ?><option value="<?= (int) $person['id'] ?>"<?= selected($filters['responsavel'], $person['id']) ?>><?= e($person['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions full-row"><a class="btn secondary" href="relatorios.php">Limpar filtros</a><button class="btn primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button></div>
    </form>
    <div class="panel-header"><div><h2>Resultado da consulta</h2><p><?= count($rows) ?> registro(s) encontrado(s).</p></div></div>
    <?php if (!$rows): ?><div class="empty-state"><i class="fa-solid fa-table-list"></i><strong>Nenhum registro encontrado</strong><p>Experimente alterar os filtros de consulta.</p></div>
    <?php else: ?><div class="table-wrapper"><table><thead><tr><th>Treinamento</th><th>Participante</th><th>Status da participação</th><th>Progresso</th><th>Carga horária</th><th>Responsável</th><th>Local</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['titulo']) ?></strong><br><small><?= e($row['treinamento_status']) ?></small></td><td><?= e($row['participante_nome']) ?></td><td><span class="badge"><?= e($row['participacao_status']) ?></span></td><td><?= (int) $row['progresso'] ?>%</td><td><?= e(formatHours($row['carga_horaria_minutos'])) ?></td><td><?= e($row['responsavel_nome'] ?: 'Não informado') ?></td><td><?= e($row['local_nome']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php renderFooter(); ?>
