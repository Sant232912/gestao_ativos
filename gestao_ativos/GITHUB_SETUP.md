# 🔄 Configuração - Sincronização com GitHub

O sistema Gestão de Ativos agora possui integração automática com GitHub para fazer backup de todos os chamados na nuvem.

## Como funciona?

1. **Automática**: A cada operação (adicionar, atualizar, deletar chamado), o arquivo é sincronizado com GitHub
2. **Segura**: Usa autenticação via token pessoal do GitHub
3. **Rastreável**: Cada sincronização gera um log com timestamp e mensagem

## Pré-requisitos

- Conta no GitHub (gratuita)
- Um repositório GitHub criado (público ou privado)
- Acesso às configurações de desenvolvedor do GitHub

## Passo a passo para configurar

### 1️⃣ Gerar Token do GitHub

1. Acesse: https://github.com/settings/tokens/new
2. Na seção "Note", coloque: `Gestao Ativos Sync`
3. Em "Expiration", selecione: `No expiration`
4. Em "Scopes", marque:
   - ✅ `repo` (acesso completo a repositórios)
   - ✅ `workflow` (para atualizar arquivos de workflow)
5. Clique em "Generate token"
6. **COPIE O TOKEN** (você só verá uma vez!)

### 2️⃣ Preparar o Repositório GitHub

1. Acesse https://github.com/new
2. Nome do repositório: `gestao-ativos` (ou outro de sua escolha)
3. Descrição: `Sistema de Gestão de Ativos e Chamados`
4. Tipo: Privado (recomendado) ou Público
5. Clique em "Create repository"
6. **NÃO** inicialize com README (já teremos um)

### 3️⃣ Configurar o token com variável de ambiente

O token não deve ser gravado em `config.php` nem enviado para o GitHub.
Configure a variável de ambiente `GITHUB_TOKEN` no servidor PHP:

```text
GITHUB_TOKEN=seu_token_pessoal
```

No Windows/XAMPP, configure a variável no ambiente do Apache/PHP e reinicie o Apache.
O usuário e o repositório já estão definidos como `Sant232912/gestao-ativos`.

Se o token não estiver configurado, a aplicação continua funcionando localmente,
mas a sincronização automática fica desabilitada.

### 4️⃣ Habilitar a sincronização

Depois de definir `GITHUB_TOKEN`, a sincronização é habilitada automaticamente.
O token precisa ter permissão de leitura e escrita no repositório privado.

#### Configuração antiga (não usar)

```php
define('GITHUB_TOKEN', 'seu_token_aqui');
```

### 5️⃣ Testar a Sincronização

1. Abra http://localhost/gestao_ativos/chamados.php
2. Adicione um novo chamado
3. Abra o navegador (F12) → Console
4. Verifique se não há erros
5. Acesse seu repositório GitHub (https://github.com/seu_usuario/gestao-ativos)
6. Procure pela pasta `dados/` → você deve ver o arquivo `chamados.json`

## Monitoramento

### Arquivo de Log

Todos os eventos de sincronização são registrados em:
```
dados/logs/sync.log
```

Verifique erros lá em caso de problemas.

### Mensagens de Sincronização

Cada operação gera uma mensagem personalizada no GitHub:
- ✅ "Novo chamado adicionado: CHD-1234567890"
- ✅ "Chamado atualizado: CHD-1234567890"
- ✅ "Chamado deletado: 1a2b3c4d5e6f7"
- ✅ "Importação em massa: 10 chamados adicionados"

## Solução de Problemas

### ❌ "Falha ao autenticar com GitHub"
- Verifique se o token foi copiado corretamente
- Certifique-se de que NÃO copiou espaços extras
- Gere um novo token se necessário

### ❌ "Repositório não encontrado"
- Verifique se o nome do repositório está correto em `config.php`
- Verifique se o repositório foi criado e é acessível

### ❌ Sincronização não ocorre
- Verifique em `dados/logs/sync.log` qual é o erro específico
- Certifique-se de que `SYNC_GITHUB_ENABLED` está `true` em `config.php`
- Verifique se a extensão `curl` está habilitada no PHP

### ❌ "Permissão negada"
- Gere um novo token com scopes `repo` e `workflow`
- Verifique se o repositório é privado (pode haver restrições)

## Estrutura no GitHub

Após sincronizar, seu repositório terá a seguinte estrutura:

```
gestao-ativos/
├── dados/
│   └── chamados.json          (seus dados sincronizados)
├── README.md                  (descrição do projeto)
└── .gitignore                 (arquivos a ignorar)
```

## Intervalo de Sincronização

Por padrão, o sistema sincroniza **a cada 5 minutos no máximo** para evitar excesso de requisições. Você pode alterar isso em `config.php`:

```php
define('SYNC_INTERVAL', 300);  // 300 segundos = 5 minutos
```

Para sincronizar a cada 1 minuto, mude para:
```php
define('SYNC_INTERVAL', 60);
```

## Recuperação de Dados

Caso seus dados sejam perdidos localmente, você pode recuperá-los do GitHub:

1. Acesse seu repositório no GitHub
2. Clique no arquivo `dados/chamados.json`
3. Clique em "Raw"
4. Copie todo o conteúdo
5. Salve em `dados/chamados.json` localmente

## Segurança

⚠️ **Importante:**
- **NUNCA** compartilhe seu token com outras pessoas
- **NUNCA** copie `config.php` com o token para repositórios públicos
- Considere usar um `.gitignore` local para não versionálo acidentalmente

## Desabilitar Sincronização

Se precisar desabilitar temporariamente, altere em `config.php`:

```php
define('SYNC_GITHUB_ENABLED', false);
```

Isso não afeta os dados locais, apenas para a sincronização para a nuvem.

---

**Pronto! 🎉** Seu sistema agora está protegido com backup automático na nuvem!

Dúvidas? Verifique `dados/logs/sync.log` para mais detalhes sobre cada operação.
