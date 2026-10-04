# Guia de estudo do sistema

Este documento explica, de forma didática, o papel de cada arquivo do projeto
**Gestão de Ativos de TI e Service Desk**. A ideia é ajudar quem está
aprendendo programação a entender como uma aplicação web é organizada e como
as partes se comunicam.

## 1. Visão geral: como o sistema funciona

O sistema é uma aplicação web em PHP. O navegador solicita uma página ao
Apache, o PHP executa o código do arquivo e devolve HTML para o navegador.
Em algumas telas, JavaScript faz novas requisições para APIs PHP usando
`fetch()`, sem precisar recarregar a página inteira.

O fluxo básico é:

```text
Navegador
   |
   | solicita uma página PHP
   v
Apache + PHP
   |
   | lê JSON, valida sessão e gera HTML
   v
Tela no navegador
   |
   | JavaScript chama uma API quando necessário
   v
api_*.php -> arquivo JSON -> resposta JSON
```

Existem também scripts SQL na pasta `database/`. Eles representam uma versão
anterior ou alternativa do modelo de dados usando banco relacional. As telas
principais atualmente trabalham com arquivos JSON na pasta `dados/`.

## 2. Arquivos da raiz do projeto

### `index.php`

É a porta de entrada do sistema. Ele inclui `auth.php`, verifica se existe uma
sessão autenticada e redireciona:

- usuário autenticado: para `dashboard.php`;
- usuário não autenticado: para `login.php`.

Conceitos para estudar:

- `require_once`: importa outro arquivo PHP uma única vez;
- `header('Location: ...')`: envia um redirecionamento HTTP;
- `exit`: interrompe a execução para evitar que a página continue sendo
  processada.

### `login.php`

Exibe o formulário de login e processa o envio do e-mail e da senha.

Quando o navegador envia um formulário com método `POST`, o PHP lê os valores
em `$_POST`, chama `fazerLogin()` e, em caso de sucesso, redireciona para o
dashboard. Quando há erro, a mensagem é exibida na própria página.

Conceitos para estudar:

- formulários HTML com `method="POST"`;
- arrays superglobais, como `$_POST` e `$_SERVER`;
- condicionais `if`;
- escape de saída com `htmlspecialchars()`, importante para evitar que texto
  do usuário seja interpretado como HTML.

### `logout.php`

É o ponto de saída da aplicação. Importa `auth.php`, chama `fazerLogout()` e
redireciona para a tela de login.

### `auth.php`

Centraliza a autenticação e o controle de usuários. É um dos arquivos mais
importantes do sistema.

Responsabilidades principais:

- iniciar a sessão com `session_start()`;
- definir o arquivo `dados/usuarios.json`;
- criar um usuário administrador inicial se o arquivo ainda não existir;
- validar login com `password_verify()`;
- criar senhas protegidas com `password_hash()`;
- guardar dados do usuário em `$_SESSION`;
- verificar se o usuário está autenticado;
- verificar se o usuário possui perfil de administrador;
- criar, listar, atualizar e excluir usuários.

Funções principais:

- `fazerLogin($email, $senha)`: procura um usuário ativo e compara a senha;
- `fazerLogout()`: limpa a sessão;
- `usuarioAutenticado()`: retorna `true` ou `false`;
- `usuarioAdmin()`: verifica o perfil administrativo;
- `exigirAutenticacao()`: redireciona visitantes não autenticados;
- `exigirAdmin()`: bloqueia usuários sem permissão de administrador;
- `obterUsuarioAtual()`: recupera os dados do usuário da sessão;
- `criarUsuario()`, `listarUsuarios()`, `atualizarUsuario()` e
  `deletarUsuario()`: administram usuários.

Conceitos para estudar:

- sessões HTTP;
- hashing de senhas;
- funções reutilizáveis;
- validação de e-mail;
- leitura e gravação de JSON;
- autorização, que é diferente de autenticação: autenticação confirma quem
  é o usuário; autorização define o que ele pode fazer.

### `acesso_negado.php`

Exibe uma página amigável quando o usuário está autenticado, mas não possui
permissão para acessar uma área administrativa.

### `config.php`

Concentra configurações compartilhadas:

- token do GitHub lido pela variável de ambiente `GITHUB_TOKEN`;
- usuário, repositório e branch do GitHub;
- caminhos dos arquivos JSON;
- caminho do arquivo de log;
- indicação se a sincronização está habilitada;
- intervalo de sincronização.

Também contém:

- `logSync()`: grava mensagens de operação em `dados/logs/sync.log`;
- `verificarConfiguracao()`: identifica configurações ausentes.

A variável de ambiente é usada para não colocar um token secreto diretamente
no código versionado.

### `github_sync.php`

Implementa a integração com a API do GitHub.

A classe `GitHubSync` encapsula operações como consultar arquivos, criar ou
atualizar conteúdo e enviar dados para o repositório. A função
`sincronizarComGitHub()` é uma interface mais simples para o restante do
sistema solicitar a sincronização.

Conceitos para estudar:

- classes e métodos em PHP;
- programação orientada a objetos;
- requisições HTTP com cURL;
- cabeçalhos HTTP e autenticação por token;
- conversão entre arrays PHP e JSON;
- tratamento de respostas de uma API externa.

### `dashboard.php`

É o painel inicial após o login. Apresenta uma visão resumida do sistema,
como indicadores e atalhos para os módulos.

Ele mostra como uma página PHP pode:

- exigir autenticação antes de renderizar;
- ler informações dos arquivos JSON;
- calcular totais;
- misturar lógica PHP com marcação HTML.

### `ativos.php`

Representa a tela de ativos de TI. Atualmente funciona principalmente como
estrutura visual, com menu, título, botão de novo ativo, campo de pesquisa e
tabela.

É um bom arquivo para estudar a diferença entre uma interface pronta e uma
funcionalidade completa: alguns controles visuais ainda não possuem backend ou
JavaScript conectado.

### `colaboradores.php`

É a tela visual de colaboradores. Apresenta a tabela e os campos básicos para
um futuro cadastro e pesquisa.

Assim como `ativos.php`, serve como base de interface, mas não possui um fluxo
completo de persistência implementado na página.

### `chamados.php`

É a interface de atendimento de chamados e contém bastante JavaScript.

Funcionalidades principais:

- listar chamados;
- abrir e fechar o formulário;
- visualizar detalhes;
- fechar ou dar baixa em um chamado;
- excluir chamados;
- pesquisar;
- importar CSV;
- mostrar prévia da importação;
- detectar chamados antigos ou ainda não tratados;
- emitir alerta sonoro;
- alternar entre chamados abertos e histórico.

A página chama `api_chamados.php` com `fetch()`. Também mantém dados no
`localStorage` como fallback caso o servidor não responda.

Conceitos para estudar:

- JavaScript assíncrono com `async` e `await`;
- API HTTP;
- eventos de formulário;
- `localStorage`;
- leitura de arquivos no navegador;
- transformação de CSV em objetos JavaScript;
- atualização dinâmica do DOM.

### `api_chamados.php`

É o backend REST dos chamados. Primeiro verifica a autenticação, identifica o
método HTTP e a ação solicitada na URL.

Operações:

- `GET ...?acao=listar`: lista chamados;
- `POST ...?acao=adicionar`: cria um chamado;
- `POST ...?acao=importar`: importa vários chamados;
- `PUT ...?acao=atualizar`: altera um chamado;
- `DELETE ...?acao=deletar`: remove um chamado.

As funções principais leem `dados/chamados.json`, validam a entrada, alteram
o array e devolvem respostas JSON com códigos HTTP apropriados.

Conceitos para estudar:

- métodos HTTP;
- API REST;
- `json_decode()` e `json_encode()`;
- `file_get_contents()` e `file_put_contents()`;
- códigos HTTP;
- entrada e saída em formato JSON.

### `estoque.php`

É o módulo de controle de aparelhos e estoque. Trabalha com IMEI, status,
chips e importações de CSV e XLSX.

Funções importantes:

- normalização de IMEI e status;
- leitura e gravação de JSON;
- busca por IMEI ou termo;
- remoção de duplicidades;
- importação de CSV;
- leitura de planilhas XLSX;
- exportação e filtragem dos dados para exibição.

O arquivo também possui HTML, PHP e JavaScript na mesma página, um padrão
comum em aplicações PHP tradicionais, embora projetos maiores normalmente
separem melhor backend, frontend e serviços.

### `detalhes_imei.php`

Recebe um IMEI pela URL, por exemplo `detalhes_imei.php?imei=123`, normaliza o
valor e procura o aparelho em `estoque.json` e `enviados.json`.

Depois apresenta um resumo com modelo, status, nome, canal e dados do chip.
Usa `htmlspecialchars()` ao imprimir os valores.

É um exemplo didático de:

- parâmetros `$_GET`;
- busca em arrays;
- valores alternativos com `??`;
- preparação de dados antes da renderização;
- proteção da saída HTML.

### `enviados.php`

Controla aparelhos que já foram entregues ou enviados. Possui recursos de
leitura, filtragem, importação e gravação em `dados/enviados.json`.

A função `normalizarRegistroEnviado()` transforma diferentes formatos de
entrada em um formato interno padronizado. O arquivo também lê CSV e XLSX,
remove duplicidades por IMEI e infere o canal quando necessário.

É um bom exemplo para estudar limpeza de dados: antes de salvar, a aplicação
converte nomes de colunas, remove espaços, normaliza valores e evita registros
repetidos.

### `centros_de_custo.php`

É a tela de gerenciamento de centros de custo. O JavaScript chama a API para
listar, criar, editar e excluir registros.

Recursos da tela:

- filtro por tipo;
- pesquisa por centro, filial ou gerente;
- modal de cadastro e edição;
- visualização de detalhes;
- importação de arquivos;
- exportação para CSV;
- cópia da tabela;
- referência rápida de contatos.

### `api_centros_de_custo.php`

É o backend dos centros de custo. Trabalha com `dados/centros_de_custo.json`
e possui operações HTTP para listar, adicionar, atualizar, excluir e importar.

Uma parte mais avançada do arquivo interpreta arquivos XLSX diretamente. Como
XLSX é um pacote ZIP de arquivos XML, o código usa `ZipArchive` para localizar
planilhas, strings compartilhadas, relacionamentos e células.

Funções como `lerStringsCompartilhadasExcel()`,
`lerRelacionamentosExcel()` e `lerPlanilhaExcel()` mostram como uma biblioteca
pode ser substituída por leitura manual do formato interno de uma planilha.

### `relatorios.php`

Apresenta atalhos visuais para relatórios de inventário, ativos por setor,
estoque e chamados. Atualmente a página é uma estrutura de navegação para
futuras consultas e exportações.

### `configuracoes.php`

Exibe informações gerais do sistema e documenta, na própria interface, alguns
recursos do estoque e da aba de enviados.

### `gerar_senha.php`

É um pequeno utilitário PHP relacionado à geração de senha. Por ser muito
curto, deve ser aberto diretamente para estudar seu comportamento exato antes
de utilizá-lo em produção.

### `acesso_negado.php`, `README.md`, `AUTENTICACAO.md`,
`SINCRONIZACAO.md` e `GITHUB_SETUP.md`

Esses arquivos completam a documentação e a experiência do sistema:

- `README.md`: apresentação geral, instalação e tecnologias;
- `AUTENTICACAO.md`: detalhes do login, sessões e perfis;
- `SINCRONIZACAO.md`: arquitetura e endpoints da sincronização de chamados;
- `GITHUB_SETUP.md`: configuração do token e do repositório GitHub;
- `acesso_negado.php`: resposta visual para uma tentativa sem permissão.

## 3. Pasta `assets`

### `assets/style.css`

É a folha de estilos compartilhada. Define cores, espaçamentos, menu lateral,
botões, tabelas, cards, modais, alertas, formulários e comportamento visual
responsivo.

O HTML usa classes, como `sidebar`, `content`, `toolbar`, `panel`, `button` e
`table-chamados`. O CSS procura essas classes e determina como cada elemento
será exibido.

Conceitos para estudar:

- seletores de classe;
- box model;
- Flexbox e Grid;
- variáveis CSS;
- media queries;
- estados como `:hover` e `:focus`.

## 4. Pasta `dados`

Os arquivos JSON são a persistência atual da aplicação. JSON é texto
estruturado em objetos e listas, fácil de ler por PHP e JavaScript.

### `dados/usuarios.json`

Armazena usuários, seus nomes, e-mails, perfis, estado ativo e senha com hash.
Não deve conter senhas em texto puro.

### `dados/chamados.json`

Armazena os chamados de suporte, incluindo número, solicitante, descrição,
status, datas e outros campos utilizados pela tela de chamados.

### `dados/estoque.json`

Armazena aparelhos disponíveis, seus IMEIs, modelos, status e informações de
chip.

### `dados/enviados.json`

Armazena aparelhos que foram enviados ou entregues, incluindo vínculo com
nome, canal, IMEI e chip.

### `dados/centros_de_custo.json`

Armazena os centros de custo importados ou cadastrados. É um arquivo de dados
operacional e pode crescer bastante.

### `dados/centros_de_custo_backup.json`

É uma cópia de segurança dos centros de custo. Backups devem ser preservados
com cuidado e não devem ser confundidos com o arquivo principal utilizado pela
aplicação.

### Arquivos CSV e XLSX

- `cds_importacao_exemplo.csv`: exemplo de importação de centros de custo;
- `enviados_importacao_exemplo.csv`: exemplo de importação de aparelhos
  enviados;
- `enviados_template.xlsx`: modelo de planilha para enviados;
- `teste2-centro.xlsx`: arquivo de teste de importação de centros de custo.

Eles ajudam a entender o formato esperado para importação e podem ser usados
para testar o fluxo sem digitar todos os registros manualmente.

### `dados/logs/sync.log`

É o registro textual das tentativas de sincronização com o GitHub. Cada linha
normalmente contém data, tipo da mensagem e descrição do evento.

## 5. Pasta `database`

Esses arquivos descrevem uma alternativa baseada em banco relacional. Eles
usam SQL e alguns scripts PHP de terminal. O fluxo atual da aplicação web não
depende diretamente deles, mas são excelentes materiais para estudar SQL.

### `01_tabelas.sql`

Cria tabelas como `colaboradores`, `pecas_estoque`,
`chamados_suporte` e `chips`. É o arquivo de estrutura: define colunas, tipos,
chaves e relacionamentos.

### `02_peca_estoque`

Contém dados ou comandos de carga relacionados às peças do estoque. A extensão
não é `.sql`, mas o conteúdo deve ser tratado como script de banco.

### `03_chamados.sql`

Limpa dados anteriores de chamados e insere registros de exemplo usando uma
CTE recursiva para gerar uma sequência de números.

Conceitos para estudar:

- `DELETE`;
- `INSERT`;
- `WITH`;
- `SELECT`;
- geração de dados de teste.

### `04_colaboradores.sql`

Insere colaboradores de exemplo. Serve para popular a tabela e testar
relatórios e relacionamentos.

### `05_consultas_e_views.sql`

Cria a view `v_relatorio_estoque`. Uma view é uma consulta salva que pode ser
consultada como se fosse uma tabela. Nesse caso, ela calcula informações do
estoque a partir de peças e chamados.

### `07_cadastrar_ativo.sql`

Demonstra como inserir um ativo ou peça no estoque usando `INSERT INTO`.

### `08_cadastrar_chamado.sql`

Demonstra como cadastrar um chamado relacionado a colaborador e peça.

### `09_relatorio_inventario.php`

É um script PHP de terminal que gera um relatório de inventário. A função
`gerar_relatorio()` consulta dados e imprime o resultado.

### `10_busca_chamado_por_colaborador.php`

É um script interativo de terminal. Usa `readline()` para receber um
identificador ou dado do colaborador e buscar seus chamados.

### `11_atualizar_status_chamado.php`

Também é um script de terminal. Recebe uma entrada, localiza o chamado e
atualiza seu status.

### `12_centros_de_custo.sql`

Cria a tabela relacional `centros_custo`, com a estrutura necessária para
representar centros, filiais, responsáveis e demais informações.

## 6. Conceitos para estudar na ordem certa

Uma sequência recomendada para aprender com este projeto:

1. HTML básico em `login.php` e `ativos.php`;
2. CSS em `assets/style.css`;
3. PHP com variáveis, condicionais e funções em `login.php`;
4. sessões e autenticação em `auth.php`;
5. leitura e gravação de JSON em `api_chamados.php`;
6. JavaScript e eventos em `chamados.php`;
7. `fetch()` e APIs REST;
8. importação e normalização de dados em `estoque.php` e `enviados.php`;
9. SQL e relacionamentos na pasta `database`;
10. classes, cURL e integração externa em `github_sync.php`.

## 7. Exemplo de fluxo completo

Ao criar um chamado:

1. o usuário preenche o formulário em `chamados.php`;
2. JavaScript captura o evento `submit`;
3. o navegador envia os dados com `fetch()` para
   `api_chamados.php?acao=adicionar`;
4. a API verifica a sessão;
5. o PHP lê `chamados.json`;
6. o novo chamado é adicionado ao array;
7. o arquivo JSON é salvo;
8. a API devolve uma resposta JSON;
9. o JavaScript atualiza a tabela na tela;
10. se configurado, o sistema pode sincronizar a alteração com o GitHub.

Esse fluxo reúne frontend, backend, persistência, autenticação e integração
externa em uma única funcionalidade.

## 8. Exercícios sugeridos

1. Adicionar uma coluna de prioridade aos chamados.
2. Criar um filtro por prioridade em `chamados.php`.
3. Criar uma API de colaboradores seguindo o padrão de `api_chamados.php`.
4. Fazer o botão “Novo Ativo” abrir um formulário real.
5. Criar uma página que conte chamados por status.
6. Substituir uma tela estática por dados vindos de JSON.
7. Criar uma consulta SQL que liste chamados e nomes de colaboradores.
8. Adicionar mensagens de erro para arquivos JSON inválidos.
9. Separar o JavaScript de uma página PHP em um arquivo próprio.
10. Criar testes manuais para cada endpoint da API.

Ao estudar, altere uma funcionalidade pequena por vez e observe o caminho
completo: entrada do usuário, validação, processamento, gravação e resposta.
