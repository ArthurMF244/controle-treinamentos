<?php

declare(strict_types=1);

require __DIR__ . '/connection.php';

$tentativas = 30;
$pdo = null;
$ultimoErro = null;

while ($tentativas > 0) {
    try {
        $pdo = db();
        $pdo->query('SELECT 1');
        break;
    } catch (Throwable $e) {
        $ultimoErro = $e;
        $tentativas--;
        echo "Aguardando MySQL... ({$tentativas} tentativa(s) restante(s))\n";
        sleep(2);
    }
}

if (!$pdo instanceof PDO) {
    $detalhe = $ultimoErro ? ' ' . $ultimoErro->getMessage() : '';
    throw new RuntimeException('Não foi possível conectar no MySQL para rodar as migrations.' . $detalhe);
}

$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

$migrationsPath = dirname(__DIR__) . '/migrations';
$files = glob($migrationsPath . '/*.sql') ?: [];
sort($files);

// O projeto anterior utilizava o mesmo nome para a migration inicial. Se a
// tabela central do novo sistema ainda não existe, libera somente essa
// migration para que uma instalação legada também receba o novo esquema.
$hasNewSchema = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'usuario'"
)->fetchColumn();

if (!(int) $hasNewSchema) {
    $pdo->prepare('DELETE FROM migrations WHERE migration = ?')->execute(['001_create_tables.sql']);
}

foreach ($files as $file) {
    $name = basename($file);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
    $stmt->execute([$name]);

    if ((int) $stmt->fetchColumn() > 0) {
        echo "Migration já aplicada: {$name}\n";
        continue;
    }

    echo "Aplicando migration: {$name}\n";
    $sql = file_get_contents($file);

    if ($sql === false) {
        throw new RuntimeException("Não foi possível ler a migration {$name}.");
    }

    try {
        $pdo->exec($sql);

        $insert = $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $insert->execute([$name]);
    } catch (Throwable $e) {
        echo "Erro na migration {$name}: {$e->getMessage()}\n";
        throw $e;
    }
}

echo "Migrations finalizadas.\n";
