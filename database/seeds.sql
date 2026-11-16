-- STOCKLY - DML (dados iniciais). Login de demonstracao: admin@stockly.com / admin123
USE stockly_db;

INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
('Administrador', 'admin@stockly.com', '$2b$12$crf26AJXaUGom50T/Q1YAOWwGcl4EOsjhahTrjAllCBMhkXJozJR2', 'admin');

INSERT INTO categorias (nome, descricao) VALUES
('Eletrônicos', 'Aparelhos e dispositivos eletrônicos'),
('Informática', 'Periféricos e acessórios de computador'),
('Escritório', 'Material de escritório e papelaria'),
('Alimentos', 'Itens alimentícios'),
('Vestuário', 'Roupas e uniformes'),
('Acessórios', 'Acessórios diversos');

-- Quantidades iniciais (antes das movimentacoes abaixo)
INSERT INTO produtos (id_categoria, codigo, nome, preco, quantidade, estoque_minimo) VALUES
(2, 'INF-001', 'Mouse sem fio', 59.90, 0, 5),
(2, 'INF-002', 'Teclado ABNT2', 89.90, 0, 5),
(2, 'INF-003', 'Pen drive 64GB', 39.90, 0, 10),
(1, 'ELE-001', 'Fone de ouvido Bluetooth', 129.90, 18, 8),
(1, 'ELE-002', 'Carregador USB-C 20W', 69.90, 25, 10),
(3, 'ESC-001', 'Caderno universitário', 18.50, 60, 20),
(3, 'ESC-002', 'Caneta esferográfica (cx 50)', 42.00, 10, 5),
(4, 'ALI-001', 'Café torrado 500g', 21.90, 30, 12),
(5, 'VES-001', 'Camiseta uniforme M', 34.90, 14, 10),
(6, 'ACE-001', 'Mochila executiva', 149.90, 4, 4);

-- Historico inicial: cada movimento tem o UPDATE correspondente no saldo
INSERT INTO movimentacoes (id_produto, id_usuario, tipo, quantidade, data_hora, observacao) VALUES
(1, 1, 'entrada', 10, NOW() - INTERVAL 5 DAY, 'Compra inicial'),
(1, 1, 'saida',    7, NOW() - INTERVAL 3 DAY, 'Venda balcão'),
(3, 1, 'entrada', 50, NOW() - INTERVAL 4 DAY, 'Compra fornecedor'),
(3, 1, 'saida',   10, NOW() - INTERVAL 2 DAY, 'Venda atacado'),
(7, 1, 'saida',    4, NOW() - INTERVAL 1 DAY, 'Uso interno'),
(10,1, 'entrada',  5, NOW() - INTERVAL 1 DAY, 'Reposição');
UPDATE produtos SET quantidade = quantidade + 10 - 7 WHERE id_produto = 1;
UPDATE produtos SET quantidade = quantidade + 50 - 10 WHERE id_produto = 3;
UPDATE produtos SET quantidade = quantidade - 4 WHERE id_produto = 7;
UPDATE produtos SET quantidade = quantidade + 5 WHERE id_produto = 10;
