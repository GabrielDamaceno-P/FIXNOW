-- Migration: remove taxa_visita e corrige preco_sugerido default
-- Execute no phpMyAdmin ou via MySQL CLI

-- 1. Remove coluna taxa_visita da tabela servico
ALTER TABLE servico DROP COLUMN IF EXISTS taxa_visita;

-- 2. Corrige default de preco_sugerido para 0.00
ALTER TABLE chamado ALTER COLUMN preco_sugerido SET DEFAULT 0.00;

-- 3. Zera preco_sugerido nos chamados Pendente que ainda têm o valor padrão 89
--    (só afeta chamados sem orçamento aceito)
UPDATE chamado
SET preco_sugerido = 0.00
WHERE status = 'Pendente'
  AND preco_sugerido = 89.00
  AND id NOT IN (SELECT chamado_id FROM orcamento WHERE status = 'Aceito');
