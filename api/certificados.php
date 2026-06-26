<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

try {
    $pdo = db();
    if (method() === 'GET') {
        $stmt = $pdo->query('SELECT ct.*, p.nome AS pessoa_nome, p.email AS pessoa_email, t.titulo AS treinamento_titulo, sc.nome AS status_nome FROM certificado_treinamento ct JOIN pessoa p ON p.id = ct.pessoa_id JOIN treinamento t ON t.id = ct.treinamento_id JOIN status_certificado sc ON sc.id = ct.status_certificado_id ORDER BY ct.data_emissao DESC');
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    if (method() === 'POST') {
        $data = requestData();
        $participantId = apiId($data['participante_id'] ?? null);
        if (!$participantId) { jsonResponse(['erro' => 'participante_id é obrigatório.'], 422); }
        $participant = $pdo->prepare('SELECT tp.pessoa_id, tp.treinamento_id, t.carga_horaria_minutos FROM treinamento_participante tp JOIN treinamento t ON t.id = tp.treinamento_id WHERE tp.id = ? AND tp.ativo = 1 AND tp.progresso = 100');
        $participant->execute([$participantId]);
        $participant = $participant->fetch();
        if (!$participant) { jsonResponse(['erro' => 'Participante não elegível para certificado.'], 422); }
        $statusId = $pdo->query("SELECT id FROM status_certificado WHERE nome = 'Emitido' LIMIT 1")->fetchColumn();
        $code = 'CERT-' . date('Ymd') . '-' . $participantId . '-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('INSERT INTO certificado_treinamento (pessoa_id, treinamento_id, status_certificado_id, carga_horaria_minutos, codigo_validacao) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$participant['pessoa_id'], $participant['treinamento_id'], $statusId, $participant['carga_horaria_minutos'], $code]);
        jsonResponse(['mensagem' => 'Certificado emitido com sucesso.', 'codigo_validacao' => $code, 'id' => (int) $pdo->lastInsertId()], 201);
    }
    jsonResponse(['erro' => 'Método não permitido.'], 405);
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') { jsonResponse(['erro' => 'Já existe certificado para esta participação.'], 409); }
    internalError($e);
} catch (Throwable $e) {
    internalError($e);
}
