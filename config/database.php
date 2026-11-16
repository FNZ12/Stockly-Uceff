<?php
declare(strict_types=1);

/** Conexao PDO (singleton). Credenciais vem do arquivo .env (fora de public/). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST', 'localhost'), env('DB_PORT', '3306'), env('DB_NAME', 'stockly_db')
    );
    try {
        $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('[Stockly] Falha de conexao: ' . $e->getMessage());
        throw new RuntimeException('Não foi possível conectar ao banco de dados. Verifique o arquivo .env.');
    }
    return $pdo;
}
