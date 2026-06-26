#!/bin/bash
set -e

echo "Aguardando MySQL ficar disponível..."

php -r '
$host = getenv("DB_HOST") ?: "mysql";
$port = getenv("DB_PORT") ?: "3306";
$db   = getenv("DB_DATABASE") ?: "controle_treinamentos";
$user = getenv("DB_USERNAME") ?: "controle_treinamentos";
$pass = getenv("DB_PASSWORD") ?: "controle_treinamentos";

$tentativas = 30;

for ($i = 1; $i <= $tentativas; $i++) {
    try {
        new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
        echo "MySQL disponível.\n";
        exit(0);
    } catch (Throwable $e) {
        echo "Tentativa {$i}/{$tentativas}: aguardando banco...\n";
        sleep(2);
    }
}

echo "MySQL não ficou disponível.\n";
exit(1);
'

if [ "$RUN_MIGRATIONS" = "true" ] && [ -f "database/migrate.php" ]; then
    echo "Executando migrations..."
    php database/migrate.php
fi

echo "Iniciando Apache..."
exec "$@"