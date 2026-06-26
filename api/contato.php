<?php

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
requireApiLogin();

if (method() === 'GET') {
    requireApiAdmin();
}

try {
    $pdo = db();
    if (method() === 'GET') {
        $stmt = $pdo->query('SELECT id, nome, email, mensagem, created_at FROM contato ORDER BY created_at DESC');
        jsonResponse(['data' => $stmt->fetchAll()]);
    }
    if (method() === 'POST') {
        $data = requestData();
        requireFields($data, ['nome', 'email', 'mensagem']);
        $email = filter_var(trim((string) $data['email']), FILTER_VALIDATE_EMAIL);
        if ($email === false) { jsonResponse(['erro' => 'E-mail inválido.'], 422); }
        $stmt = $pdo->prepare('INSERT INTO contato (nome, email, mensagem) VALUES (?, ?, ?)');
        $stmt->execute([trim((string) $data['nome']), $email, trim((string) $data['mensagem'])]);
        jsonResponse(['mensagem' => 'Mensagem enviada com sucesso.', 'id' => (int) $pdo->lastInsertId()], 201);
    }
    jsonResponse(['erro' => 'Método não permitido.'], 405);
} catch (Throwable $e) {
    internalError($e);
}
