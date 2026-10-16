SET @schema_atual = DATABASE();

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_atual
          AND TABLE_NAME = 'registro_entrada'
          AND COLUMN_NAME = 'justificativa_atraso'
    ),
    'SELECT 1',
    'ALTER TABLE registro_entrada ADD COLUMN justificativa_atraso TEXT DEFAULT NULL'
);
PREPARE migration_stmt FROM @ddl;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_atual
          AND TABLE_NAME = 'registro_entrada'
          AND COLUMN_NAME = 'status_justificativa'
    ),
    'SELECT 1',
    'ALTER TABLE registro_entrada ADD COLUMN status_justificativa ENUM(''pendente'',''aprovada'',''recusada'') NOT NULL DEFAULT ''pendente'''
);
PREPARE migration_stmt FROM @ddl;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_atual
          AND TABLE_NAME = 'registro_entrada'
          AND COLUMN_NAME = 'observacao_validacao'
    ),
    'SELECT 1',
    'ALTER TABLE registro_entrada ADD COLUMN observacao_validacao TEXT DEFAULT NULL'
);
PREPARE migration_stmt FROM @ddl;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_atual
          AND TABLE_NAME = 'registro_entrada'
          AND COLUMN_NAME = 'validado_por'
    ),
    'SELECT 1',
    'ALTER TABLE registro_entrada ADD COLUMN validado_por VARCHAR(100) DEFAULT NULL'
);
PREPARE migration_stmt FROM @ddl;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_atual
          AND TABLE_NAME = 'registro_entrada'
          AND COLUMN_NAME = 'validado_em'
    ),
    'SELECT 1',
    'ALTER TABLE registro_entrada ADD COLUMN validado_em DATETIME DEFAULT NULL'
);
PREPARE migration_stmt FROM @ddl;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;