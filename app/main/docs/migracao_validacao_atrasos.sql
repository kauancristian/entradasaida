ALTER TABLE registro_entrada
    ADD COLUMN status_justificativa ENUM('pendente', 'aprovada', 'recusada') NOT NULL DEFAULT 'pendente',
    ADD COLUMN observacao_validacao TEXT NULL,
    ADD COLUMN validado_por VARCHAR(100) NULL,
    ADD COLUMN validado_em DATETIME NULL;