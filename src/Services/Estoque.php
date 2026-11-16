<?php
declare(strict_types=1);

/** Erro de regra de negocio/validacao (codigo = status HTTP). */
class AppException extends Exception {}

/** Regras de negocio e consultas do Stockly (todas com prepared statements). */
final class Estoque
{
    /** Normal > minimo | Baixo <= minimo | Critico = zerado ou <= 50% do minimo. */
    public const SITUACAO = "CASE WHEN p.quantidade = 0 OR p.quantidade <= p.estoque_minimo * 0.5 THEN 'critico'
                                  WHEN p.quantidade <= p.estoque_minimo THEN 'baixo' ELSE 'normal' END";

    private static function inteiro(mixed $v, string $campo, int $min = 0): int
    {
        $i = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);
        if ($i === false) {
            throw new AppException($campo . ' deve ser um número inteiro ' . ($min > 0 ? 'maior que zero.' : 'não negativo.'), 422);
        }
        return $i;
    }

    /* ---------------- autenticacao ---------------- */
    public static function autenticar(PDO $pdo, string $email, string $senha): array
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
            throw new AppException('Informe um e-mail e uma senha válidos.', 422);
        }
        $st = $pdo->prepare('SELECT id_usuario, nome, email, senha_hash, perfil FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        if (!$u || !password_verify($senha, $u['senha_hash'])) {
            throw new AppException('E-mail ou senha incorretos.', 401);
        }
        unset($u['senha_hash']);
        return $u;
    }

    /* ---------------- usuarios ---------------- */
    public static function usuarios(PDO $pdo): array
    {
        return $pdo->query('SELECT id_usuario, nome, email, perfil, criado_em FROM usuarios ORDER BY nome')->fetchAll();
    }

    public static function criarUsuario(PDO $pdo, array $d): int
    {
        $nome  = trim((string)($d['nome'] ?? ''));
        $email = mb_strtolower(trim((string)($d['email'] ?? '')));
        $senha = (string)($d['senha'] ?? '');
        $perfil = ($d['perfil'] ?? '') === 'admin' ? 'admin' : 'operador';
        if ($nome === '' || mb_strlen($nome) > 100) throw new AppException('Informe o nome (até 100 caracteres).', 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new AppException('E-mail inválido.', 422);
        if (strlen($senha) < 6) throw new AppException('A senha deve ter ao menos 6 caracteres.', 422);
        try {
            $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?,?,?,?)')
                ->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') throw new AppException('Já existe um usuário com este e-mail.', 409);
            throw $e;
        }
        return (int)$pdo->lastInsertId();
    }

    /* ---------------- categorias ---------------- */
    public static function categorias(PDO $pdo): array
    {
        return $pdo->query('SELECT c.*, COUNT(p.id_produto) AS total_produtos
                            FROM categorias c LEFT JOIN produtos p ON p.id_categoria = c.id_categoria
                            GROUP BY c.id_categoria ORDER BY c.nome')->fetchAll();
    }

    public static function criarCategoria(PDO $pdo, array $d): int
    {
        $nome = trim((string)($d['nome'] ?? ''));
        $desc = trim((string)($d['descricao'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 80) throw new AppException('Informe o nome da categoria (até 80 caracteres).', 422);
        if (mb_strlen($desc) > 255) throw new AppException('Descrição muito longa (máx. 255).', 422);
        $st = $pdo->prepare('SELECT 1 FROM categorias WHERE LOWER(nome) = LOWER(?)');
        $st->execute([$nome]);
        if ($st->fetch()) throw new AppException('Já existe uma categoria com este nome.', 409);
        $pdo->prepare('INSERT INTO categorias (nome, descricao) VALUES (?, ?)')->execute([$nome, $desc ?: null]);
        return (int)$pdo->lastInsertId();
    }

    /* ---------------- produtos ---------------- */
    public static function produtos(PDO $pdo, array $f = []): array
    {
        $w = ['1=1']; $p = [];
        if (!empty($f['q'])) { $w[] = '(p.nome LIKE ? OR p.codigo LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
        if (!empty($f['categoria'])) { $w[] = 'p.id_categoria = ?'; $p[] = (int)$f['categoria']; }
        if (!empty($f['ativos'])) { $w[] = "p.status = 'ativo'"; }
        $st = $pdo->prepare('SELECT p.*, c.nome AS categoria, ' . self::SITUACAO . ' AS situacao
                             FROM produtos p JOIN categorias c ON c.id_categoria = p.id_categoria
                             WHERE ' . implode(' AND ', $w) . ' ORDER BY p.nome');
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function produto(PDO $pdo, int $id): array
    {
        $st = $pdo->prepare('SELECT p.*, c.nome AS categoria, ' . self::SITUACAO . ' AS situacao
                             FROM produtos p JOIN categorias c ON c.id_categoria = p.id_categoria WHERE p.id_produto = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        if (!$r) throw new AppException('Produto não encontrado.', 404);
        return $r;
    }

    /** Cria (id = null) ou edita um produto. */
    public static function salvarProduto(PDO $pdo, array $d, int $idUsuario, ?int $id = null): int
    {
        $codigo = trim((string)($d['codigo'] ?? ''));
        $nome   = trim((string)($d['nome'] ?? ''));
        if ($codigo === '' || mb_strlen($codigo) > 30) throw new AppException('Informe o código (até 30 caracteres).', 422);
        if ($nome === '' || mb_strlen($nome) > 150) throw new AppException('Informe o nome (até 150 caracteres).', 422);
        $preco = str_replace(',', '.', trim((string)($d['preco'] ?? '')));
        if (!is_numeric($preco) || (float)$preco < 0) throw new AppException('O preço deve ser um número maior ou igual a zero.', 422);
        $min = self::inteiro($d['estoque_minimo'] ?? null, 'Estoque mínimo');
        $cat = self::inteiro($d['id_categoria'] ?? null, 'Categoria', 1);

        $st = $pdo->prepare('SELECT 1 FROM categorias WHERE id_categoria = ?');
        $st->execute([$cat]);
        if (!$st->fetch()) throw new AppException('Categoria inválida.', 422);

        $st = $pdo->prepare('SELECT 1 FROM produtos WHERE codigo = ? AND id_produto <> ?');
        $st->execute([$codigo, $id ?? 0]);
        if ($st->fetch()) throw new AppException('Já existe um produto com este código.', 409);

        try {
            if ($id === null) {
                $qtd = self::inteiro($d['quantidade'] ?? 0, 'Quantidade inicial');
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO produtos (id_categoria, codigo, nome, preco, quantidade, estoque_minimo) VALUES (?,?,?,?,?,?)')
                    ->execute([$cat, $codigo, $nome, $preco, $qtd, $min]);
                $novo = (int)$pdo->lastInsertId();
                if ($qtd > 0) {
                    $pdo->prepare("INSERT INTO movimentacoes (id_produto, id_usuario, tipo, quantidade, observacao) VALUES (?,?,'entrada',?, 'Estoque inicial')")
                        ->execute([$novo, $idUsuario, $qtd]);
                }
                $pdo->commit();
                return $novo;
            }
            self::produto($pdo, $id);
            $status = ($d['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';
            $pdo->prepare('UPDATE produtos SET id_categoria=?, codigo=?, nome=?, preco=?, estoque_minimo=?, status=? WHERE id_produto=?')
                ->execute([$cat, $codigo, $nome, $preco, $min, $status, $id]);
            return $id;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getCode() === '23000') throw new AppException('Dados violam uma restrição do banco (código duplicado ou valor inválido).', 409);
            throw $e;
        }
    }

    public static function inativarProduto(PDO $pdo, int $id): void
    {
        self::produto($pdo, $id);
        $pdo->prepare("UPDATE produtos SET status = 'inativo' WHERE id_produto = ?")->execute([$id]);
    }

    /* ---------------- movimentacoes ---------------- */
    /** Entrada/saida com transacao e bloqueio de linha: o saldo nunca fica negativo. */
    public static function movimentar(PDO $pdo, int $idUsuario, array $d): array
    {
        $tipo = (string)($d['tipo'] ?? '');
        if (!in_array($tipo, ['entrada', 'saida'], true)) throw new AppException('Tipo de movimentação inválido.', 422);
        $idProduto = self::inteiro($d['id_produto'] ?? null, 'Produto', 1);
        $qtd = self::inteiro($d['quantidade'] ?? null, 'Quantidade', 1);
        $obs = trim((string)($d['observacao'] ?? ''));
        if (mb_strlen($obs) > 255) throw new AppException('Observação muito longa (máx. 255).', 422);

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT quantidade, status, nome FROM produtos WHERE id_produto = ? FOR UPDATE');
            $st->execute([$idProduto]);
            $p = $st->fetch();
            if (!$p) throw new AppException('Produto não encontrado.', 404);
            if ($p['status'] !== 'ativo') throw new AppException('Produto inativo não pode receber movimentações.', 422);

            $novoSaldo = $tipo === 'entrada' ? (int)$p['quantidade'] + $qtd : (int)$p['quantidade'] - $qtd;
            if ($novoSaldo < 0) {
                throw new AppException("Saldo insuficiente: {$p['nome']} possui {$p['quantidade']} unidade(s) em estoque.", 422);
            }
            $pdo->prepare('UPDATE produtos SET quantidade = ? WHERE id_produto = ?')->execute([$novoSaldo, $idProduto]);
            $pdo->prepare('INSERT INTO movimentacoes (id_produto, id_usuario, tipo, quantidade, observacao) VALUES (?,?,?,?,?)')
                ->execute([$idProduto, $idUsuario, $tipo, $qtd, $obs ?: null]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return ['id_movimentacao' => $id, 'saldo_atual' => $novoSaldo];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function movimentacoes(PDO $pdo, array $f = [], int $limite = 500): array
    {
        $w = ['1=1']; $p = [];
        if (!empty($f['produto'])) { $w[] = 'm.id_produto = ?'; $p[] = (int)$f['produto']; }
        if (!empty($f['tipo']) && in_array($f['tipo'], ['entrada', 'saida'], true)) { $w[] = 'm.tipo = ?'; $p[] = $f['tipo']; }
        if (!empty($f['de'])) { $w[] = 'm.data_hora >= ?'; $p[] = $f['de'] . ' 00:00:00'; }
        if (!empty($f['ate'])) { $w[] = 'm.data_hora < DATE_ADD(?, INTERVAL 1 DAY)'; $p[] = $f['ate']; }
        if (!empty($f['categoria'])) { $w[] = 'p.id_categoria = ?'; $p[] = (int)$f['categoria']; }
        if (!empty($f['q'])) {
            $w[] = '(p.nome LIKE ? OR p.codigo LIKE ? OR m.observacao LIKE ?)';
            $l = '%' . $f['q'] . '%'; array_push($p, $l, $l, $l);
        }
        $ordem = (($f['ordem'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
        $st = $pdo->prepare("SELECT m.id_movimentacao, m.tipo, m.quantidade, m.data_hora, m.observacao,
                                    p.codigo, p.nome AS produto, u.nome AS usuario
                             FROM movimentacoes m
                             JOIN produtos p ON p.id_produto = m.id_produto
                             JOIN usuarios u ON u.id_usuario = m.id_usuario
                             WHERE " . implode(' AND ', $w) . " ORDER BY m.data_hora $ordem, m.id_movimentacao $ordem LIMIT " . (int)$limite);
        $st->execute($p);
        return $st->fetchAll();
    }

    /* ---------------- estoque baixo ---------------- */
    public static function estoqueBaixo(PDO $pdo, array $f = [], ?int $limite = null): array
    {
        $w = ["p.status = 'ativo'", 'p.quantidade <= p.estoque_minimo']; $p = [];
        if (!empty($f['produto'])) { $w[] = 'p.id_produto = ?'; $p[] = (int)$f['produto']; }
        if (!empty($f['categoria'])) { $w[] = 'p.id_categoria = ?'; $p[] = (int)$f['categoria']; }
        $st = $pdo->prepare('SELECT p.id_produto, p.codigo, p.nome, c.nome AS categoria, p.quantidade, p.estoque_minimo,
                                    ' . self::SITUACAO . ' AS situacao
                             FROM produtos p JOIN categorias c ON c.id_categoria = p.id_categoria
                             WHERE ' . implode(' AND ', $w) . ' ORDER BY p.quantidade ASC, p.nome' . ($limite ? ' LIMIT ' . (int)$limite : ''));
        $st->execute($p);
        return $st->fetchAll();
    }

    /* ---------------- dashboard ---------------- */
    public static function dashboard(PDO $pdo): array
    {
        $q = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
        $cards = [
            'produtos'      => $q('SELECT COUNT(*) FROM produtos'),
            'categorias'    => $q('SELECT COUNT(*) FROM categorias'),
            'itens'         => $q("SELECT COALESCE(SUM(quantidade),0) FROM produtos WHERE status='ativo'"),
            'estoque_baixo' => $q("SELECT COUNT(*) FROM produtos WHERE status='ativo' AND quantidade <= estoque_minimo"),
            'movimentacoes' => $q('SELECT COUNT(*) FROM movimentacoes'),
        ];
        $dias = [];
        for ($i = 6; $i >= 0; $i--) { $dias[date('Y-m-d', strtotime("-$i day"))] = ['entrada' => 0, 'saida' => 0]; }
        $st = $pdo->prepare('SELECT DATE(data_hora) AS dia, tipo, SUM(quantidade) AS total FROM movimentacoes
                             WHERE data_hora >= ? GROUP BY dia, tipo');
        $st->execute([array_key_first($dias) . ' 00:00:00']);
        foreach ($st->fetchAll() as $r) { if (isset($dias[$r['dia']])) $dias[$r['dia']][$r['tipo']] = (int)$r['total']; }
        $grafico = ['labels' => array_map(fn($d) => date('d/m', strtotime($d)), array_keys($dias)),
                    'entradas' => array_column($dias, 'entrada'), 'saidas' => array_column($dias, 'saida')];

        $menor = $pdo->query("SELECT p.codigo, p.nome, p.quantidade, p.estoque_minimo, " . self::SITUACAO . " AS situacao
                              FROM produtos p WHERE p.status='ativo' ORDER BY p.quantidade ASC, p.nome LIMIT 5")->fetchAll();
        return ['cards' => $cards, 'grafico' => $grafico, 'menor_estoque' => $menor,
                'ultimas' => self::movimentacoes($pdo, [], 5), 'alertas' => self::estoqueBaixo($pdo, [], 5)];
    }

    /* ---------------- relatorios ---------------- */
    public static function relatorio(PDO $pdo, string $tipo, array $f = []): array
    {
        switch ($tipo) {
            case 'estoque':
                $r = self::produtos($pdo, ['categoria' => $f['categoria'] ?? null]);
                if (!empty($f['produto'])) $r = array_values(array_filter($r, fn($x) => (int)$x['id_produto'] === (int)$f['produto']));
                foreach ($r as &$x) { $x['valor_total'] = brl($x['quantidade'] * $x['preco']); $x['preco'] = brl($x['preco']); $x['situacao'] = ucfirst($x['situacao']); }
                return ['titulo' => 'Relatório geral de estoque',
                        'colunas' => ['Código', 'Produto', 'Categoria', 'Preço', 'Qtd.', 'Mínimo', 'Valor em estoque', 'Situação'],
                        'campos' => ['codigo', 'nome', 'categoria', 'preco', 'quantidade', 'estoque_minimo', 'valor_total', 'situacao'], 'linhas' => $r];
            case 'por_categoria':
                $w = '1=1'; $p = [];
                if (!empty($f['categoria'])) { $w = 'c.id_categoria = ?'; $p[] = (int)$f['categoria']; }
                $st = $pdo->prepare("SELECT c.nome AS categoria, COUNT(p.id_produto) AS produtos,
                                            COALESCE(SUM(p.quantidade),0) AS itens, COALESCE(SUM(p.quantidade*p.preco),0) AS valor
                                     FROM categorias c LEFT JOIN produtos p ON p.id_categoria = c.id_categoria
                                     WHERE $w GROUP BY c.id_categoria ORDER BY c.nome");
                $st->execute($p);
                $r = $st->fetchAll();
                foreach ($r as &$x) { $x['valor'] = brl($x['valor']); }
                return ['titulo' => 'Relatório de produtos por categoria',
                        'colunas' => ['Categoria', 'Nº de produtos', 'Itens em estoque', 'Valor em estoque'],
                        'campos' => ['categoria', 'produtos', 'itens', 'valor'], 'linhas' => $r];
            case 'movimentacoes':
                $r = self::movimentacoes($pdo, $f);
                foreach ($r as &$x) { $x['data_hora'] = data_br($x['data_hora']); $x['tipo'] = ucfirst($x['tipo'] === 'saida' ? 'saída' : 'entrada'); }
                return ['titulo' => 'Relatório de movimentações',
                        'colunas' => ['Data/hora', 'Código', 'Produto', 'Tipo', 'Qtd.', 'Usuário', 'Observação'],
                        'campos' => ['data_hora', 'codigo', 'produto', 'tipo', 'quantidade', 'usuario', 'observacao'], 'linhas' => $r];
            case 'abaixo_minimo':
                $r = self::estoqueBaixo($pdo, $f);
                foreach ($r as &$x) { $x['situacao'] = ucfirst($x['situacao']); }
                return ['titulo' => 'Produtos abaixo do estoque mínimo',
                        'colunas' => ['Código', 'Produto', 'Categoria', 'Qtd. atual', 'Mínimo', 'Situação'],
                        'campos' => ['codigo', 'nome', 'categoria', 'quantidade', 'estoque_minimo', 'situacao'], 'linhas' => $r];
        }
        throw new AppException('Tipo de relatório inválido.', 422);
    }
}
