-- Execute uma vez, depois de fazer backup do banco e antes de publicar o novo PHP.
-- Os nomes das constraints correspondem ao esquema de entradasaida.sql.

ALTER TABLE `registro_entrada`
  ADD COLUMN `id_usuario_sessao` varchar(191) DEFAULT NULL,
  ADD COLUMN `usuario_nome` varchar(300) DEFAULT NULL,
  ADD COLUMN `usuario_email` varchar(254) DEFAULT NULL;

ALTER TABLE `registro_saida`
  ADD COLUMN `id_usuario_sessao` varchar(191) DEFAULT NULL,
  ADD COLUMN `usuario_nome` varchar(300) DEFAULT NULL,
  ADD COLUMN `usuario_email` varchar(254) DEFAULT NULL;

ALTER TABLE `saida_estagio`
  ADD COLUMN `id_usuario_sessao` varchar(191) DEFAULT NULL,
  ADD COLUMN `usuario_nome` varchar(300) DEFAULT NULL,
  ADD COLUMN `usuario_email` varchar(254) DEFAULT NULL;

UPDATE `registro_entrada` r
LEFT JOIN `usuario` u ON u.`id_usuario` = r.`id_usuario`
LEFT JOIN `funcionario` f ON f.`id_funcionario` = u.`id_funcionario`
SET r.`id_usuario_sessao` = CAST(r.`id_usuario` AS CHAR),
    r.`usuario_nome` = COALESCE(NULLIF(f.`nome`, ''), CONCAT('Usuário legado #', r.`id_usuario`)),
    r.`usuario_email` = u.`email`;

UPDATE `registro_saida` r
LEFT JOIN `usuario` u ON u.`id_usuario` = r.`id_usuario`
LEFT JOIN `funcionario` f ON f.`id_funcionario` = u.`id_funcionario`
SET r.`id_usuario_sessao` = CAST(r.`id_usuario` AS CHAR),
    r.`usuario_nome` = COALESCE(NULLIF(f.`nome`, ''), CONCAT('Usuário legado #', r.`id_usuario`)),
    r.`usuario_email` = u.`email`;

ALTER TABLE `cadastrar`
  DROP FOREIGN KEY `cadastrar_ibfk_2`;

ALTER TABLE `registro_entrada`
  DROP FOREIGN KEY `registro_entrada_ibfk_4`,
  MODIFY COLUMN `id_usuario` int(11) DEFAULT NULL;

ALTER TABLE `registro_saida`
  DROP FOREIGN KEY `registro_saida_ibfk_4`,
  MODIFY COLUMN `id_usuario` int(11) DEFAULT NULL;
