CREATE DATABASE IF NOT EXISTS tcc
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tcc;

-- ─────────────────────────────────────────────
--  TABELAS
-- ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS admin (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(120) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha       VARCHAR(255) NOT NULL,
  telefone    VARCHAR(20)  NOT NULL,
  genero      ENUM('Feminino','Masculino','Outro','Prefiro não informar') NOT NULL DEFAULT 'Prefiro não informar',
  perfil      ENUM('Master','Operacoes','Financeiro') NOT NULL DEFAULT 'Master',
  foto_perfil VARCHAR(255) NOT NULL DEFAULT 'assets/img/perfil/default-cliente.jpg',
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cliente (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(120) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha       VARCHAR(255) NOT NULL,
  cpf         CHAR(11)     NULL UNIQUE,
  telefone    VARCHAR(20)  NOT NULL,
  endereco    VARCHAR(200) NOT NULL DEFAULT '',
  cep         VARCHAR(10)  NOT NULL DEFAULT '',
  foto_perfil VARCHAR(255) NOT NULL DEFAULT 'assets/img/perfil/default-cliente.jpg',
  genero      ENUM('Feminino','Masculino','Outro','Prefiro não informar') NOT NULL DEFAULT 'Prefiro não informar',
  ativo       TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS tecnico (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(120) NOT NULL,
  email           VARCHAR(150) NULL UNIQUE,
  senha           VARCHAR(255) NULL,
  cpf             CHAR(11)     NULL UNIQUE,
  especialidade   VARCHAR(100) NULL DEFAULT NULL,
  telefone        VARCHAR(20)  NOT NULL,
  genero          ENUM('Feminino','Masculino','Outro') NOT NULL DEFAULT 'Masculino',
  foto_perfil     VARCHAR(255) NOT NULL DEFAULT 'assets/img/perfil/default-tecnico.jpg',
  documento_path  VARCHAR(255) NULL,
  avaliacao_media DECIMAL(3,2) DEFAULT 0.00,
  ativo           TINYINT(1)   NOT NULL DEFAULT 1,
  status_cadastro ENUM('Pendente','Aprovado','Recusado') NOT NULL DEFAULT 'Pendente',
  destaque        TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  admin_id        INT NULL,
  CONSTRAINT fk_tecnico_admin FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS categoria (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL UNIQUE,
  descricao VARCHAR(255) NULL,
  ativo     TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  admin_id  INT NULL,
  CONSTRAINT fk_categoria_admin FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS chamado (
  id                        INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id                INT NOT NULL,
  tecnico_id                INT NULL,
  categoria                 VARCHAR(100) NOT NULL,
  descricao                 TEXT NOT NULL,
  foto_path                 VARCHAR(255) NULL,
  endereco_servico          VARCHAR(200) NOT NULL,
  data_agendamento          DATETIME NULL,
  data_agendamento_proposta DATETIME NULL,
  reagendamento_pendente    TINYINT(1)   NOT NULL DEFAULT 0,
  preco_sugerido            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  prest_feminino            TINYINT(1)   NOT NULL DEFAULT 0,
  lat_servico               DECIMAL(10,7) NULL,
  lng_servico               DECIMAL(10,7) NULL,
  status                    ENUM('Pendente','Aguardando Orçamento','Em Andamento','Concluído','Negado') NOT NULL DEFAULT 'Pendente',
  criado_em                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  em_deslocamento           TINYINT(1)   NOT NULL DEFAULT 0,
  deslocamento_inicio       DATETIME NULL,
  tecnico_lat               DECIMAL(10,7) NULL,
  tecnico_lng               DECIMAL(10,7) NULL,
  CONSTRAINT fk_chamado_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE,
  CONSTRAINT fk_chamado_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS chamado_foto (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id INT NOT NULL,
  foto_path  VARCHAR(300) NOT NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chamado_foto (chamado_id),
  CONSTRAINT fk_chamado_foto FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS pagamento (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id INT NOT NULL,
  metodo     ENUM('PIX','Cartão','Dinheiro') NOT NULL DEFAULT 'PIX',
  valor      DECIMAL(10,2) NOT NULL,
  status     ENUM('Pendente','Pago','Estornado') NOT NULL DEFAULT 'Pendente',
  pago_em    DATETIME NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pagamento_chamado FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS avaliacao (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id INT NOT NULL,
  cliente_id INT NOT NULL,
  tecnico_id INT NOT NULL,
  nota       TINYINT NOT NULL,
  comentario VARCHAR(255) NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_avaliacao_chamado (chamado_id),
  CONSTRAINT fk_avaliacao_chamado FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE,
  CONSTRAINT fk_avaliacao_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE,
  CONSTRAINT fk_avaliacao_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS notificacao (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  tipo_destinatario ENUM('cliente','prestador','admin') NOT NULL DEFAULT 'cliente',
  cliente_id        INT NULL,
  tecnico_id        INT NULL,
  chamado_id        INT NULL,
  mensagem          VARCHAR(500) NOT NULL,
  lida              TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_chamado FOREIGN KEY (chamado_id) REFERENCES chamado(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS servico (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  tecnico_id   INT NOT NULL,
  categoria_id INT NULL,
  nome         VARCHAR(150) NOT NULL,
  descricao    TEXT NULL,
  preco        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ativo        TINYINT(1)    NOT NULL DEFAULT 1,
  criado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_servico_tecnico   FOREIGN KEY (tecnico_id)   REFERENCES tecnico(id)   ON DELETE CASCADE,
  CONSTRAINT fk_servico_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS disponibilidade (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  tecnico_id INT NOT NULL,
  data       DATE NOT NULL,
  hora       TIME NOT NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_disp_slot (tecnico_id, data, hora),
  CONSTRAINT fk_disp_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS portfolio_foto (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  tecnico_id   INT NOT NULL,
  foto_path    VARCHAR(255) NOT NULL,
  titulo       VARCHAR(100) NULL,
  descricao    VARCHAR(255) NULL,
  categoria_id INT NULL,
  criado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_portfolio_tecnico   FOREIGN KEY (tecnico_id)   REFERENCES tecnico(id)   ON DELETE CASCADE,
  CONSTRAINT fk_portfolio_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS orcamento (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id    INT NOT NULL,
  tecnico_id    INT NOT NULL,
  valor         DECIMAL(10,2) NOT NULL,
  descricao     TEXT NULL,
  prazo_dias    TINYINT UNSIGNED NULL,
  status        ENUM('Pendente','Aceito','Recusado') NOT NULL DEFAULT 'Pendente',
  motivo_recusa VARCHAR(500) NULL,
  criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orcamento_chamado FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE,
  CONSTRAINT fk_orcamento_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS mensagem_chamado (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id   INT NOT NULL,
  cliente_id   INT NULL,
  tecnico_id   INT NULL,
  mensagem     TEXT NULL,
  arquivo_path VARCHAR(300) NULL,
  arquivo_nome VARCHAR(200) NULL,
  lida         TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chamado_msg (chamado_id),
  INDEX idx_lida (chamado_id, lida),
  CONSTRAINT fk_msg_chamado  FOREIGN KEY (chamado_id) REFERENCES chamado(id)  ON DELETE CASCADE,
  CONSTRAINT fk_msg_cliente  FOREIGN KEY (cliente_id) REFERENCES cliente(id)  ON DELETE SET NULL,
  CONSTRAINT fk_msg_tecnico  FOREIGN KEY (tecnico_id) REFERENCES tecnico(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS suporte (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id  INT NULL,
  tecnico_id  INT NULL,
  assunto     VARCHAR(200) NOT NULL,
  mensagem    TEXT NULL,
  categoria   VARCHAR(50)  NOT NULL DEFAULT 'Outro',
  prioridade  ENUM('Baixa','Normal','Alta','Urgente') NOT NULL DEFAULT 'Normal',
  status      ENUM('Aberto','Em Andamento','Fechado') NOT NULL DEFAULT 'Aberto',
  resposta    TEXT NULL,
  admin_id    INT NULL,
  chamado_id  INT NULL,
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_suporte_cliente  FOREIGN KEY (cliente_id)  REFERENCES cliente(id)  ON DELETE SET NULL,
  CONSTRAINT fk_suporte_tecnico  FOREIGN KEY (tecnico_id)  REFERENCES tecnico(id)  ON DELETE SET NULL,
  CONSTRAINT fk_suporte_admin    FOREIGN KEY (admin_id)    REFERENCES admin(id)    ON DELETE SET NULL,
  CONSTRAINT fk_suporte_chamado  FOREIGN KEY (chamado_id)  REFERENCES chamado(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS suporte_mensagem (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  suporte_id INT NOT NULL,
  autor_tipo ENUM('cliente','prestador','admin') NOT NULL,
  autor_id   INT NOT NULL,
  mensagem   TEXT NOT NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sup_msg (suporte_id),
  CONSTRAINT fk_supmsg_suporte FOREIGN KEY (suporte_id) REFERENCES suporte(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────
--  DADOS INICIAIS (admin + categorias padrão)
-- ─────────────────────────────────────────────

-- Admin padrão (senha: admin123)
INSERT INTO admin (nome, email, senha, telefone, genero, perfil, foto_perfil)
VALUES (
  'Admin Master', 'admin@fixnow.com',
  '$2y$10$rkqWRfm2R3DWqByTIfuzNe2ADoie4LS6e114uh/7ZSgH2YvXOgxKC',
  '(11) 90000-0000', 'Prefiro não informar', 'Master',
  'assets/img/perfil/default-cliente.jpg'
)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome), telefone = VALUES(telefone),
  genero = VALUES(genero), perfil = VALUES(perfil);

-- Categorias padrão
INSERT IGNORE INTO categoria (nome, descricao) VALUES
('Suporte TI',   'Computadores, redes, dispositivos e sistemas'),
('Elétrica',     'Instalações elétricas residenciais e comerciais'),
('Hidráulica',   'Encanamentos, vazamentos e instalações hidráulicas'),
('Pintura',      'Pintura residencial e comercial'),
('Marcenaria',   'Móveis, portas, janelas e estruturas de madeira'),
('Limpeza',      'Limpeza residencial, comercial e pós-obra'),
('Refrigeração', 'Ar-condicionado, geladeiras e equipamentos de frio'),
('Jardinagem',   'Jardins, gramados, podas e paisagismo');
