-- Migration: adiciona coluna ativo na tabela cliente
ALTER TABLE cliente
  ADD COLUMN IF NOT EXISTS ativo TINYINT(1) NOT NULL DEFAULT 1;
