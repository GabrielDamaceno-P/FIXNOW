-- Adiciona 'Aguardando Orçamento' ao ENUM de status do chamado
ALTER TABLE chamado
  MODIFY COLUMN status ENUM('Pendente','Aguardando Orçamento','Em Andamento','Concluído','Negado')
  NOT NULL DEFAULT 'Pendente';

-- Corrige chamados corrompidos: tecnico_id definido mas status vazio (causado pelo ENUM inválido)
UPDATE chamado
  SET status = 'Aguardando Orçamento'
  WHERE (status = '' OR status IS NULL)
    AND tecnico_id IS NOT NULL;
