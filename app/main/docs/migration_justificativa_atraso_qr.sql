ALTER TABLE registro_entrada
    ADD COLUMN justificativa_atraso TEXT DEFAULT NULL AFTER id_aluno;
