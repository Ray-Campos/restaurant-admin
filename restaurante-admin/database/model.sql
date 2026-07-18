CREATE DATABASE IF NOT EXISTS restaurante_db;

USE restaurante_db;

CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(50), 
    telefone VARCHAR(20),
    email VARCHAR(100),
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS funcionarios (
    id_funcionario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    salario DECIMAL(10,2),
    data_contratacao DATE
);

CREATE TABLE IF NOT EXISTS mesas (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    capacidade INT NOT NULL,
    status ENUM('livre', 'ocupada', 'reservada') DEFAULT 'livre'
);

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    id_categoria INT,

    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_mesa INT,
    id_funcionario INT,
    data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('aberto', 'fechado', 'cancelado') DEFAULT 'aberto',
    forma_de_pagamento ENUM('DINHEIRO', 'PIX', 'CARTAO') NOT NULL,

    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
    FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa),
    FOREIGN KEY (id_funcionario) REFERENCES funcionarios(id_funcionario)
);

CREATE TABLE IF NOT EXISTS itens_pedido (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
    FOREIGN KEY (id_produto) REFERENCES produtos(id_produto)
);

CREATE TABLE IF NOT EXISTS despesas (
    id_despesa INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_despesa DATE
);

-- ==========================================
-- TRIGGERS DE ESTOQUE
-- ==========================================

DROP TRIGGER IF EXISTS trg_ajusta_estoque_update;
CREATE TRIGGER trg_ajusta_estoque_update
BEFORE UPDATE ON itens_pedido
FOR EACH ROW
BEGIN
    DECLARE v_estoque INT;
    DECLARE v_diferenca INT;

    SET v_diferenca = NEW.quantidade - OLD.quantidade;

    SELECT estoque
    INTO v_estoque
    FROM produtos
    WHERE id_produto = OLD.id_produto;

    IF v_diferenca > 0 AND v_estoque < v_diferenca THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Estoque insuficiente para alterar quantidade.';
    END IF;

    UPDATE produtos
    SET estoque = estoque - v_diferenca
    WHERE id_produto = OLD.id_produto;
END;

DROP TRIGGER IF EXISTS trg_baixa_estoque;
CREATE TRIGGER trg_baixa_estoque
AFTER INSERT ON itens_pedido
FOR EACH ROW
BEGIN
    UPDATE produtos
    SET estoque = estoque - NEW.quantidade
    WHERE id_produto = NEW.id_produto;
END;

DROP TRIGGER IF EXISTS trg_restaura_estoque;
CREATE TRIGGER trg_restaura_estoque
AFTER DELETE ON itens_pedido
FOR EACH ROW
BEGIN
    UPDATE produtos
    SET estoque = estoque + OLD.quantidade
    WHERE id_produto = OLD.id_produto;
END;

DROP TRIGGER IF EXISTS trg_valida_estoque;
CREATE TRIGGER trg_valida_estoque
BEFORE INSERT ON itens_pedido
FOR EACH ROW
BEGIN
    DECLARE v_estoque INT;

    SELECT estoque
    INTO v_estoque
    FROM produtos
    WHERE id_produto = NEW.id_produto;

    IF v_estoque < NEW.quantidade THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Estoque insuficiente.';
    END IF;
END;