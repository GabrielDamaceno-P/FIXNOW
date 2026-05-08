

CREATE DATABASE IF NOT EXISTS projeto_fixnow
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE projeto_fixnow;

CREATE TABLE IF NOT EXISTS cliente (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(120) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  senha        VARCHAR(255) NOT NULL,
  cpf          CHAR(11) NULL UNIQUE,
  telefone     VARCHAR(20) NOT NULL,
  endereco     VARCHAR(200) NOT NULL,
  cep          VARCHAR(10) NOT NULL,
  foto_perfil  VARCHAR(255) NOT NULL,
  genero       ENUM('Feminino','Masculino','Outro','Prefiro não informar') NOT NULL DEFAULT 'Prefiro não informar',
  is_admin     TINYINT(1) NOT NULL DEFAULT 0,
  admin_perfil ENUM('Master','Operacoes','Financeiro') NULL DEFAULT NULL,
  criado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS tecnico (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  nome             VARCHAR(120) NOT NULL,
  email            VARCHAR(150) NULL UNIQUE,
  senha            VARCHAR(255) NULL,
  cpf              CHAR(11) NULL UNIQUE,
  especialidade    VARCHAR(100) NULL DEFAULT NULL,
  telefone         VARCHAR(20) NOT NULL,
  genero           ENUM('Feminino','Masculino','Outro') NOT NULL DEFAULT 'Masculino',
  foto_perfil      VARCHAR(255) NOT NULL,
  avaliacao_media  DECIMAL(3,2) DEFAULT 0.00,
  ativo            TINYINT(1) NOT NULL DEFAULT 1,
  status_cadastro  ENUM('Pendente','Aprovado','Recusado') NOT NULL DEFAULT 'Aprovado',
  destaque         TINYINT(1) NOT NULL DEFAULT 0,
  criado_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categoria (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL UNIQUE,
  descricao VARCHAR(255) NULL,
  ativo     TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS chamado (
  id                        INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id                INT NOT NULL,
  tecnico_id                INT NULL,
  categoria                 ENUM('Suporte TI','Elétrica','Hidráulica','Pintura','Marcenaria') NOT NULL,
  descricao                 TEXT NOT NULL,
  foto_path                 VARCHAR(255) NULL,
  endereco_servico          VARCHAR(200) NOT NULL,
  data_agendamento          DATETIME NULL,
  data_agendamento_proposta DATETIME NULL,
  reagendamento_pendente    TINYINT(1) NOT NULL DEFAULT 0,
  preco_sugerido            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  exige_prestadora_mulher   TINYINT(1) NOT NULL DEFAULT 0,
  status                    ENUM('Pendente','Em Andamento','Concluído','Negado') NOT NULL DEFAULT 'Pendente',
  criado_em                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_chamado_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE,
  CONSTRAINT fk_chamado_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE SET NULL
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
  nota       TINYINT NOT NULL CHECK (nota BETWEEN 1 AND 5),
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
  lida              TINYINT(1) NOT NULL DEFAULT 0,
  criado_em         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_cliente  FOREIGN KEY (cliente_id)  REFERENCES cliente(id)  ON DELETE CASCADE,
  CONSTRAINT fk_notif_tecnico  FOREIGN KEY (tecnico_id)  REFERENCES tecnico(id)  ON DELETE CASCADE,
  CONSTRAINT fk_notif_chamado  FOREIGN KEY (chamado_id)  REFERENCES chamado(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS servico (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  tecnico_id   INT NOT NULL,
  categoria_id INT NULL,
  nome         VARCHAR(150) NOT NULL,
  descricao    TEXT NULL,
  preco        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ativo        TINYINT(1) NOT NULL DEFAULT 1,
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


CREATE TABLE IF NOT EXISTS chamado_foto (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id INT NOT NULL,
  foto_path  VARCHAR(300) NOT NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chamado_foto (chamado_id),
  CONSTRAINT fk_chamado_foto FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS suporte (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  tipo_usuario   ENUM('cliente','prestador','admin') NOT NULL,
  usuario_id     INT NOT NULL,
  assunto        VARCHAR(200) NOT NULL,
  mensagem       TEXT NULL,
  categoria      VARCHAR(50) NOT NULL DEFAULT 'Outro',
  prioridade     ENUM('Baixa','Normal','Alta','Urgente') NOT NULL DEFAULT 'Normal',
  status         ENUM('Aberto','Em Andamento','Fechado') NOT NULL DEFAULT 'Aberto',
  resposta       TEXT NULL,
  respondido_por INT NULL,
  criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS suporte_mensagem (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  suporte_id  INT NOT NULL,
  autor_tipo  ENUM('cliente','prestador','admin') NOT NULL,
  autor_id    INT NOT NULL,
  mensagem    TEXT NOT NULL,
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sup_msg (suporte_id),
  CONSTRAINT fk_supmsg_suporte FOREIGN KEY (suporte_id) REFERENCES suporte(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS orcamento (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id    INT NOT NULL,
  tecnico_id    INT NOT NULL,
  valor         DECIMAL(10,2) NOT NULL,
  descricao     TEXT NULL,
  prazo_dias    TINYINT UNSIGNED NULL,
  status        ENUM('Pendente','Aceito','Recusado') NOT NULL DEFAULT 'Pendente',
  motivo_recusa VARCHAR(500) NULL
    COMMENT 'Motivo informado pelo cliente ao recusar o orçamento',
  criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orcamento_chamado FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE,
  CONSTRAINT fk_orcamento_tecnico FOREIGN KEY (tecnico_id) REFERENCES tecnico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS mensagem_chamado (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id     INT NOT NULL,
  remetente_tipo ENUM('cliente','prestador') NOT NULL,
  remetente_id   INT NOT NULL,
  mensagem       TEXT NULL,
  arquivo_path   VARCHAR(300) NULL,
  arquivo_nome   VARCHAR(200) NULL,
  lida           TINYINT(1) NOT NULL DEFAULT 0,
  criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chamado_msg (chamado_id),
  INDEX idx_lida (chamado_id, remetente_tipo, lida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT IGNORE INTO categoria (nome, descricao) VALUES
('Suporte TI',  'Computadores, redes, dispositivos e sistemas'),
('Elétrica',    'Instalações elétricas residenciais e comerciais'),
('Hidráulica',  'Encanamentos, vazamentos e instalações hidráulicas'),
('Pintura',     'Pintura residencial e comercial'),
('Marcenaria',  'Móveis, portas, janelas e estruturas de madeira');

INSERT INTO tecnico (nome, email, senha, cpf, especialidade, telefone, genero, foto_perfil, avaliacao_media, ativo, status_cadastro) VALUES
('João Silva',   NULL, NULL, NULL, 'Suporte TI', '(11) 98888-1111', 'Masculino', 'assets/img/perfil/default-tecnico.jpg', 4.8, 1, 'Aprovado'),
('Carlos Souza', NULL, NULL, NULL, 'Elétrica',   '(11) 97777-2222', 'Masculino', 'assets/img/perfil/default-tecnico.jpg', 4.7, 1, 'Aprovado'),
('Marina Alves', NULL, NULL, NULL, 'Hidráulica', '(11) 96666-3333', 'Feminino',  'assets/img/perfil/default-tecnico.jpg', 4.9, 1, 'Aprovado'),
('Rafael Lima',  NULL, NULL, NULL, 'Pintura',    '(11) 95555-4444', 'Masculino', 'assets/img/perfil/default-tecnico.jpg', 4.6, 1, 'Aprovado'),
('Pedro Nunes',  NULL, NULL, NULL, 'Marcenaria', '(11) 94444-5555', 'Masculino', 'assets/img/perfil/default-tecnico.jpg', 4.8, 1, 'Aprovado')
ON DUPLICATE KEY UPDATE
  especialidade   = VALUES(especialidade),
  telefone        = VALUES(telefone),
  genero          = VALUES(genero),
  ativo           = VALUES(ativo),
  status_cadastro = VALUES(status_cadastro);

-- Admin Master  (senha: admin123)
INSERT INTO cliente (nome, email, senha, cpf, telefone, endereco, cep, foto_perfil, genero, is_admin, admin_perfil)
VALUES (
  'Admin Master', 'admin@fixnow.com',
  '$2y$10$P8Aq84K5xS7gQ7Lh7V4I9e9yq8jS8W1SIbIz.I8vowAN3zJfYzEe2',
  NULL, '(11) 90000-0000', 'Rua Central, 100', '01000-000',
  'assets/img/perfil/default-cliente.jpg', 'Prefiro não informar', 1, 'Master'
)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome), telefone = VALUES(telefone),
  endereco = VALUES(endereco), cep = VALUES(cep),
  genero = VALUES(genero), is_admin = 1, admin_perfil = 'Master';

-- Admin Financeiro  (senha: admin123)
INSERT INTO cliente (nome, email, senha, cpf, telefone, endereco, cep, foto_perfil, genero, is_admin, admin_perfil)
VALUES (
  'Admin Financeiro', 'financeiro@fixnow.com',
  '$2y$10$P8Aq84K5xS7gQ7Lh7V4I9e9yq8jS8W1SIbIz.I8vowAN3zJfYzEe2',
  NULL, '(11) 91111-1111', 'Rua Central, 100', '01000-000',
  'assets/img/perfil/default-cliente.jpg', 'Prefiro não informar', 1, 'Financeiro'
)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome), telefone = VALUES(telefone),
  endereco = VALUES(endereco), cep = VALUES(cep),
  genero = VALUES(genero), is_admin = 1, admin_perfil = 'Financeiro';
