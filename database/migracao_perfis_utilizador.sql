-- Migração dos perfis de utilizador para: Chefe de Departamento e Formador.
-- Executar UMA vez numa base de dados já existente (gestao_horarios).
-- Correspondência: Administrador -> Chefe de Departamento
--                  Coordenador   -> Chefe de Departamento
--                  Operador      -> Formador
-- Não altera mais nenhuma tabela nem dado.

USE `gestao_horarios`;

-- 1) Aceita temporariamente os valores antigos e os novos
ALTER TABLE `utilizador`
  MODIFY `perfil` ENUM('Administrador','Coordenador','Operador','Chefe de Departamento','Formador')
  NOT NULL DEFAULT 'Operador';

-- 2) Converte os utilizadores existentes
UPDATE `utilizador` SET `perfil` = 'Chefe de Departamento' WHERE `perfil` IN ('Administrador','Coordenador');
UPDATE `utilizador` SET `perfil` = 'Formador'              WHERE `perfil` = 'Operador';

-- 3) Fica apenas com os dois perfis novos
ALTER TABLE `utilizador`
  MODIFY `perfil` ENUM('Chefe de Departamento','Formador') NOT NULL DEFAULT 'Formador';
