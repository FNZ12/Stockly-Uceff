<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
date_default_timezone_set('America/Sao_Paulo');

/* ---- .env ---- */
$envFile = ROOT . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $linha, 2);
        $_ENV[trim($k)] = trim($v);
    }
}
function env(string $chave, string $padrao = ''): string
{
    return $_ENV[$chave] ?? ((getenv($chave) !== false) ? (string)getenv($chave) : $padrao);
}

require_once ROOT . '/config/database.php';
require_once ROOT . '/src/Services/Estoque.php';

/* ---- sessao ---- */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

/* ---- erros nao tratados: nunca expor detalhes ao usuario ---- */
set_exception_handler(function (Throwable $e) {
    error_log('[Stockly] ' . $e->getMessage());
    $msg = $e instanceof RuntimeException ? $e->getMessage() : 'Erro interno do servidor.';
    http_response_code(500);
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api.php')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:2rem"><h3>Ops!</h3><p>' . htmlspecialchars($msg) . '</p>';
    }
});

/* ---- helpers ---- */
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function brl(float|string $v): string { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function data_br(?string $d): string { return $d ? date('d/m/Y H:i', strtotime($d)) : ''; }
function usuario(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void
{
    if (!usuario()) { header('Location: login.php'); exit; }
}
function require_admin(): void
{
    require_login();
    if ((usuario()['perfil'] ?? '') !== 'admin') { http_response_code(403); exit('Acesso restrito a administradores.'); }
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_ok(?string $t): bool { return $t !== null && $t !== '' && hash_equals($_SESSION['csrf'] ?? '', $t); }
function badge_situacao(string $s): string
{
    return match ($s) {
        'critico' => '<span class="badge text-bg-danger">Crítico</span>',
        'baixo'   => '<span class="badge text-bg-warning">Baixo</span>',
        default   => '<span class="badge text-bg-success">Normal</span>',
    };
}
