create database relampagoservice;
use relampagoservice;

CREATE TABLE funcoes (
  id_funcoes INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome_func VARCHAR(20) NULL,
  categoria VARCHAR(40) NULL,
  valor_base DECIMAL(10,2) NULL,
  duracao_estimada INTEGER UNSIGNED NULL,
  descricao TEXT NULL,
  imagem longblob NULL,
  PRIMARY KEY(id_funcoes)
);

CREATE TABLE registro (
  id_registro INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(45) NULL,
  apelido VARCHAR(12) NULL,
  cpf VARCHAR(15) NULL,
  estado VARCHAR(20),
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
  PRIMARY KEY(id_registro)
);

CREATE TABLE Servico (
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
  PRIMARY KEY(id_servico),
  INDEX Servico_FKIndex3(registro_id_registro),
  INDEX Servico_FKIndex4(funcoes_id_funcoes)
);

CREATE TABLE checklist_itens (
  id_item INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  funcoes_id_funcoes INTEGER UNSIGNED NOT NULL,
  descricao VARCHAR(120) NOT NULL,
  ativo TINYINT(1) DEFAULT 1,
  PRIMARY KEY(id_item),
  INDEX checklist_funcoes(funcoes_id_funcoes)
);

CREATE TABLE servico_checklist (
  id_checklist INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  servico_id INTEGER UNSIGNED NOT NULL,
  item_id INTEGER UNSIGNED NOT NULL,
  concluido TINYINT(1) DEFAULT 0,
  PRIMARY KEY(id_checklist),
  INDEX servico_checklist_servico(servico_id),
  INDEX servico_checklist_item(item_id)
);

CREATE TABLE audit_log (
  id_audit INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  registro_id_registro INTEGER UNSIGNED NULL,
  acao VARCHAR(60) NOT NULL,
  entidade VARCHAR(60) NOT NULL,
  entidade_id INTEGER UNSIGNED NULL,
  detalhes TEXT NULL,
  data_acao DATETIME NOT NULL,
  PRIMARY KEY(id_audit),
  INDEX audit_log_user(registro_id_registro),
  INDEX audit_log_entity(entidade, entidade_id)
);

CREATE TABLE trabalhador_funcoes (
  id_trafun INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  funcoes_id_funcoes INTEGER UNSIGNED NOT NULL,
  registro_id_registro INTEGER UNSIGNED NOT NULL,
  certificado BLOB NULL,
  valor_hora INT NULL,
  disponivel BOOL NULL,
  PRIMARY KEY(id_trafun),
  INDEX trabalhador_funcoes_FKIndex1(registro_id_registro),
  INDEX trabalhador_funcoes_FKIndex2(funcoes_id_funcoes)
);

CREATE TABLE notificacoes (
  id_notificacao INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  registro_id_registro INTEGER UNSIGNED NOT NULL,
  mensagem TEXT NOT NULL,
  lida TINYINT(1) DEFAULT 0,
  link VARCHAR(255) NULL,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id_notificacao),
  INDEX notificacoes_user(registro_id_registro)
);

SELECT * FROM trabalhador_funcoes;
SELECT * FROM registro;
SELECT * FROM funcoes;
SELECT * FROM servico;
SELECT * FROM servico WHERE ativo=0;
SELECT disponivel FROM trabalhador_funcoes WHERE id_trafun = '1' LIMIT 1;

SELECT COUNT(*) as 'conta' FROM servico WHERE id_trabalhador = '2' AND ativo='0';
SELECT avaliacao as 'soma', comentario FROM servico WHERE id_trabalhador = '2' AND ativo='0';

SELECT B.nome_func as 'funcao', A.certificado, A.valor_hora, A.disponivel FROM trabalhador_funcoes A INNER JOIN funcoes B ON A.funcoes_id_funcoes = B.id_funcoes;

SELECT B.nome_func as 'funcao', A.valor_atual FROM servico A INNER JOIN funcoes B ON A.funcoes_id_funcoes = B.id_funcoes WHERE id_servico = '2';

SELECT * FROM servico WHERE registro_id_registro = '3' ORDER BY id_servico DESC;

SELECT A.nome 'nome_colaborador', B.nome_func 'funcao', C.valor_atual 'valor_hora', C.tempo_servico 'tempo_min', C.avaliacao 'avaliacao', C.ativo 'ativo', 
C.comentario 'comentario', C.data_2 'data_2', C.id_servico FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador
 INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE registro_id_registro = '3' AND ativo='0' ORDER BY id_servico DESC;
 
 SELECT A.endereco 'endereco', B.nome_func 'funcao', A.id_servico 'id_servico', C.nome 'nome' FROM servico A INNER JOIN registro C ON C.id_registro = A.registro_id_registro 
 INNER JOIN funcoes B ON B.id_funcoes = A.funcoes_id_funcoes;

SELECT nome AS “Nome_que_eu_escrevi”, senha as “Bora_hackear” FROM registro;

UPDATE registro set funcao='1' where id_registro='2';

INSERT INTO servico(registro_id_registro, id_trabalhador, funcoes_id_funcoes, endereco, valor_atual, tempo_servico, avaliacao, ativo, comentario, data_2) 
        VALUES ('1', '2', '1', 'kkk nao', '35', '0', '5', '1', 'analise', '2022-12-12');
        
SELECT avaliacao as 'soma', comentario as 'coment', avaliacao FROM servico WHERE id_trabalhador = '2' AND ativo='0' AND avaliacao>0;