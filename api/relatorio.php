<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

try {
    if (method() !== 'GET') {
        jsonResponse(['erro' => 'Método não permitido.'], 405);
    }

    $where = ['tp.ativo = 1'];
    $params = [];
    foreach (['treinamento' => 't.id', 'status' => 'st.id', 'participante' => 'p.id', 'responsavel' => 'r.id'] as $query => $column) {
        $value = apiId($_GET[$query] ?? null);
        if ($value) {
            $where[] = "{$column} = :{$query}";
            $params[":{$query}"] = $value;
        }
    }

    $sql = 'SELECT t.id AS treinamento_id, t.titulo AS treinamento, t.carga_horaria_minutos, st.nome AS status_treinamento, p.id AS participante_id, p.nome AS participante, sp.nome AS status_participacao, tp.progresso, r.id AS responsavel_id, r.nome AS responsavel, l.nome AS local FROM treinamento_participante tp JOIN treinamento t ON t.id = tp.treinamento_id JOIN pessoa p ON p.id = tp.pessoa_id JOIN status_participacao sp ON sp.id = tp.status_participacao_id JOIN status_treinamento st ON st.id = t.status_treinamento_id JOIN local_treinamento l ON l.id = t.local_treinamento_id LEFT JOIN pessoa r ON r.id = t.responsavel_pessoa_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.titulo, p.nome';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['data' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    internalError($e);
}
