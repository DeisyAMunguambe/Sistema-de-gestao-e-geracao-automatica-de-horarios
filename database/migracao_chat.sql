-- Chat Box do SGH: executar na base de dados gestao_horarios existente.
-- Faça uma cópia de segurança antes de executar.
CREATE TABLE IF NOT EXISTS chat_conversa (
  id_conversa INT NOT NULL AUTO_INCREMENT,
  id_formador INT NOT NULL,
  id_administrador INT NOT NULL,
  criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('Aberta','Fechada') NOT NULL DEFAULT 'Aberta',
  PRIMARY KEY (id_conversa),
  UNIQUE KEY uq_chat_formador (id_formador),
  KEY idx_chat_admin (id_administrador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS chat_mensagem (
  id_mensagem INT NOT NULL AUTO_INCREMENT,
  id_conversa INT NOT NULL,
  id_remetente INT NOT NULL,
  id_horario INT NULL,
  mensagem TEXT NOT NULL,
  enviada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lida_em DATETIME NULL,
  PRIMARY KEY (id_mensagem),
  KEY idx_mensagem_conversa_data (id_conversa, enviada_em),
  KEY idx_mensagem_remetente (id_remetente),
  KEY idx_mensagem_horario (id_horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
