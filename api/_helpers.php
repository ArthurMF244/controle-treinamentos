<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/database/connection.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('controle_treinamentos');
    session_start();
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireApiLogin(): void
{
    if (!isset($_SESSION['usuario']['id'])) {
        jsonResponse(['erro' => 'Autenticação necessária.'], 401);
    }
}

function requestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        try {
            $data = json_decode(file_get_contents('php://input') ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            jsonResponse(['erro' => 'JSON inválido.'], 400);
        }

        return is_array($data) ? $data : [];
    }

    return $_POST ?: [];
}

function method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function apiId(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : (int) $id;
}

function getId(): ?int
{
    return apiId($_GET['id'] ?? null);
}

function boolParam(string $name): bool
{
    return isset($_GET[$name]) && in_array(strtolower((string) $_GET[$name]), ['1', 'true', 'sim', 'yes'], true);
}

function requireFields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            jsonResponse(['erro' => "Campo obrigatório: {$field}."], 422);
        }
    }
}

function apiDateTime(mixed $value): ?string
{
    $value = trim((string) $value);
    $date = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value)
        ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }

    return $date->format('Y-m-d H:i:s');
}

function apiProgress(mixed $value): ?int
{
    $progress = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);

    return $progress === false ? null : (int) $progress;
}

function internalError(Throwable $e): never
{
    error_log($e->__toString());
    jsonResponse(['erro' => 'Erro interno do servidor.'], 500);
}
