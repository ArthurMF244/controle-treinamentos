<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$pdo = db();
$admin = isAdmin();
$metrics = $pdo->query(<<<'SQL'
    SELECT
        (SELECT COUNT(*) FROM treinamento WHERE ativo = 1) AS total_treinamentos,
        (SELECT COUNT(*) FROM treinamento WHERE ativo = 1 AND data_inicio <= NOW() AND data_fim >= NOW()) AS treinamentos_ativos,
        (SELECT COUNT(*) FROM treinamento_participante WHERE ativo = 1) AS participantes_inscritos,
        (SELECT COUNT(*) FROM certificado_treinamento) AS certificados_emitidos,
        (SELECT COALESCE(ROUND(AVG(progresso)), 0) FROM treinamento_participante WHERE ativo = 1) AS progresso_medio
SQL)->fetch();

$upcoming = $pdo->query(<<<'SQL'
    SELECT t.titulo, t.data_inicio, t.data_fim, l.nome AS local_nome, st.nome AS status_nome, p.nome AS responsavel_nome,
           (SELECT COUNT(*) FROM treinamento_participante tp WHERE tp.treinamento_id = t.id AND tp.ativo = 1) AS participantes
    FROM treinamento t
    JOIN local_treinamento l ON l.id = t.local_treinamento_id
    JOIN status_treinamento st ON st.id = t.status_treinamento_id
    LEFT JOIN pessoa p ON p.id = t.responsavel_pessoa_id
    WHERE t.ativo = 1 AND t.data_inicio >= NOW()
    ORDER BY t.data_inicio
    LIMIT 6
SQL)->fetchAll();

renderHeader('Dashboard', 'dashboard');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Visão geral</span>
        <h1>Dashboard</h1>
        <p><?= $admin ? 'Acompanhe os treinamentos institucionais em um só lugar.' : 'Consulte a agenda e o andamento dos treinamentos.' ?></p>
    </div>
    <?php if ($admin): ?><a class="btn primary" href="treinamentos.php?novo=1"><i class="fa-solid fa-plus"></i> Novo treinamento</a><?php endif; ?>
</header>

<section class="metrics" aria-label="Indicadores de treinamentos">
    <article class="metric-card"><span class="metric-icon blue"><i class="fa-solid fa-graduation-cap"></i></span><div><small>Total de treinamentos</small><strong><?= (int) $metrics['total_treinamentos'] ?></strong></div></article>
    <article class="metric-card"><span class="metric-icon green"><i class="fa-solid fa-person-chalkboard"></i></span><div><small>Treinamentos ativos</small><strong><?= (int) $metrics['treinamentos_ativos'] ?></strong></div></article>
    <?php if ($admin): ?>
        <article class="metric-card"><span class="metric-icon orange"><i class="fa-solid fa-users"></i></span><div><small>Participantes inscritos</small><strong><?= (int) $metrics['participantes_inscritos'] ?></strong></div></article>
        <article class="metric-card"><span class="metric-icon purple"><i class="fa-solid fa-certificate"></i></span><div><small>Certificados emitidos</small><strong><?= (int) $metrics['certificados_emitidos'] ?></strong></div></article>
    <?php endif; ?>
    <article class="metric-card"><span class="metric-icon blue"><i class="fa-solid fa-chart-simple"></i></span><div><small>Progresso médio</small><strong><?= (int) $metrics['progresso_medio'] ?>%</strong></div></article>
</section>

<section class="panel">
    <div class="panel-header">
        <div><h2>Próximos treinamentos</h2><p>Agenda dos próximos eventos cadastrados.</p></div>
        <a class="text-link" href="treinamentos.php">Ver todos <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <?php if (!$upcoming): ?>
        <div class="empty-state"><i class="fa-regular fa-calendar"></i><strong>Nenhum treinamento próximo</strong><p>Cadastre um treinamento para começar a organizar a agenda.</p></div>
    <?php else: ?>
        <div class="table-wrapper"><table><thead><tr><th>Treinamento</th><th>Início</th><th>Local</th><th>Responsável</th><?php if ($admin): ?><th>Participantes</th><?php endif; ?><th>Status</th></tr></thead><tbody>
        <?php foreach ($upcoming as $training): ?>
            <tr><td><strong><?= e($training['titulo']) ?></strong><br><small>Até <?= e(formatDateTime($training['data_fim'])) ?></small></td><td><?= e(formatDateTime($training['data_inicio'])) ?></td><td><?= e($training['local_nome']) ?></td><td><?= e($training['responsavel_nome'] ?: 'Não informado') ?></td><?php if ($admin): ?><td><?= (int) $training['participantes'] ?></td><?php endif; ?><td><span class="badge"><?= e($training['status_nome']) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
