<?php

header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/github_sync.php';
exigirAutenticacao();

$arquivo = __DIR__ . '/dados/centros_de_custo.json';

// Criar diretório se não existir
if (!is_dir(__DIR__ . '/dados')) {
    mkdir(__DIR__ . '/dados', 0777, true);
}

// Garantir que o arquivo existe
if (!file_exists($arquivo)) {
    file_put_contents($arquivo, json_encode([]));
}

$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';

if ($metodo === 'OPTIONS') {
    http_response_code(204);
    exit;
}

switch ($metodo) {

    case 'GET':

        if ($acao === 'listar') {
            listarCentros();
        } else {
            responderErroCentro('Ação não reconhecida', 400);
        }

        break;

    case 'POST':

        if ($acao === 'adicionar') {

            adicionarCentro();

        } elseif ($acao === 'importar') {

            importarCentros();

        } else {
            responderErroCentro('Ação não reconhecida', 400);
        }

        break;

    case 'PUT':

        if ($acao === 'atualizar') {
            atualizarCentro();
        } else {
            responderErroCentro('Ação não reconhecida', 400);
        }

        break;

    case 'DELETE':

        if ($acao === 'deletar') {
            deletarCentro();
        } else {
            responderErroCentro('Ação não reconhecida', 400);
        }

        break;

    default:

        http_response_code(400);

        echo json_encode([
            'erro' => 'Ação não reconhecida'
        ]);
}

function responderErroCentro(string $mensagem, int $status): void
{
    http_response_code($status);
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

function listarCentros()
{
    global $arquivo;

    $centros = json_decode(
        file_get_contents($arquivo),
        true
    );

    echo json_encode($centros ?? []);
}

function adicionarCentro()
{
    global $arquivo;

    $dados = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (
        empty($dados['numeroCentro']) ||
        empty($dados['bandeira']) ||
        empty($dados['filial'])
    ) {

        http_response_code(400);

        echo json_encode([
            'erro' => 'Dados obrigatórios não informados'
        ]);

        return;
    }

    $centros = json_decode(
        file_get_contents($arquivo),
        true
    ) ?? [];

    foreach ($centros as $centroExistente) {

    if (
        $centroExistente['numeroCentro'] === $dados['numeroCentro']
    ) {

        http_response_code(400);

        echo json_encode([
            'erro' => 'Centro de custo já cadastrado'
        ]);

        return;
    }
}

    $dados['id'] = uniqid();

    $centros[] = $dados;

    if (
        file_put_contents(
            $arquivo,
            json_encode(
                $centros,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        )
    ) {

        sincronizarComGitHub(
            "Novo centro de custo cadastrado: {$dados['numeroCentro']}",
            'centros'
        );

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Centro de custo cadastrado com sucesso'
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            'erro' => 'Erro ao salvar'
        ]);
    }
}

function atualizarCentro()
{
    global $arquivo;

    $dados = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (empty($dados['id'])) {

        http_response_code(400);

        echo json_encode([
            'erro' => 'ID não informado'
        ]);

        return;
    }

    $centros = json_decode(
        file_get_contents($arquivo),
        true
    ) ?? [];

    foreach ($centros as &$centro) {

        if ($centro['id'] === $dados['id']) {

            $centro = array_merge(
                $centro,
                $dados
            );

            break;
        }
    }

    if (
        file_put_contents(
            $arquivo,
            json_encode(
                $centros,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        )
    ) {

        sincronizarComGitHub(
            "Centro de custo atualizado: {$dados['numeroCentro']}",
            'centros'
        );

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Centro de custo atualizado'
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            'erro' => 'Erro ao atualizar'
        ]);
    }
}

function deletarCentro()
{
    global $arquivo;

    $dados = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (empty($dados['id'])) {

        http_response_code(400);

        echo json_encode([
            'erro' => 'ID não informado'
        ]);

        return;
    }

    $centros = json_decode(
        file_get_contents($arquivo),
        true
    ) ?? [];

    $centros = array_filter(
        $centros,
        function ($centro) use ($dados) {

            return $centro['id'] !== $dados['id'];

        }
    );

    if (
        file_put_contents(
            $arquivo,
            json_encode(
                array_values($centros),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        )
    ) {

        sincronizarComGitHub(
            "Centro de custo removido: {$dados['id']}",
            'centros'
        );

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Centro de custo removido'
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            'erro' => 'Erro ao remover'
        ]);
    }
}

function importarCentros()
{
    global $arquivo;

    if (!empty($_FILES['arquivo']['tmp_name'])) {
        $dados = lerCentrosDoArquivo($_FILES['arquivo']);

        if (isset($dados['erro'])) {
            responderErroCentro($dados['erro'], 400);
        }
    } else {
        $dados = json_decode(
            file_get_contents('php://input'),
            true
        );
    }

    if (
        empty($dados) ||
        !is_array($dados)
    ) {

        http_response_code(400);

        echo json_encode([
            'erro' => 'Dados inválidos'
        ]);

        return;
    }

    $centros = json_decode(
        file_get_contents($arquivo),
        true
    ) ?? [];

    $totalImportado = 0;
    $totalAtualizado = 0;

   foreach ($dados as $centro) {

   if (
       empty($centro['numeroCentro'])
   ) {
       continue;
   }

    $indiceExistente = null;

    foreach ($centros as $indice => $existente) {

        if (
            trim($existente['numeroCentro']) ===
            trim($centro['numeroCentro'])
        ) {

            $indiceExistente = $indice;
            break;
        }
    }

    if ($indiceExistente !== null) {
        $centro['id'] = $centros[$indiceExistente]['id'] ?? uniqid();
        $centros[$indiceExistente] = array_merge(
            $centros[$indiceExistente],
            $centro
        );
        $totalAtualizado++;
        continue;
    }

    $centro['id'] = uniqid();

    $centros[] = $centro;

    $totalImportado++;
}

    if (
        file_put_contents(
            $arquivo,
            json_encode(
                $centros,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        )
    ) {

        sincronizarComGitHub(
            "Importação de {$totalImportado} centros",
            'centros'
        );

        echo json_encode([
            'sucesso' => true,
            'total' => $totalImportado,
            'atualizados' => $totalAtualizado,
            'mensagem' => "{$totalImportado} centros importados e {$totalAtualizado} atualizados"
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            'erro' => 'Erro ao importar'
        ]);
    }
}

function lerCentrosDoArquivo(array $upload): array
{
    $nome = strtolower($upload['name'] ?? '');
    $extensao = pathinfo($nome, PATHINFO_EXTENSION);

    if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['erro' => 'O upload do arquivo falhou. Tente novamente.'];
    }

    if ($extensao === 'xlsx') {
        return lerCentrosDoExcel($upload['tmp_name']);
    }

    if ($extensao === 'csv') {
        return lerCentrosDoCsv($upload['tmp_name']);
    }

    if ($extensao === 'xls') {
        return ['erro' => 'Este arquivo está no formato Excel antigo (.xls). Abra-o no Excel e salve como CSV UTF-8 ou .xlsx antes de importar.'];
    }

    return ['erro' => 'Formato inválido. Envie um arquivo .csv ou .xlsx.'];
}

function lerCentrosDoExcel(string $caminho): array
{
    if (!class_exists('ZipArchive')) {
        return ['erro' => 'O servidor não possui suporte para arquivos XLSX (extensão ZipArchive).'];
    }

    $zip = new ZipArchive();
    if ($zip->open($caminho) !== true) {
        return ['erro' => 'Não foi possível abrir o arquivo Excel.'];
    }

    try {
        $strings = lerStringsCompartilhadasExcel($zip);
        $relacionamentos = lerRelacionamentosExcel($zip);
        $abas = lerAbasExcel($zip, $relacionamentos);

        if (empty($abas['Informações cadastrais']) || empty($abas['Centro de custo'])) {
            return ['erro' => 'O Excel deve conter as abas "Informações cadastrais" e "Centro de custo".'];
        }

        $cadastros = lerPlanilhaExcel($zip, $abas['Informações cadastrais'], $strings);
        $codigos = lerPlanilhaExcel($zip, $abas['Centro de custo'], $strings);
        $contatos = !empty($abas['Contatos lojas'])
            ? lerPlanilhaExcel($zip, $abas['Contatos lojas'], $strings)
            : [];

        $mapaCentros = [];
        foreach ($codigos as $linha) {
            $filial = normalizarChaveExcel($linha['Filial SAP'] ?? '');
            $codigo = trim((string) ($linha['Centro de Custo'] ?? ''));

            if ($filial !== '' && $codigo !== '') {
                $mapaCentros[$filial] = [
                    'codigo' => $codigo,
                    'nome' => trim((string) ($linha['FL + Grupo'] ?? ''))
                ];
            }
        }

        $mapaContatos = [];
        foreach ($contatos as $linha) {
            $filial = normalizarChaveExcel($linha['Filial SAP'] ?? '');
            if ($filial !== '') {
                $mapaContatos[$filial] = [
                    'telefone1' => trim((string) ($linha['Contato 1'] ?? '')),
                    'telefone2' => trim((string) ($linha['Contato 2'] ?? '')),
                    'email' => trim((string) ($linha['E-mail'] ?? ''))
                ];
            }
        }

        $dados = [];
        foreach ($cadastros as $linha) {
            $filial = normalizarChaveExcel($linha['Filial SAP'] ?? '');
            $centro = $mapaCentros[$filial] ?? [];
            $numeroCentro = $centro['codigo'] ?? '';
            $contato = $mapaContatos[$filial] ?? [];

            if ($filial === '' || $numeroCentro === '') {
                continue;
            }

            $dados[] = [
                'tipo' => 'Loja',
                'numeroCentro' => $numeroCentro,
                'filial' => $centro['nome'] ?: trim((string) ($linha['Filial SAP'] ?? '')),
                'bandeira' => trim((string) ($linha['Bandeira'] ?? '')),
                'gerente' => trim((string) ($linha['Regional'] ?? '')),
                'cnpj' => trim((string) ($linha['CNPJ'] ?? '')),
                'razaoSocial' => trim((string) ($linha['Razão social'] ?? '')),
                'endereco' => trim((string) ($linha['Endereço'] ?? '')),
                'numero' => trim((string) ($linha['N°'] ?? '')),
                'complemento' => trim((string) ($linha['Complemento'] ?? '')),
                'bairro' => trim((string) ($linha['Bairro'] ?? '')),
                'regiao' => trim((string) ($linha['Região'] ?? '')),
                'cidade' => trim((string) ($linha['Cidade'] ?? '')),
                'uf' => trim((string) ($linha['UF'] ?? '')),
                'cep' => trim((string) ($linha['CEP'] ?? '')),
                'perfil' => trim((string) ($linha['Perfil'] ?? '')),
                'status' => trim((string) ($linha['Status'] ?? '')),
                'grupo' => trim((string) ($linha['Grupo'] ?? ''))
            ];
            $dados[array_key_last($dados)]['telefone1'] = $contato['telefone1'] ?? '';
            $dados[array_key_last($dados)]['telefone2'] = $contato['telefone2'] ?? '';
            $dados[array_key_last($dados)]['email'] = $contato['email'] ?? '';
        }

        return $dados;
    } finally {
        $zip->close();
    }
}

function lerStringsCompartilhadasExcel(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }

    $documento = simplexml_load_string($xml);
    $strings = [];
    $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    foreach ($documento->children($namespace)->si as $item) {
        $texto = '';
        foreach ($item->children($namespace)->t as $parte) {
            $texto .= (string) $parte;
        }
        $strings[] = $texto;
    }

    return $strings;
}

function lerCentrosDoCsv(string $caminho): array
{
    $arquivoCsv = fopen($caminho, 'rb');
    if ($arquivoCsv === false) {
        return ['erro' => 'Não foi possível abrir o arquivo CSV.'];
    }

    $cabecalhos = fgetcsv($arquivoCsv, 0, ';');
    if ($cabecalhos === false) {
        fclose($arquivoCsv);
        return ['erro' => 'O arquivo CSV está vazio.'];
    }

    $cabecalhos = array_map(static fn ($valor) => trim((string) $valor), $cabecalhos);
    $dados = [];

    while (($colunas = fgetcsv($arquivoCsv, 0, ';')) !== false) {
        $linha = [];
        foreach ($cabecalhos as $indice => $cabecalho) {
            $linha[$cabecalho] = trim((string) ($colunas[$indice] ?? ''));
        }

        if (isset($linha['Código CC'])) {
            $dados[] = [
                'tipo' => 'CDS',
                'numeroCentro' => $linha['Código CC'],
                'colaborador' => $linha['Colaborador'] ?? '',
                'cpf' => $linha['CPF'] ?? '',
                'estabelecimento' => $linha['Estabelecimento'] ?? '',
                'descricaoCC' => $linha['Descrição CC'] ?? ''
            ];
        }
    }

    fclose($arquivoCsv);
    return $dados;
}

function lerRelacionamentosExcel(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $documento = simplexml_load_string($xml);
    $relacionamentos = [];

    foreach ($documento->Relationship as $relacionamento) {
        $relacionamentos[(string) $relacionamento['Id']] = (string) $relacionamento['Target'];
    }

    return $relacionamentos;
}

function lerAbasExcel(ZipArchive $zip, array $relacionamentos): array
{
    $xml = $zip->getFromName('xl/workbook.xml');
    $documento = simplexml_load_string($xml);
    $documento->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $documento->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $abas = [];

    foreach ($documento->xpath('//x:sheets/x:sheet') ?: [] as $aba) {
        $id = (string) $aba->attributes('r', true)->id;
        $caminho = $relacionamentos[$id] ?? '';
        $caminho = ltrim($caminho, '/');
        $abas[(string) $aba['name']] = str_starts_with($caminho, 'xl/') ? $caminho : 'xl/' . $caminho;
    }

    return $abas;
}

function lerPlanilhaExcel(ZipArchive $zip, string $caminho, array $strings): array
{
    $xml = $zip->getFromName($caminho);
    if ($xml === false) {
        return [];
    }

    $documento = simplexml_load_string($xml);
    $documento->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $linhas = $documento->xpath('//x:sheetData/x:row') ?: [];
    $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $cabecalhos = [];
    $resultado = [];

    foreach ($linhas as $indice => $linha) {
        $valores = [];
        foreach ($linha->children($namespace)->c as $celula) {
            $atributos = $celula->attributes();
            $referencia = (string) $atributos['r'];
            preg_match('/^[A-Z]+/', $referencia, $coluna);
            $coluna = $coluna[0] ?? '';
            $valor = isset($celula->children($namespace)->v)
                ? (string) $celula->children($namespace)->v
                : '';

            if ((string) $atributos['t'] === 's' && isset($strings[(int) $valor])) {
                $valor = $strings[(int) $valor];
            }

            $valores[$coluna] = trim($valor);
        }

        if ($indice === 0) {
            $cabecalhos = $valores;
            continue;
        }

        $registro = [];
        foreach ($cabecalhos as $coluna => $cabecalho) {
            if ($cabecalho !== '') {
                $registro[$cabecalho] = $valores[$coluna] ?? '';
            }
        }

        if (array_filter($registro, static fn ($valor) => $valor !== '')) {
            $resultado[] = $registro;
        }
    }

    return $resultado;
}

function normalizarChaveExcel($valor): string
{
    return preg_replace('/^0+(?=\d)/', '', trim((string) $valor));
}

?>