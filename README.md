# Stockly — Sistema Web de Controle de Estoque

Aplicação web para pequenas empresas e comércios controlarem produtos, categorias e movimentações de estoque (entradas e saídas), com alertas de estoque mínimo, histórico e relatórios. Projeto Integrador — **Etapa 2** (banco de dados, telas e back-end).

## Tecnologias
HTML5 · CSS3 · JavaScript · Bootstrap 5 · PHP 8+ · MySQL (PDO) · Chart.js · Git/GitHub

## Funcionalidades
- Login/logout com sessão PHP, `password_hash`/`password_verify` e proteção de páginas internas
- Dashboard com indicadores reais, gráfico de entradas/saídas (7 dias), menor estoque, últimas movimentações e alertas
- Produtos: cadastrar, listar, editar, inativar, pesquisar (nome/código) e filtrar por categoria
- Categorias: cadastrar e listar (sem nomes duplicados)
- Movimentações: entrada e saída com **transação MySQL + `SELECT … FOR UPDATE`**; saldo nunca fica negativo
- Estoque baixo: Normal (acima do mínimo), Baixo (≤ mínimo), Crítico (zerado ou ≤ 50% do mínimo)
- Histórico com filtros (produto, tipo, período, busca, ordenação)
- Relatórios (estoque geral, por categoria, movimentações, abaixo do mínimo) com impressão pelo navegador
- Usuários (somente administrador)
- Segurança: PDO com prepared statements, escape contra XSS, token CSRF, validação no servidor

## Requisitos
PHP 8.0+ (extensão `pdo_mysql`), MySQL 8+ ou MariaDB 10.4+ (XAMPP atende) e navegador com internet (Bootstrap/Chart.js via CDN).

## Instalação (XAMPP)
1. Copie a pasta `stockly` para `C:\xampp\htdocs\` e inicie **Apache** e **MySQL**.
2. Crie o banco: no phpMyAdmin → *Importar* → `database/schema.sql`, depois `database/seeds.sql`
   (em *Conjunto de caracteres do arquivo*, escolha **utf8**, senão os acentos ficam com `?`).
   Ou, via terminal na pasta do projeto:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 -e "source database/schema.sql"
   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 -e "source database/seeds.sql"
   ```
3. Configure a conexão: copie `.env.example` para `.env` e ajuste `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   (para banco remoto, informe o host e a porta do servidor). O `.env` não é versionado.
4. Acesse `http://localhost/stockly/public/`.

Alternativa sem Apache: `php -S localhost:8000 -t public` na raiz do projeto → `http://localhost:8000`.

**Usuário de demonstração:** `admin@stockly.com` / `admin123`

## Banco de dados (`stockly_db`)
| Tabela | Campos principais | Regras |
|---|---|---|
| `usuarios` | id_usuario, nome, email, senha_hash, perfil | e-mail UNIQUE; perfil admin/operador |
| `categorias` | id_categoria, nome, descricao | nome UNIQUE |
| `produtos` | id_produto, id_categoria (FK), codigo, nome, preco, quantidade, estoque_minimo, status | codigo UNIQUE; CHECK preço/quantidade/mínimo ≥ 0; ON DELETE RESTRICT |
| `movimentacoes` | id_movimentacao, id_produto (FK), id_usuario (FK), tipo, quantidade, data_hora, observacao | tipo entrada/saida; CHECK quantidade > 0; índices por produto, data e tipo |

Relacionamentos: categoria 1—N produtos; produto 1—N movimentações; usuário 1—N movimentações.
Scripts: `database/schema.sql` (DDL) e `database/seeds.sql` (DML: admin, 6 categorias, 10 produtos, histórico).

## API (JSON) — `public/api.php?r=<rota>`
Métodos que alteram dados exigem o header `X-CSRF-Token` (obtido em `GET auth/csrf`). Resposta: `{"success": true|false, "message": "...", "data": ...}`.

| Método | Rota | Descrição |
|---|---|---|
| GET | `auth/csrf` | token CSRF / inicia sessão |
| POST | `auth/login` · `auth/logout` | autenticação |
| GET | `dashboard` | indicadores |
| GET/POST | `categorias` | listar / cadastrar |
| GET/POST | `produtos` | listar (`q`, `categoria`) / cadastrar |
| GET/PUT/DELETE | `produtos&id=` | consultar / editar / inativar |
| GET/POST | `movimentacoes` | histórico (`produto`,`tipo`,`de`,`ate`,`q`,`ordem`) / registrar (`tipo`: entrada\|saida) |
| GET | `estoque-baixo` | produtos para reposição |
| GET | `relatorios&tipo=` | `estoque`, `por_categoria`, `movimentacoes`, `abaixo_minimo` |
| GET/POST | `usuarios` | somente admin |

Exemplo: `POST api.php?r=movimentacoes` com `{"tipo":"saida","id_produto":1,"quantidade":3}` → `{"success":true,"message":"Saída registrada com sucesso!","data":{...,"saldo_atual":N}}`.
**Postman:** importe `tests/postman_collection.json`, ajuste `baseUrl` e execute na ordem (o cookie de sessão é mantido pelo Postman).

## Estrutura
```
config/database.php   conexão PDO        database/   schema.sql, seeds.sql
includes/             bootstrap, header, sidebar, footer
public/               telas PHP, api.php, assets/ (css, js)
routes/api.php        roteador da API  src/Services/Estoque.php  regras de negócio
tests/                coleção Postman   docs/wireframes.pdf  wireframes
```

## Decisões e limitações
- Produtos não são excluídos, apenas inativados (o histórico é preservado; as FKs usam `RESTRICT`).
- A quantidade inicial gera uma movimentação de entrada "Estoque inicial".
- Sem limitação de tentativas de login e sem edição/exclusão de categorias e usuários nesta versão inicial.
