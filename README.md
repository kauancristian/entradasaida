# entradasaida

## Configuração do banco de dados

O sistema lê a conexão MySQL destas variáveis de ambiente:

- `DB_HOST` (padrão: `localhost`)
- `DB_PORT` (opcional)
- `DB_NAME` (padrão: `entradasaida`)
- `DB_USER` (padrão: `root`)
- `DB_PASSWORD` (padrão: vazio)

No ambiente de hospedagem, configure `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` com os dados fornecidos pelo provedor; defina `DB_PORT` se ele não usar a porta padrão. Não coloque senhas no código-fonte nem no repositório. O PHP do servidor precisa ter a extensão `pdo_mysql` habilitada e permissão de conexão ao banco.

Defina também `APP_ENV=production`. Em requisições hospedadas, a aplicação exige que as variáveis `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` estejam configuradas; ela não tenta conectar usando as credenciais locais do XAMPP.

## Restrições por turma

Antes de usar a área “Restrições”, faça backup do banco e execute uma vez `app/main/docs/migration_restricoes.sql`. A tela permite criar regras para entrada, saída, estágio ou todos os registros, escolhendo bloquear um intervalo ou permitir registros somente dentro dele, e atribuí-las às turmas. Regras atribuídas são verificadas no servidor durante os registros manuais e via QR Code.

## Atualização necessária para justificativas de atraso via QR

Antes de publicar a versão que grava justificativas de atraso via QR, confira se `registro_entrada.justificativa_atraso` já existe no banco de produção. Se não existir, faça backup do banco e execute uma única vez `app/main/docs/migration_justificativa_atraso_qr.sql`.
