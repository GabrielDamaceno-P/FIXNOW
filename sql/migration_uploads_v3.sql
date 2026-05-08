-- Migration v3: múltiplas fotos chamado, anexos chat, portfólio por categoria
-- Execute no phpMyAdmin

-- 1. Tabela de fotos do chamado (múltiplas por solicitação)
CREATE TABLE IF NOT EXISTS chamado_foto (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  chamado_id INT NOT NULL,
  foto_path  VARCHAR(300) NOT NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chamado_foto (chamado_id),
  CONSTRAINT fk_chamado_foto FOREIGN KEY (chamado_id) REFERENCES chamado(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migra foto existente em chamado.foto_path para a nova tabela
INSERT IGNORE INTO chamado_foto (chamado_id, foto_path)
SELECT id, foto_path FROM chamado WHERE foto_path IS NOT NULL AND foto_path != '';

-- 2. Anexos no chat
ALTER TABLE mensagem_chamado
  ADD COLUMN IF NOT EXISTS arquivo_path VARCHAR(300) NULL AFTER mensagem,
  ADD COLUMN IF NOT EXISTS arquivo_nome VARCHAR(200) NULL AFTER arquivo_path;

-- Permite mensagem vazia quando há apenas anexo
ALTER TABLE mensagem_chamado MODIFY COLUMN mensagem TEXT NULL;

-- 3. Portfólio por categoria
ALTER TABLE portfolio_foto
  ADD COLUMN IF NOT EXISTS categoria_id INT NULL AFTER descricao,
  ADD CONSTRAINT fk_portfolio_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE SET NULL;
