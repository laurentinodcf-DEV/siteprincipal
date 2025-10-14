-- Adicionar coluna ativo na tabela salao_clientes se não existir
ALTER TABLE `salao_clientes` 
ADD COLUMN IF NOT EXISTS `ativo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=ativo, 0=inativo';

-- Atualizar todos os registros existentes para ativo=1
UPDATE `salao_clientes` SET `ativo` = 1 WHERE `ativo` IS NULL;
