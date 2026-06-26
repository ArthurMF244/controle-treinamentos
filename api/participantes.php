<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

if (in_array(method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    requireApiAdmin();
}

try {
    $pdo = db();
    $id = getId();

    if (method() === 'GET') {
        $where = ['tp.ativo = 1'];
        $params = [];
        $trainingId = apiId($_GET['treinamento'] ?? null);
        $personId = apiId($_GET['pessoa'] ?? null);
        if ($trainingId) { $where[] = 'tp.treinamento_id = :treinamento'; $params[':treinamento'] = $trainingId; }
        if ($personId) { $where[] = 'tp.pessoa_id = :pessoa'; $params[':pessoa'] = $personId; }
        if ($id) { $where[] = 'tp.id = :id'; $params[':id'] = $id; }
        $stmt = $pdo->prepare('SELECT tp.*, p.nome AS pessoa_nome, p.email AS pessoa_email, t.titulo AS treinamento_titulo, t.carga_horaria_minutos, sp.nome AS status_nome FROM treinamento_participante tp JOIN pessoa p ON p.id = tp.pessoa_id JOIN treinamento t ON t.id = tp.treinamento_id JOIN status_participacao sp ON sp.id = tp.status_participacao_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.data_inicio DESC, p.nome');
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if ($id && !$rows) { jsonResponse(['erro' => 'Participação não encontrada.'], 404); }
        jsonResponse(['data' => $id ? $rows[0] : $rows]);
    }

    if (method() === 'POST') {
        $data = requestData();
        requireFields($data, ['pessoa_id', 'treinamento_id', 'status_participacao_id', 'progresso']);
        $personId = apiId($data['pessoa_id']);
        $trainingId = apiId($data['treinamento_id']);
        $statusId = apiId($data['status_participacao_id']);
        $progress = apiProgress($data['progresso']);
        if (!$personId || !$trainingId || !$statusId || $progress === null) { jsonResponse(['erro' => 'Dados de participação inválidos.'], 422); }
        $stmt = $pdo->prepare('INSERT INTO treinamento_participante (pessoa_id, treinamento_id, status_participacao_id, progresso) VALUES (?, ?, ?, ?)');
        $stmt->execute([$personId, $trainingId, $statusId, $progress]);
        jsonResponse(['mensagem' => 'Participante inscrito com sucesso.', 'id' => (int) $pdo->lastInsertId()], 201);
    }

    if (in_array(method(), ['PUT', 'PATCH'], true) && $id) {
        $data = requestData();
        requireFields($data, ['status_participacao_id', 'progresso']);
        $statusId = apiId($data['status_participacao_id']);
        $progress = apiProgress($data['progresso']);
        if (!$statusId || $progress === null) { jsonResponse(['erro' => 'Status ou progresso inválido.'], 422); }
        $stmt = $pdo->prepare('UPDATE treinamento_participante SET status_participacao_id = ?, progresso = ? WHERE id = ? AND ativo = 1');
        $stmt->execute([$statusId, $progress, $id]);
        if (!$stmt->rowCount()) { jsonResponse(['erro' => 'Participação não encontrada.'], 404); }
        jsonResponse(['mensagem' => 'Participação atualizada com sucesso.']);
    }

    jsonResponse(['erro' => 'Método ou identificador inválido.'], 405);
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') { jsonResponse(['erro' => 'Participação duplicada ou referência inválida.'], 409); }
    internalError($e);
} catch (Throwable $e) {
    internalError($e);
}
