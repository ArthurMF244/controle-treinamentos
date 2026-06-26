<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$pdo = db();
$admin = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$admin) {
        requireAdmin();
    }
    requireCsrf('certificados.php');

    try {
        $participantId = positiveInt($_POST['participante_id'] ?? null);
        if (!$participantId) {
            throw new InvalidArgumentException('Selecione uma participação válida.');
        }

        $stmt = $pdo->prepare(<<<'SQL'
            SELECT tp.id, tp.pessoa_id, tp.treinamento_id, t.carga_horaria_minutos
            FROM treinamento_participante tp
            JOIN treinamento t ON t.id = tp.treinamento_id
            WHERE tp.id = ? AND tp.ativo = 1 AND tp.progresso = 100
        SQL);
        $stmt->execute([$participantId]);
        $participant = $stmt->fetch();
        if (!$participant) {
            throw new InvalidArgumentException('O certificado só pode ser emitido para participante com 100% de progresso.');
        }

        $statusId = $pdo->query("SELECT id FROM status_certificado WHERE nome = 'Emitido' LIMIT 1")->fetchColumn();
        if (!$statusId) {
            throw new RuntimeException('Status de certificado não configurado.');
        }

        $code = 'CERT-' . date('Ymd') . '-' . $participantId . '-' . strtoupper(bin2hex(random_bytes(4)));
        $insert = $pdo->prepare('INSERT INTO certificado_treinamento (pessoa_id, treinamento_id, status_certificado_id, carga_horaria_minutos, codigo_validacao) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$participant['pessoa_id'], $participant['treinamento_id'], $statusId, $participant['carga_horaria_minutos'], $code]);
        setFlash('success', 'Certificado emitido com sucesso. Código de validação: ' . $code);
    } catch (InvalidArgumentException $e) {
        setFlash('error', $e->getMessage());
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            setFlash('error', 'Já existe um certificado emitido para esta participação.');
        } else {
            setFlash('error', 'Não foi possível emitir o certificado.');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Não foi possível emitir o certificado.');
    }

    redirect('certificados.php');
}

$eligible = $pdo->query(<<<'SQL'
    SELECT tp.id, p.nome AS pessoa_nome, t.titulo AS treinamento_titulo
    FROM treinamento_participante tp
    JOIN pessoa p ON p.id = tp.pessoa_id
    JOIN treinamento t ON t.id = tp.treinamento_id
    LEFT JOIN certificado_treinamento ct ON ct.pessoa_id = tp.pessoa_id AND ct.treinamento_id = tp.treinamento_id
    WHERE tp.ativo = 1 AND tp.progresso = 100 AND ct.id IS NULL
    ORDER BY p.nome, t.titulo
SQL)->fetchAll();
$preselected = positiveInt($_GET['participante_id'] ?? null);
$certificates = $pdo->query(<<<'SQL'
    SELECT ct.*, p.nome AS pessoa_nome, p.email AS pessoa_email, t.titulo AS treinamento_titulo, sc.nome AS status_nome
    FROM certificado_treinamento ct
    JOIN pessoa p ON p.id = ct.pessoa_id
    JOIN treinamento t ON t.id = ct.treinamento_id
    JOIN status_certificado sc ON sc.id = ct.status_certificado_id
    ORDER BY ct.data_emissao DESC, ct.id DESC
SQL)->fetchAll();

renderHeader('Certificados', 'certificados');
?>
<header class="page-header"><div><span class="eyebrow"><?= $admin ? 'Conclusão' : 'Consulta' ?></span><h1>Certificados</h1><p><?= $admin ? 'Emita e consulte os certificados dos treinamentos concluídos.' : 'Consulte os certificados emitidos nos treinamentos concluídos.' ?></p></div></header>

<?php if ($admin): ?>
<section class="panel">
    <div class="panel-header"><div><h2>Emitir certificado</h2><p>Disponível apenas para participantes com progresso de 100%.</p></div></div>
    <?php if (!$eligible): ?><div class="notice">Não há participantes elegíveis para emissão no momento.</div>
    <?php else: ?><form method="post" class="data-form compact"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label class="full-row">Participação elegível<select name="participante_id" required><option value="">Selecione</option><?php foreach ($eligible as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($preselected, $item['id']) ?>><?= e($item['pessoa_nome']) ?> — <?= e($item['treinamento_titulo']) ?></option><?php endforeach; ?></select></label><div class="form-actions"><button class="btn primary" type="submit"><i class="fa-solid fa-certificate"></i> Emitir certificado</button></div></form><?php endif; ?>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><h2>Certificados emitidos</h2><p>Lista de certificados disponíveis para validação.</p></div></div>
    <?php if (!$certificates): ?><div class="empty-state"><i class="fa-solid fa-certificate"></i><strong>Nenhum certificado emitido</strong><p>Conclua o progresso de um participante para disponibilizar a emissão.</p></div>
    <?php else: ?><div class="table-wrapper"><table><thead><tr><th>Participante</th><th>Treinamento</th><th>Emissão</th><th>Carga horária</th><th>Status</th><th>Código de validação</th></tr></thead><tbody><?php foreach ($certificates as $certificate): ?><tr><td><strong><?= e($certificate['pessoa_nome']) ?></strong><br><small><?= e($certificate['pessoa_email']) ?></small></td><td><?= e($certificate['treinamento_titulo']) ?></td><td><?= e(formatDateTime($certificate['data_emissao'])) ?></td><td><?= e(formatHours($certificate['carga_horaria_minutos'])) ?></td><td><span class="badge"><?= e($certificate['status_nome']) ?></span></td><td><strong><?= e($certificate['codigo_validacao']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php renderFooter(); ?>
