<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

$pdo = db();
$admin = isAdmin();

function trainingLookups(PDO $pdo): array
{
    return [
        'tipos' => $pdo->query('SELECT id, nome FROM tipo_treinamento WHERE ativo = 1 ORDER BY nome')->fetchAll(),
        'status' => $pdo->query('SELECT id, nome FROM status_treinamento ORDER BY nome')->fetchAll(),
        'locais' => $pdo->query('SELECT id, nome, capacidade FROM local_treinamento WHERE ativo = 1 ORDER BY nome')->fetchAll(),
        'pessoas' => $pdo->query('SELECT p.id, p.nome, p.cargo, a.nome AS area_nome FROM pessoa p JOIN area a ON a.id = p.area_id WHERE p.ativo = 1 ORDER BY p.nome')->fetchAll(),
    ];
}

function validTrainingData(array $data): array
{
    $titulo = trim((string) ($data['titulo'] ?? ''));
    $descricao = trim((string) ($data['descricao'] ?? ''));
    $instrutor = trim((string) ($data['instrutor'] ?? ''));
    $inicio = dateTimeToSql($data['data_inicio'] ?? '');
    $fim = dateTimeToSql($data['data_fim'] ?? '');
    $cargaHoras = filter_var($data['carga_horaria_horas'] ?? null, FILTER_VALIDATE_FLOAT);
    $tipo = positiveInt($data['tipo_treinamento_id'] ?? null);
    $status = positiveInt($data['status_treinamento_id'] ?? null);
    $local = positiveInt($data['local_treinamento_id'] ?? null);
    $responsavel = positiveInt($data['responsavel_pessoa_id'] ?? null);

    if ($titulo === '' || $descricao === '' || $instrutor === '' || !$inicio || !$fim || $cargaHoras === false || $cargaHoras <= 0 || !$tipo || !$status || !$local) {
        throw new InvalidArgumentException('Preencha todos os campos obrigatórios com valores válidos.');
    }
    if (strtotime($fim) <= strtotime($inicio)) {
        throw new InvalidArgumentException('A data de término deve ser posterior à data de início.');
    }

    return [$titulo, $descricao, $inicio, $fim, (int) round($cargaHoras * 60), $tipo, $status, $local, $responsavel, $instrutor];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$admin) {
        requireAdmin();
    }
    requireCsrf('treinamentos.php');
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'salvar') {
            [$titulo, $descricao, $inicio, $fim, $carga, $tipo, $status, $local, $responsavel, $instrutor] = validTrainingData($_POST);
            $id = positiveInt($_POST['id'] ?? null);

            if ($id) {
                $stmt = $pdo->prepare(<<<'SQL'
                    UPDATE treinamento SET titulo = ?, descricao = ?, data_inicio = ?, data_fim = ?, carga_horaria_minutos = ?,
                    tipo_treinamento_id = ?, status_treinamento_id = ?, local_treinamento_id = ?, responsavel_pessoa_id = ?, instrutor = ?
                    WHERE id = ?
                SQL);
                $stmt->execute([$titulo, $descricao, $inicio, $fim, $carga, $tipo, $status, $local, $responsavel, $instrutor, $id]);
                setFlash('success', 'Treinamento atualizado com sucesso.');
            } else {
                $stmt = $pdo->prepare(<<<'SQL'
                    INSERT INTO treinamento (titulo, descricao, data_inicio, data_fim, carga_horaria_minutos, tipo_treinamento_id,
                    status_treinamento_id, local_treinamento_id, responsavel_pessoa_id, instrutor)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                SQL);
                $stmt->execute([$titulo, $descricao, $inicio, $fim, $carga, $tipo, $status, $local, $responsavel, $instrutor]);
                setFlash('success', 'Treinamento cadastrado com sucesso.');
            }
        }

        if ($action === 'inativar') {
            $id = positiveInt($_POST['id'] ?? null);
            if (!$id) {
                throw new InvalidArgumentException('Treinamento inválido.');
            }
            $pdo->prepare('UPDATE treinamento SET ativo = 0 WHERE id = ?')->execute([$id]);
            setFlash('success', 'Treinamento inativado com sucesso.');
        }
    } catch (InvalidArgumentException $e) {
        setFlash('error', $e->getMessage());
    } catch (PDOException $e) {
        setFlash('error', 'Não foi possível salvar o treinamento. Confira os dados informados.');
    }

    redirect('treinamentos.php');
}

$editId = positiveInt($_GET['editar'] ?? null);
if (!$admin && (isset($_GET['novo']) || $editId)) {
    setFlash('error', 'Acesso não permitido para o seu perfil.');
    redirect('treinamentos.php');
}

$lookups = trainingLookups($pdo);
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM treinamento WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        setFlash('error', 'Treinamento não encontrado.');
        redirect('treinamentos.php');
    }
}

$showForm = $admin && (isset($_GET['novo']) || $editing !== null);
$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => positiveInt($_GET['status'] ?? null),
    'tipo' => positiveInt($_GET['tipo'] ?? null),
    'responsavel' => positiveInt($_GET['responsavel'] ?? null),
];
$where = ['t.ativo = 1'];
$params = [];
if ($filters['q'] !== '') { $where[] = '(t.titulo LIKE :q OR t.descricao LIKE :q OR t.instrutor LIKE :q)'; $params[':q'] = '%' . $filters['q'] . '%'; }
if ($filters['status']) { $where[] = 't.status_treinamento_id = :status'; $params[':status'] = $filters['status']; }
if ($filters['tipo']) { $where[] = 't.tipo_treinamento_id = :tipo'; $params[':tipo'] = $filters['tipo']; }
if ($filters['responsavel']) { $where[] = 't.responsavel_pessoa_id = :responsavel'; $params[':responsavel'] = $filters['responsavel']; }

$stmt = $pdo->prepare('SELECT t.*, tt.nome AS tipo_nome, st.nome AS status_nome, l.nome AS local_nome, p.nome AS responsavel_nome, (SELECT COUNT(*) FROM treinamento_participante tp WHERE tp.treinamento_id = t.id AND tp.ativo = 1) AS inscritos FROM treinamento t JOIN tipo_treinamento tt ON tt.id = t.tipo_treinamento_id JOIN status_treinamento st ON st.id = t.status_treinamento_id JOIN local_treinamento l ON l.id = t.local_treinamento_id LEFT JOIN pessoa p ON p.id = t.responsavel_pessoa_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.data_inicio DESC');
$stmt->execute($params);
$trainings = $stmt->fetchAll();

renderHeader('Treinamentos', 'treinamentos');
?>
<header class="page-header"><div><span class="eyebrow"><?= $admin ? 'Gestão' : 'Consulta' ?></span><h1>Treinamentos</h1><p><?= $admin ? 'Cadastre e acompanhe as capacitações institucionais.' : 'Consulte as capacitações institucionais disponíveis.' ?></p></div><?php if ($admin): ?><a class="btn primary" href="treinamentos.php?novo=1"><i class="fa-solid fa-plus"></i> Novo treinamento</a><?php endif; ?></header>

<?php if ($showForm): $form = $editing ?: []; ?>
<section class="panel">
    <div class="panel-header"><div><h2><?= $editing ? 'Editar treinamento' : 'Novo treinamento' ?></h2><p>Informe os dados da capacitação.</p></div><a class="text-link" href="treinamentos.php">Fechar</a></div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="salvar"><input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
        <label class="full-row">Título *<input name="titulo" maxlength="180" value="<?= e($form['titulo'] ?? '') ?>" required></label>
        <label class="full-row">Descrição *<textarea name="descricao" required><?= e($form['descricao'] ?? '') ?></textarea></label>
        <label>Data de início *<input type="datetime-local" name="data_inicio" value="<?= e(inputDateTime($form['data_inicio'] ?? null)) ?>" required></label>
        <label>Data de término *<input type="datetime-local" name="data_fim" value="<?= e(inputDateTime($form['data_fim'] ?? null)) ?>" required></label>
        <label>Carga horária (horas) *<input type="number" name="carga_horaria_horas" min="0.25" step="0.25" value="<?= e(isset($form['carga_horaria_minutos']) ? (string) ($form['carga_horaria_minutos'] / 60) : '') ?>" required></label>
        <label>Instrutor *<input name="instrutor" maxlength="150" value="<?= e($form['instrutor'] ?? '') ?>" required></label>
        <label>Tipo *<select name="tipo_treinamento_id" required><option value="">Selecione</option><?php foreach ($lookups['tipos'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($form['tipo_treinamento_id'] ?? '', $item['id']) ?>><?= e($item['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Status *<select name="status_treinamento_id" required><option value="">Selecione</option><?php foreach ($lookups['status'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($form['status_treinamento_id'] ?? '', $item['id']) ?>><?= e($item['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Local *<select name="local_treinamento_id" required><option value="">Selecione</option><?php foreach ($lookups['locais'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($form['local_treinamento_id'] ?? '', $item['id']) ?>><?= e($item['nome']) ?> (<?= (int) $item['capacidade'] ?> pessoas)</option><?php endforeach; ?></select></label>
        <label>Responsável<select name="responsavel_pessoa_id"><option value="">Não informado</option><?php foreach ($lookups['pessoas'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($form['responsavel_pessoa_id'] ?? '', $item['id']) ?>><?= e($item['nome']) ?> — <?= e($item['cargo']) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions full-row"><a class="btn secondary" href="treinamentos.php">Cancelar</a><button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar treinamento</button></div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-header"><div><h2>Treinamentos cadastrados</h2><p>Use os filtros para localizar uma capacitação.</p></div></div>
    <form method="get" class="filter-form">
        <label>Buscar<input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Título ou instrutor"></label>
        <label>Status<select name="status"><option value="">Todos</option><?php foreach ($lookups['status'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($filters['status'], $item['id']) ?>><?= e($item['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Tipo<select name="tipo"><option value="">Todos</option><?php foreach ($lookups['tipos'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($filters['tipo'], $item['id']) ?>><?= e($item['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Responsável<select name="responsavel"><option value="">Todos</option><?php foreach ($lookups['pessoas'] as $item): ?><option value="<?= (int) $item['id'] ?>"<?= selected($filters['responsavel'], $item['id']) ?>><?= e($item['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions full-row"><a class="btn secondary" href="treinamentos.php">Limpar filtros</a><button class="btn primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button></div>
    </form>
    <?php if (!$trainings): ?><div class="empty-state"><i class="fa-solid fa-graduation-cap"></i><strong>Nenhum treinamento encontrado</strong><p>Altere os filtros ou cadastre um novo treinamento.</p></div>
    <?php else: ?><div class="table-wrapper"><table><thead><tr><th>Treinamento</th><th>Período</th><th>Tipo / status</th><th>Local</th><th>Responsável</th><th>Inscritos</th><?php if ($admin): ?><th>Ações</th><?php endif; ?></tr></thead><tbody><?php foreach ($trainings as $training): ?><tr>
        <td><strong><?= e($training['titulo']) ?></strong><br><small><?= e(formatHours($training['carga_horaria_minutos'])) ?> · <?= e($training['instrutor']) ?></small></td><td><?= e(formatDateTime($training['data_inicio'])) ?><br><small>até <?= e(formatDateTime($training['data_fim'])) ?></small></td><td><?= e($training['tipo_nome']) ?><br><span class="badge"><?= e($training['status_nome']) ?></span></td><td><?= e($training['local_nome']) ?></td><td><?= e($training['responsavel_nome'] ?: 'Não informado') ?></td><td><?= (int) $training['inscritos'] ?></td><?php if ($admin): ?><td><div class="actions"><a class="btn secondary small" href="treinamentos.php?editar=<?= (int) $training['id'] ?>"><i class="fa-solid fa-pen"></i> Editar</a><form method="post" class="inline-form" onsubmit="return confirm('Inativar este treinamento?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="inativar"><input type="hidden" name="id" value="<?= (int) $training['id'] ?>"><button class="btn danger small" type="submit"><i class="fa-solid fa-ban"></i> Inativar</button></form></div></td><?php endif; ?>
    </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php renderFooter(); ?>
