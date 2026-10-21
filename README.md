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

Para mostrar o menu simplificado às recepcionistas, configure `RECEPCAO_EMAILS` no ambiente do servidor com os dois e-mails usados no login, separados por vírgula. A comparação ignora diferenças entre maiúsculas e minúsculas. Exemplo:

```text
RECEPCAO_EMAILS=adriana@escola.edu.br,rosana@escola.edu.br
```

Substitua os endereços de exemplo pelos e-mails reais. O perfil da recepção terá acesso a registrar atrasos, registrar saídas antecipadas, registrar saídas para estágio e consultar ausências registradas. Os demais usuários continuam com o menu padrão.

Após entrar pelo login da hospedagem, `inicio.php` identifica o perfil pelo e-mail armazenado na sessão. Não há mais parâmetro de URL nem usuário fictício para ativar o perfil de recepção. No XAMPP, sem uma sessão de login com um dos e-mails configurados, a interface exibida é a padrão.

- `http://localhost/app/subsystems/entradasaida/app/main/views/inicio.php?section=inicio`

Para visualizar o perfil da recepção localmente, configure `RECEPCAO_EMAILS` com os e-mails reais e entre usando o fluxo de login que cria a sessão do usuário. Use um banco local para testar gravações; as operações podem criar registros nesse banco.

## Banco de dados

Para uma instalação nova, crie o banco configurado em `DB_NAME` e importe `app/main/docs/entradasaida.sql`. O dump já contém toda a estrutura necessária para o sistema, incluindo restrições por turma e validação de atrasos via QR Code. Não é necessário importar outros arquivos SQL.

O dump também contém os dados presentes na exportação. Confira se deseja copiá-los para a nova máquina antes de importá-lo. Não importe esse arquivo sobre um banco em uso: ele é destinado à criação de uma instalação nova.
