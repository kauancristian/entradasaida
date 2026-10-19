ALTER TABLE registro_entrada
    MODIFY COLUMN id_tipo_conducente INT(11) DEFAULT NULL,
    MODIFY COLUMN id_tipo_responsavel INT(11) DEFAULT NULL;