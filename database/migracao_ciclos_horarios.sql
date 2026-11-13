-- Migração: permite guardar o histórico de vários horários por turma.
-- Executar na base de dados gestao_horarios. As páginas também criam estas tabelas automaticamente.
CREATE TABLE IF NOT EXISTS horario_ciclo (
  id_ciclo INT AUTO_INCREMENT PRIMARY KEY,
  id_turma INT NOT NULL,
  numero INT NOT NULL,
  data_inicio DATE NOT NULL,
  data_expiracao DATE NOT NULL,
  estado ENUM('Activo','Arquivado') NOT NULL DEFAULT 'Activo',
  criado_por VARCHAR(150) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_turma_ciclo (id_turma, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS horario_historico (
  id_historico INT AUTO_INCREMENT PRIMARY KEY,
  id_ciclo INT NOT NULL,
  id_turma INT NOT NULL,
  id_modulo INT NOT NULL,
  id_formador INT NOT NULL,
  id_sala INT NOT NULL,
  dia_semana VARCHAR(20) NOT NULL,
  turno VARCHAR(20) NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  arquivado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_historico_turma_modulo (id_turma, id_modulo),
  INDEX idx_historico_ciclo (id_ciclo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



