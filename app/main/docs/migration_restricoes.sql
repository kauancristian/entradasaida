CREATE TABLE IF NOT EXISTS restricao (
    id_restricao INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    tipo_registro ENUM('entrada', 'saida', 'estagio', 'todos') NOT NULL DEFAULT 'todos',
    modo ENUM('bloquear', 'permitir') NOT NULL DEFAULT 'bloquear',
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_restricao),
    KEY idx_restricao_ativa_horario (ativo, hora_inicio, hora_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS restricao_turma (
    id_restricao INT NOT NULL,
    id_turma INT NOT NULL,
    PRIMARY KEY (id_restricao, id_turma),
    KEY idx_restricao_turma_turma (id_turma),
    CONSTRAINT fk_restricao_turma_restricao
        FOREIGN KEY (id_restricao) REFERENCES restricao (id_restricao)
        ON DELETE CASCADE,
    CONSTRAINT fk_restricao_turma_turma
        FOREIGN KEY (id_turma) REFERENCES turma (id_turma)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
