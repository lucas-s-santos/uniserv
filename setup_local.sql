-- UniServ - Setup local MySQL (XAMPP/phpMyAdmin)
-- Execute este arquivo inteiro no MySQL para criar o banco local.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS relampagoservice
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE relampagoservice;

CREATE TABLE IF NOT EXISTS funcoes (
  id_funcoes INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome_func VARCHAR(20) NULL,
  categoria VARCHAR(40) NULL,
  valor_base DECIMAL(10,2) NULL,
  duracao_estimada INTEGER UNSIGNED NULL,
  descricao TEXT NULL,
  imagem LONGBLOB NULL,
  PRIMARY KEY (id_funcoes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registro (
  id_registro INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(45) NULL,
  apelido VARCHAR(12) NULL,
  cpf VARCHAR(15) NULL,
  estado VARCHAR(20) NULL,
  cidade VARCHAR(15) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  sexo VARCHAR(1) NULL,
  cnpj VARCHAR(20) NULL,
  email VARCHAR(50) NULL,
  telefone VARCHAR(17) NULL,
  foto VARCHAR(255) NULL,
  senha VARCHAR(255) NULL,
  servicos_ok INTEGER UNSIGNED NULL,
  data_ani DATE NULL,
  funcao BOOL NULL,
  descricao TEXT NULL,
  pix_tipo VARCHAR(15) NULL,
  pix_chave VARCHAR(120) NULL,
  aceita_pix TINYINT(1) DEFAULT 1,
  aceita_dinheiro TINYINT(1) DEFAULT 0,
  aceita_cartao_presencial TINYINT(1) DEFAULT 0,
  pagamento_preferido VARCHAR(12) NULL,
  mensagem_pagamento VARCHAR(255) NULL,
  atualizar BOOL NULL,
  PRIMARY KEY (id_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Servico (
  id_servico INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  registro_id_registro INTEGER UNSIGNED NOT NULL,
  id_trabalhador INTEGER UNSIGNED NOT NULL,
  funcoes_id_funcoes INTEGER UNSIGNED NOT NULL,
  endereco TEXT NULL,
  valor_atual INT NULL,
  tempo_servico INTEGER UNSIGNED NULL,
  avaliacao INTEGER UNSIGNED NULL,
  ativo TINYINT NULL,
  status_etapa TINYINT NULL,
  comentario TEXT NULL,
  valor_final DECIMAL(10,2) NULL,
  pagamento_status TINYINT NULL,
  pagamento_comprovante VARCHAR(255) NULL,
  pagamento_data DATETIME NULL,
  foto_antes VARCHAR(255) NULL,
  foto_depois VARCHAR(255) NULL,
  data_2 DATE NULL,
  PRIMARY KEY (id_servico),
  INDEX Servico_FKIndex3 (registro_id_registro),
  INDEX Servico_FKIndex4 (funcoes_id_funcoes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checklist_itens (
  id_item INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  funcoes_id_funcoes INTEGER UNSIGNED NOT NULL,
  descricao VARCHAR(120) NOT NULL,
  ativo TINYINT(1) DEFAULT 1,
  PRIMARY KEY (id_item),
  INDEX checklist_funcoes (funcoes_id_funcoes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servico_checklist (
  id_checklist INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  servico_id INTEGER UNSIGNED NOT NULL,
  item_id INTEGER UNSIGNED NOT NULL,
  concluido TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id_checklist),
  INDEX servico_checklist_servico (servico_id),
  INDEX servico_checklist_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id_audit INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  registro_id_registro INTEGER UNSIGNED NULL,
  acao VARCHAR(60) NOT NULL,
  entidade VARCHAR(60) NOT NULL,
  entidade_id INTEGER UNSIGNED NULL,
  detalhes TEXT NULL,
  data_acao DATETIME NOT NULL,
  PRIMARY KEY (id_audit),
  INDEX audit_log_user (registro_id_registro),
  INDEX audit_log_entity (entidade, entidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabalhador_funcoes (
  id_trafun INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  funcoes_id_funcoes INTEGER UNSIGNED NOT NULL,
  registro_id_registro INTEGER UNSIGNED NOT NULL,
  certificado BLOB NULL,
  valor_hora INT NULL,
  disponivel BOOL NULL,
  PRIMARY KEY (id_trafun),
  INDEX trabalhador_funcoes_FKIndex1 (registro_id_registro),
  INDEX trabalhador_funcoes_FKIndex2 (funcoes_id_funcoes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
  id_notificacao INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  registro_id_registro INTEGER UNSIGNED NOT NULL,
  mensagem TEXT NOT NULL,
  lida TINYINT(1) DEFAULT 0,
  link VARCHAR(255) NULL,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_notificacao),
  INDEX notificacoes_user (registro_id_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador inicial para primeiro acesso.
-- CPF: 16036874677 | Senha: casa1182
INSERT INTO registro (nome, apelido, cpf, senha, funcao, servicos_ok, atualizar)
SELECT 'Admin', 'admin', '16036874677', 'casa1182', 1, 0, 0
WHERE NOT EXISTS (
  SELECT 1 FROM registro WHERE cpf = '16036874677'
);
