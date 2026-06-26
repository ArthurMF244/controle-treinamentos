<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

try {
    $pdo = db();
    $metrics = $pdo->query(<<<'SQL'
        SELECT
            (SELECT COUNT(*) FROM treinamento WHERE ativo = 1) AS total_treinamentos,
            (SELECT COUNT(*) FROM treinamento WHERE ativo = 1 AND data_inicio <= NOW() AND data_fim >= NOW()) AS treinamentos_ativos,
            (SELECT COUNT(*) FROM treinamento_participante WHERE ativo = 1) AS participantes_inscritos,
            (SELECT COUNT(*) FROM certificado_treinamento) AS certificados_emitidos
    SQL)->fetch();
    $upcoming = $pdo->query(<<<'SQL'
        SELECT t.id, t.titulo, t.data_inicio, t.data_fim, l.nome AS local, st.nome AS status
        FROM treinamento t
        JOIN local_treinamento l ON l.id = t.local_treinamento_id
        JOIN status_treinamento st ON st.id = t.status_treinamento_id
        WHERE t.ativo = 1 AND t.data_inicio >= NOW()
        ORDER BY t.data_inicio LIMIT 6
    SQL)->fetchAll();
    jsonResponse(['data' => ['indicadores' => $metrics, 'proximos_treinamentos' => $upcoming]]);
} catch (Throwable $e) {
    internalError($e);
}
