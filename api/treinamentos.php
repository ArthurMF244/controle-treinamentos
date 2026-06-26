<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

if (in_array(method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    requireApiAdmin();
}

function trainingApiPayload(array $data): array
{
    requireFields($data, [
        'titulo', 'descricao', 'data_inicio', 'data_fim', 'carga_horaria_minutos',
        'tipo_treinamento_id', 'status_treinamento_id', 'local_treinamento_id', 'instrutor',
    ]);
    $inicio = apiDateTime($data['data_inicio']);
    $fim = apiDateTime($data['data_fim']);
    $carga = filter_var($data['carga_horaria_minutos'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tipo = apiId($data['tipo_treinamento_id']);
    $status = apiId($data['status_treinamento_id']);
    $local = apiId($data['local_treinamento_id']);
    $responsavel = apiId($data['responsavel_pessoa_id'] ?? null);

    if (!$inicio || !$fim || strtotime($fim) <= strtotime($inicio) || $carga === false || !$tipo || !$status || !$local) {
        throw new InvalidArgumentException('Dados do treinamento inválidos.');
    }

    return [
        trim((string) $data['titulo']), trim((string) $data['descricao']), $inicio, $fim, (int) $carga,
        $tipo, $status, $local, $responsavel, trim((string) $data['instrutor']),
    ];
}

try {
    $pdo = db();
    $id = getId();

    if (method() === 'GET' && $id) {
        $stmt = $pdo->prepare(<<<'SQL'
            SELECT t.*, tt.nome AS tipo_nome, st.nome AS status_nome, l.nome AS local_nome,
                   p.nome AS responsavel_nome,
                   (SELECT COUNT(*) FROM treinamento_participante tp WHERE tp.treinamento_id = t.id AND tp.ativo = 1) AS participantes_inscritos
            FROM treinamento t
            JOIN tipo_treinamento tt ON tt.id = t.tipo_treinamento_id
            JOIN status_treinamento st ON st.id = t.status_treinamento_id
            JOIN local_treinamento l ON l.id = t.local_treinamento_id
            LEFT JOIN pessoa p ON p.id = t.responsavel_pessoa_id
            WHERE t.id = ?
        SQL);
        $stmt->execute([$id]);
        $training = $stmt->fetch();
        if (!$training) {
            jsonResponse(['erro' => 'Treinamento não encontrado.'], 404);
        }
        jsonResponse(['data' => $training]);
    }

    if (method() === 'GET') {
        $where = apiIsAdmin() && boolParam('incluir_inativos') ? ['1 = 1'] : ['t.ativo = 1'];
        $params = [];
        foreach (['status' => 't.status_treinamento_id', 'tipo' => 't.tipo_treinamento_id', 'responsavel' => 't.responsavel_pessoa_id'] as $query => $column) {
            $value = apiId($_GET[$query] ?? null);
            if ($value) { $where[] = "{$column} = :{$query}"; $params[":{$query}"] = $value; }
        }
        $search = trim((string) ($_GET['q'] ?? ''));
        if ($search !== '') { $where[] = '(t.titulo LIKE :q OR t.descricao LIKE :q OR t.instrutor LIKE :q)'; $params[':q'] = "%{$search}%"; }
        $stmt = $pdo->prepare('SELECT t.*, tt.nome AS tipo_nome, st.nome AS status_nome, l.nome AS local_nome, p.nome AS responsavel_nome FROM treinamento t JOIN tipo_treinamento tt ON tt.id = t.tipo_treinamento_id JOIN status_treinamento st ON st.id = t.status_treinamento_id JOIN local_treinamento l ON l.id = t.local_treinamento_id LEFT JOIN pessoa p ON p.id = t.responsavel_pessoa_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.data_inicio DESC');
        $stmt->execute($params);
        jsonResponse(['data' => $stmt->fetchAll()]);
    }

    if (method() === 'POST') {
        $values = trainingApiPayload(requestData());
        $stmt = $pdo->prepare('INSERT INTO treinamento (titulo, descricao, data_inicio, data_fim, carga_horaria_minutos, tipo_treinamento_id, status_treinamento_id, local_treinamento_id, responsavel_pessoa_id, instrutor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute($values);
        jsonResponse(['mensagem' => 'Treinamento criado com sucesso.', 'id' => (int) $pdo->lastInsertId()], 201);
    }

    if (in_array(method(), ['PUT', 'PATCH'], true) && $id) {
        $values = trainingApiPayload(requestData());
        $values[] = $id;
        $stmt = $pdo->prepare('UPDATE treinamento SET titulo = ?, descricao = ?, data_inicio = ?, data_fim = ?, carga_horaria_minutos = ?, tipo_treinamento_id = ?, status_treinamento_id = ?, local_treinamento_id = ?, responsavel_pessoa_id = ?, instrutor = ? WHERE id = ?');
        $stmt->execute($values);
        if (!$stmt->rowCount()) {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM treinamento WHERE id = ?');
            $exists->execute([$id]);
            if (!(int) $exists->fetchColumn()) { jsonResponse(['erro' => 'Treinamento não encontrado.'], 404); }
        }
        jsonResponse(['mensagem' => 'Treinamento atualizado com sucesso.']);
    }

    if (method() === 'DELETE' && $id) {
        $stmt = $pdo->prepare('UPDATE treinamento SET ativo = 0 WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->rowCount()) { jsonResponse(['erro' => 'Treinamento não encontrado ou já inativo.'], 404); }
        jsonResponse(['mensagem' => 'Treinamento inativado com sucesso.']);
    }

    jsonResponse(['erro' => 'Método ou identificador inválido.'], 405);
} catch (InvalidArgumentException $e) {
    jsonResponse(['erro' => $e->getMessage()], 422);
} catch (Throwable $e) {
    internalError($e);
}
