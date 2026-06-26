<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

try {
    $pdo = db();
    if (method() === 'GET') {
        $where = boolParam('ativas') ? 'WHERE p.ativo = 1' : '';
        $stmt = $pdo->query("SELECT p.id, p.nome, p.email, p.cargo, p.area_id, p.ativo, a.nome AS area_nome FROM pessoa p JOIN area a ON a.id = p.area_id {$where} ORDER BY p.nome");
        jsonResponse(['data' => $stmt->fetchAll()]);
    }

    if (method() === 'POST') {
        $data = requestData();
        requireFields($data, ['nome', 'email', 'cargo', 'area_id']);
        $email = filter_var(trim((string) $data['email']), FILTER_VALIDATE_EMAIL);
        $areaId = apiId($data['area_id']);
        if ($email === false || !$areaId) {
            jsonResponse(['erro' => 'E-mail ou área inválidos.'], 422);
        }
        $stmt = $pdo->prepare('INSERT INTO pessoa (nome, email, cargo, area_id, ativo) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([trim((string) $data['nome']), $email, trim((string) $data['cargo']), $areaId, !empty($data['ativo']) ? 1 : 0]);
        jsonResponse(['mensagem' => 'Pessoa cadastrada com sucesso.', 'id' => (int) $pdo->lastInsertId()], 201);
    }

    jsonResponse(['erro' => 'Método não permitido.'], 405);
} catch (PDOException $e) {
    if ((string) $e->getCode() === '23000') {
        jsonResponse(['erro' => 'Já existe uma pessoa com este e-mail.'], 409);
    }
    internalError($e);
} catch (Throwable $e) {
    internalError($e);
}
