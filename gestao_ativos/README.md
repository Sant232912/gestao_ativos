# Gestão de Ativos de TI e Service Desk

Sistema web para organizar o inventário de equipamentos, o controle de estoque
e o atendimento de solicitações de suporte técnico em um único ambiente.

## Sobre o projeto

O **Gestão de Ativos** foi desenvolvido para auxiliar equipes de TI no controle
do ciclo de vida dos ativos da empresa. A aplicação permite acompanhar
equipamentos, peças, colaboradores, centros de custo e chamados de suporte,
mantendo as informações centralizadas e fáceis de consultar.

O projeto utiliza arquivos JSON para persistência dos dados, o que facilita a
instalação em ambientes locais com XAMPP e Apache. Também possui APIs PHP para
as principais operações do sistema e pode sincronizar os chamados com um
repositório do GitHub quando essa opção estiver configurada.

## Principais funcionalidades

- Dashboard com visão geral das informações do sistema;
- Cadastro e consulta de ativos de TI;
- Controle de estoque e peças utilizadas em chamados;
- Gerenciamento de colaboradores;
- Abertura, atualização, pesquisa, importação e encerramento de chamados;
- Cadastro, edição, exclusão, filtro, importação e exportação de centros de
  custo;
- Relatórios para apoiar o acompanhamento do inventário;
- Autenticação de usuários com controle de acesso por perfil;
- Armazenamento dos dados em arquivos JSON;
- Sincronização opcional dos chamados com o GitHub;
- Registro das operações de sincronização em arquivo de log.

## Tecnologias utilizadas

- PHP;
- HTML5;
- CSS3;
- JavaScript;
- JSON;
- API do GitHub;
- Apache/XAMPP para execução local.

## Estrutura principal

```text
gestao_ativos/
├── assets/                 # Arquivos de estilo da aplicação
├── dados/                  # Dados em JSON, modelos de importação e logs
├── database/               # Scripts e consultas de apoio ao banco de dados
├── api_chamados.php        # API de gerenciamento de chamados
├── api_centros_de_custo.php # API de gerenciamento de centros de custo
├── dashboard.php           # Painel inicial
├── chamados.php            # Tela de atendimento de chamados
├── centros_de_custo.php    # Tela de centros de custo
├── estoque.php             # Controle de estoque
├── ativos.php              # Controle de ativos
├── colaboradores.php       # Cadastro de colaboradores
├── usuarios.php            # Administração de usuários
└── config.php              # Configurações gerais e integração
```

## Como executar localmente

1. Instale o [XAMPP](https://www.apachefriends.org/).
2. Copie o projeto para a pasta `htdocs` do XAMPP.
3. Inicie o serviço **Apache** pelo painel do XAMPP.
4. Acesse:

   ```text
   http://localhost/gestao_ativos/
   ```

5. Faça login com um usuário cadastrado no arquivo `dados/usuarios.json`.

Na primeira execução, o sistema cria automaticamente a estrutura de dados
necessária. O usuário administrativo inicial é criado somente quando o arquivo
de usuários ainda não existe.

## Sincronização com o GitHub

A sincronização é opcional. Para habilitá-la, configure a variável de ambiente
`GITHUB_TOKEN` no servidor que executa o PHP. O token não deve ser salvo no
código nem publicado no repositório.

Quando habilitada, a integração pode enviar as alterações dos chamados para o
repositório configurado em `config.php`. Os eventos e possíveis erros ficam
registrados em:

```text
dados/logs/sync.log
```

Para mais informações, consulte
[GITHUB_SETUP.md](GITHUB_SETUP.md) e [SINCRONIZACAO.md](SINCRONIZACAO.md).

## Objetivo

O projeto busca oferecer uma solução simples, centralizada e extensível para
equipes de suporte e infraestrutura acompanharem seus ativos, reduzirem a
perda de informações e terem mais visibilidade sobre as demandas de TI.
