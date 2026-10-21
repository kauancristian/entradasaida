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

## Perfil da recepção

O tipo de usuário `usuario_recepcao`, atribuído à pessoa para o sistema Entrada/saída no gerenciador de permissões, ativa a interface simplificada. Os usuários com outros tipos, como `Admin_entrada_saida`, continuam usando a interface padrão. O tipo é carregado na sessão durante o login; não é necessário configurar e-mails nem usar parâmetros de URL para selecionar a interface.

O perfil da recepção terá acesso a registrar atrasos, registrar saídas antecipadas, registrar saídas para estágio e consultar ausências registradas.

## Banco de dados

Para uma instalação nova, crie o banco configurado em `DB_NAME` e importe `app/main/docs/entradasaida.sql`. O dump já contém toda a estrutura necessária para o sistema, incluindo restrições por turma e validação de atrasos via QR Code. Não é necessário importar outros arquivos SQL.

O dump também contém os dados presentes na exportação. Confira se deseja copiá-los para a nova máquina antes de importá-lo. Não importe esse arquivo sobre um banco em uso: ele é destinado à criação de uma instalação nova.
