-- Migração: sistema de suporte com threads + prioridade + categoria
-- Execute este script uma única vez no phpMyAdmin ou MySQL CLI.

ALTER TABLE suporte
  ADD COLUMN IF NOT EXISTS prioridade ENUM('Baixa','Normal','Alta','Urgente') NOT NULL DEFAULT 'Normal',
  ADD COLUMN IF NOT EXISTS categoria  VARCHAR(50) NOT NULL DEFAULT 'Outro';

CREATE TABLE IF NOT EXISTS suporte_mensagem (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  suporte_id  INT NOT NULL,
  autor_tipo  ENUM('cliente','prestador','admin') NOT NULL,
  autor_id    INT NOT NULL,
  mensagem    TEXT NOT NULL,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (suporte_id) REFERENCES suporte(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migra mensagens originais dos tickets existentes
INSERT IGNORE INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem, criado_em)
SELECT id, tipo_usuario, usuario_id, mensagem, criado_em
FROM suporte
WHERE mensagem IS NOT NULL AND mensagem <> '';

-- Migra respostas admin existentes
INSERT IGNORE INTO suporte_mensagem (suporte_id, autor_tipo, autor_id, mensagem, criado_em)
SELECT s.id, 'admin', COALESCE(s.respondido_por, 1), s.resposta, COALESCE(s.atualizado_em, s.criado_em)
FROM suporte s
WHERE s.resposta IS NOT NULL AND s.resposta <> '';
