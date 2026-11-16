-- STOCKLY - DDL (MySQL 8+ / MariaDB 10.4+)
CREATE DATABASE IF NOT EXISTS stockly_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stockly_db;

DROP TABLE IF EXISTS movimentacoes;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  perfil     ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  CONSTRAINT uq_usuarios_email UNIQUE (email)
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome         VARCHAR(80) NOT NULL,
  descricao    VARCHAR(255) NULL,
  PRIMARY KEY (id_categoria),
  CONSTRAINT uq_categorias_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE produtos (
  id_produto     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_categoria   INT UNSIGNED NOT NULL,
  codigo         VARCHAR(30) NOT NULL,
  nome           VARCHAR(150) NOT NULL,
  preco          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  quantidade     INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  status         ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (id_produto),
  CONSTRAINT uq_produtos_codigo UNIQUE (codigo),
  CONSTRAINT fk_produtos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria) ON DELETE RESTRICT,
  CONSTRAINT ck_produtos_preco CHECK (preco >= 0),
  CONSTRAINT ck_produtos_qtd CHECK (quantidade >= 0),
  CONSTRAINT ck_produtos_min CHECK (estoque_minimo >= 0),
  INDEX idx_produtos_nome (nome),
  INDEX idx_produtos_categoria (id_categoria),
  INDEX idx_produtos_status (status)
) ENGINE=InnoDB;

CREATE TABLE movimentacoes (
  id_movimentacao INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_produto      INT UNSIGNED NOT NULL,
  id_usuario      INT UNSIGNED NOT NULL,
  tipo            ENUM('entrada','saida') NOT NULL,
  quantidade      INT NOT NULL,
  data_hora       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacao      VARCHAR(255) NULL,
  PRIMARY KEY (id_movimentacao),
  CONSTRAINT fk_mov_produto FOREIGN KEY (id_produto) REFERENCES produtos (id_produto) ON DELETE RESTRICT,
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE RESTRICT,
  CONSTRAINT ck_mov_qtd CHECK (quantidade > 0),
  INDEX idx_mov_produto (id_produto),
  INDEX idx_mov_data (data_hora),
  INDEX idx_mov_tipo (tipo)
) ENGINE=InnoDB;
