<?php
declare(strict_types=1);
/**
 * Roteador da API REST (JSON). Chamado por public/api.php?r=<rota>
 * Rotas:
 *  GET  auth/csrf | POST auth/login | POST auth/logout | GET auth/me
 *  GET  dashboard
 *  GET/POST categorias
 *  GET/POST produtos | GET/PUT/DELETE produtos&id=
 *  GET/POST movimentacoes
 *  GET  estoque-baixo | GET relatorios&tipo=
 *  GET/POST usuarios (admin)
 */
function responder(bool $ok, string $msg = '', mixed $data = null, int $http = 200): never
{
    http_response_code($http);
    $out = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function tratar_api(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $metodo = $_SERVER['REQUEST_METHOD'];
    $rota   = trim((string)($_GET['r'] ?? ''), '/');
    $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $body   = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) $body = $_POST;

    try {
        if ($metodo !== 'GET' && !csrf_ok($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            throw new AppException('Token CSRF inválido ou ausente.', 419);
        }
        $pdo = db();
        $chave = "$metodo $rota";

        /* --- rotas publicas --- */
        if ($chave === 'GET auth/csrf') responder(true, '', ['csrf_token' => csrf_token()]);
        if ($chave === 'POST auth/login') {
            $u = Estoque::autenticar($pdo, (string)($body['email'] ?? ''), (string)($body['senha'] ?? ''));
            session_regenerate_id(true);
            $_SESSION['user'] = $u;
            csrf_token();
            responder(true, 'Login realizado com sucesso!', ['usuario' => $u, 'csrf_token' => csrf_token()]);
        }

        /* --- exige autenticacao --- */
        $user = usuario();
        if (!$user) throw new AppException('Sessão expirada. Faça login novamente.', 401);
        $get = $_GET;

        switch ($chave) {
            case 'POST auth/logout':
                $_SESSION = []; session_destroy();
                responder(true, 'Sessão encerrada.');
            case 'GET auth/me':
                responder(true, '', $user);
            case 'GET dashboard':
                responder(true, '', Estoque::dashboard($pdo));

            case 'GET categorias':
                responder(true, '', Estoque::categorias($pdo));
            case 'POST categorias':
                $novo = Estoque::criarCategoria($pdo, $body);
                responder(true, 'Categoria cadastrada com sucesso!', ['id_categoria' => $novo], 201);

            case 'GET produtos':
                responder(true, '', $id ? Estoque::produto($pdo, $id) : Estoque::produtos($pdo, $get));
            case 'POST produtos':
                $novo = Estoque::salvarProduto($pdo, $body, (int)$user['id_usuario']);
                responder(true, 'Produto cadastrado com sucesso!', ['id_produto' => $novo], 201);
            case 'PUT produtos':
                if (!$id) throw new AppException('Informe o id do produto.', 422);
                Estoque::salvarProduto($pdo, $body, (int)$user['id_usuario'], $id);
                responder(true, 'Produto atualizado com sucesso!');
            case 'DELETE produtos':
                if (!$id) throw new AppException('Informe o id do produto.', 422);
                Estoque::inativarProduto($pdo, $id);
                responder(true, 'Produto inativado. O histórico foi preservado.');

            case 'GET movimentacoes':
                responder(true, '', Estoque::movimentacoes($pdo, $get));
            case 'POST movimentacoes':
                $r = Estoque::movimentar($pdo, (int)$user['id_usuario'], $body);
                $msg = ($body['tipo'] ?? '') === 'entrada' ? 'Entrada registrada com sucesso!' : 'Saída registrada com sucesso!';
                responder(true, $msg, $r, 201);

            case 'GET estoque-baixo':
                responder(true, '', Estoque::estoqueBaixo($pdo, $get));
            case 'GET relatorios':
                responder(true, '', Estoque::relatorio($pdo, (string)($get['tipo'] ?? 'estoque'), $get));

            case 'GET usuarios':
            case 'POST usuarios':
                if (($user['perfil'] ?? '') !== 'admin') throw new AppException('Acesso restrito a administradores.', 403);
                if ($metodo === 'GET') responder(true, '', Estoque::usuarios($pdo));
                $novo = Estoque::criarUsuario($pdo, $body);
                responder(true, 'Usuário cadastrado com sucesso!', ['id_usuario' => $novo], 201);
        }
        throw new AppException('Rota não encontrada.', 404);
    } catch (AppException $e) {
        responder(false, $e->getMessage(), null, $e->getCode() ?: 400);
    } catch (RuntimeException $e) {
        error_log('[Stockly] ' . $e->getMessage());
        responder(false, $e->getMessage(), null, 500);
    } catch (Throwable $e) {
        error_log('[Stockly] ' . $e->getMessage());
        responder(false, 'Erro interno do servidor.', null, 500);
    }
}
