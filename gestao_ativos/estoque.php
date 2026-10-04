<?php
ini_set('memory_limit', '1024M');
set_time_limit(300);

require_once __DIR__ . '/auth.php';
exigirAutenticacao();

function normalizarImei($valor): string
{
    return preg_replace('/\D+/', '', (string) $valor);
}

function normalizarStatus($valor): string
{
    $valor = strtolower(trim((string) $valor));
    $mapa = [
        'disponivel' => 'em_estoque',
        'em estoque' => 'em_estoque',
        'estoque' => 'em_estoque',
        'pendente' => 'pendente',
        'reservado' => 'pendente',
        'enviado' => 'enviado',
        'vendido' => 'enviado',
        'manutencao' => 'pendente',
    ];

    return $mapa[$valor] ?? ($valor === '' ? 'em_estoque' : $valor);
}

function normalizarChipStatus($valor): string
{
    $valor = strtolower(trim((string) $valor));
    $mapa = [
        'disponivel' => 'Disponível',
        'em estoque' => 'Disponível',
        'ativo' => 'Ativo',
        'vinculado' => 'Vinculado',
        'ocupado' => 'Vinculado',
        'reservado' => 'Reservado',
        'cancelado' => 'Cancelado',
    ];

    if ($valor === '') {
        return '';
    }

    return $mapa[$valor] ?? ucfirst($valor);
}

function extrairChipDados(array $linha): array
{
    $iccid = trim((string) ($linha['iccid'] ?? $linha['chip'] ?? $linha['icc_id'] ?? $linha['numero_iccid'] ?? ''));
    $statusChip = normalizarChipStatus($linha['chip_status'] ?? $linha['status_chip'] ?? $linha['status_do_chip'] ?? '');
    $linhaCadastrada = trim((string) ($linha['linha_cadastrada'] ?? $linha['linha'] ?? $linha['numero_linha'] ?? $linha['linha_telefonica'] ?? ''));
    $pessoa = trim((string) ($linha['pessoa'] ?? $linha['nome_pessoa'] ?? $linha['responsavel'] ?? $linha['nome_vinculado'] ?? $linha['vinculado'] ?? $linha['cliente'] ?? ''));

    $chip = [];
    if ($iccid !== '') {
        $chip['iccid'] = $iccid;
    }
    if ($statusChip !== '') {
        $chip['status'] = $statusChip;
    }
    if ($linhaCadastrada !== '') {
        $chip['linha_cadastrada'] = $linhaCadastrada;
    }
    if ($pessoa !== '') {
        $chip['pessoa'] = $pessoa;
    }

    return $chip;
}

function carregarJson(string $arquivo, array $padrao = []): array
{
    if (!file_exists($arquivo)) {
        file_put_contents($arquivo, json_encode($padrao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $padrao;
    }

    $json = json_decode(file_get_contents($arquivo), true);
    return is_array($json) ? $json : $padrao;
}

function salvarJson(string $arquivo, array $dados): void
{
    file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function localizarNaLista(array $lista, string $imei): ?array
{
    foreach ($lista as $item) {
        if (($item['imei'] ?? '') === $imei) {
            return $item;
        }
    }
    return null;
}

function buscarPorTermo(array $lista, string $termo): array
{
    if ($termo === '') {
        return $lista;
    }

    $term = strtolower($termo);
    return array_values(array_filter($lista, function ($item) use ($term) {
        $chip = $item['chip'] ?? [];
        $texto = strtolower(
            ($item['imei'] ?? '') . ' ' .
            ($item['modelo'] ?? '') . ' ' .
            ($item['status'] ?? '') . ' ' .
            ($item['observacao'] ?? '') . ' ' .
            ($chip['iccid'] ?? '') . ' ' .
            ($chip['status'] ?? '') . ' ' .
            ($chip['linha_cadastrada'] ?? '') . ' ' .
            ($chip['pessoa'] ?? '')
        );
        return str_contains($texto, $term);
    }));
}

function formatarStatus(string $status): string
{
    $status = strtolower(trim($status));
    $mapa = [
        'em_estoque' => 'Em estoque',
        'pendente' => 'Pendente',
        'enviado' => 'Enviado',
        'disponivel' => 'Em estoque',
    ];

    return $mapa[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function removerDuplicadosPorImei(array $lista): array
{
    $deduplicada = [];
    $seen = [];

    foreach ($lista as $item) {
        $imei = normalizarImei($item['imei'] ?? '');
        if ($imei === '' || isset($seen[$imei])) {
            continue;
        }

        $seen[$imei] = true;
        $deduplicada[] = $item;
    }

    return array_values($deduplicada);
}

function colunaParaIndice(string $coluna): int
{
    $valor = 0;
    foreach (str_split(strtoupper($coluna)) as $char) {
        $valor = $valor * 26 + ((ord($char) - 64));
    }
    return $valor;
}

function valorDaCelula($celula, array $sharedStrings = []): string
{
    if (!is_object($celula)) {
        return '';
    }

    $tipo = (string) $celula['t'];
    if ($tipo === 'inlineStr') {
        return trim((string) $celula->is->t);
    }

    if ($tipo === 's') {
        $indice = (int) ((string) $celula->v);
        return $sharedStrings[$indice] ?? '';
    }

    if ($tipo === 'b') {
        return ((string) $celula->v) === '1' ? 'true' : 'false';
    }

    return trim((string) $celula->v);
}

function importarCsv(array $upload): array
{
    $handle = fopen($upload['tmp_name'], 'r');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível ler o arquivo CSV.');
    }

    $primeiraLinha = fgetcsv($handle, 0, ';');
    if ($primeiraLinha === false) {
        fclose($handle);
        return [];
    }

    $delimiter = ';';
    if (count($primeiraLinha) === 1) {
        rewind($handle);
        $primeiraLinha = fgetcsv($handle, 0, ',');
        if (count($primeiraLinha) > 1) {
            $delimiter = ',';
        }
    }

    rewind($handle);
    $cabecalhos = [];
    $resultados = [];
    while (($linha = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (empty($cabecalhos) && !empty($linha)) {
            $cabecalhos = array_map(function ($campo) {
                $campo = trim((string) $campo);
                $campo = str_replace(['-', '_'], ' ', $campo);
                $campo = preg_replace('/[^\pL\pN\s]/u', '', $campo);
                return strtolower(trim((string) $campo));
            }, $linha);
            continue;
        }

        if (!empty($linha) && count($linha) > 1) {
            $dadosLinha = array_combine($cabecalhos, $linha);
            if (!is_array($dadosLinha)) {
                continue;
            }

            $imei = normalizarImei($dadosLinha['imei'] ?? ($dadosLinha['imei_'] ?? $dadosLinha['numero_imei'] ?? ''));
            $modelo = trim((string) ($dadosLinha['modelo'] ?? $dadosLinha['nome_modelo'] ?? ''));
            $status = normalizarStatus($dadosLinha['status'] ?? 'em_estoque');
            $chip = extrairChipDados($dadosLinha);

            if ($imei === '' || $modelo === '') {
                continue;
            }

            $registro = [
                'imei' => $imei,
                'modelo' => $modelo,
                'status' => $status,
                'observacao' => trim((string) ($dadosLinha['observacao'] ?? ''))
            ];

            if (!empty($chip)) {
                $registro['chip'] = $chip;
            }

            $resultados[] = $registro;
        }
    }
    fclose($handle);

    return $resultados;
}

function importarXlsx(array $upload): array
{
    $zip = new ZipArchive();
    $abriu = $zip->open($upload['tmp_name']);
    if ($abriu !== true) {
        throw new RuntimeException('O arquivo do Excel não pôde ser aberto.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        if ($xml !== false) {
            foreach ($xml->si as $item) {
                $sharedStrings[] = trim((string) $item->t);
            }
        }
    }

    $workbookXml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
    $relsXml = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
    $relMap = [];
    foreach ($relsXml->Relationship as $rel) {
        $relMap[(string) $rel['Id']] = (string) $rel['Target'];
    }

    $primeiraFolha = $workbookXml->sheets->sheet[0];
    $sheetId = (string) $primeiraFolha->attributes()['{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id'];
    $sheetPath = 'xl/' . $relMap[$sheetId];
    $sheetXml = simplexml_load_string($zip->getFromName($sheetPath));
    $zip->close();

    $rows = [];
    foreach ($sheetXml->sheetData->row as $row) {
        $values = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $coluna = preg_replace('/[^A-Z]/', '', $ref);
            $values[colunaParaIndice($coluna)] = valorDaCelula($cell, $sharedStrings);
        }

        ksort($values);
        $rows[] = array_values($values);
    }

    if (empty($rows)) {
        return [];
    }

    $cabecalhos = array_map(function ($valor) {
        return strtolower(trim(preg_replace('/[^\pL\pN]+/u', ' ', $valor)));
    }, $rows[0]);

    $registros = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (count($row) === 0 || (count(array_filter($row, fn($valor) => trim((string) $valor) !== '')) === 0)) {
            continue;
        }

        $data = [];
        foreach ($cabecalhos as $indice => $campo) {
            $data[$campo] = $row[$indice] ?? '';
        }

        $imei = normalizarImei($data['imei'] ?? ($data['imei_'] ?? $data['numero_imei'] ?? ''));
        $modelo = trim((string) ($data['modelo'] ?? $data['model'] ?? $data['nome_modelo'] ?? ''));
        $status = normalizarStatus($data['status'] ?? 'em_estoque');
        $chip = extrairChipDados($data);

        if ($imei !== '' && $modelo !== '') {
            $registro = [
                'imei' => $imei,
                'modelo' => $modelo,
                'status' => $status,
                'observacao' => trim((string) ($data['observacao'] ?? '')),
            ];
            if (!empty($chip)) {
                $registro['chip'] = $chip;
            }
            $registros[] = $registro;
        }
    }

    return $registros;
}

$estoqueFile = __DIR__ . '/dados/estoque.json';
$enviadosFile = __DIR__ . '/dados/enviados.json';
$estoque = removerDuplicadosPorImei(carregarJson($estoqueFile, [
    ['imei' => '356123456789012', 'modelo' => 'iPhone 12', 'status' => 'em_estoque', 'observacao' => 'Estoque inicial'],
    ['imei' => '356123456789013', 'modelo' => 'Samsung Galaxy A54', 'status' => 'pendente', 'observacao' => 'Aguardando conferência'],
    ['imei' => '356123456789014', 'modelo' => 'Motorola G Power', 'status' => 'em_estoque', 'observacao' => 'Disponível para entrega']
]));
$enviados = removerDuplicadosPorImei(carregarJson($enviadosFile, [
    ['imei' => '356123456789015', 'nome' => 'Ana Souza', 'canal' => 'CD', 'modelo' => 'iPhone 11', 'data_envio' => '2026-08-10'],
    ['imei' => '356123456789016', 'nome' => 'Bruno Costa', 'canal' => 'VAREJO', 'modelo' => 'Samsung Galaxy S23', 'data_envio' => '2026-08-15'],
]));

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'importar') {
        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Selecione um arquivo para importar.';
        } else {
            $arquivo = $_FILES['arquivo'];
            $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            try {
                if ($ext === 'csv') {
                    $novos = importarCsv($arquivo);
                } elseif ($ext === 'xlsx' || $ext === 'xls') {
                    $novos = importarXlsx($arquivo);
                } else {
                    throw new RuntimeException('Formato de arquivo não suportado. Use CSV ou XLSX.');
                }

                $adicionados = 0;
                $ignorados = 0;
                foreach ($novos as $item) {
                    $imei = normalizarImei($item['imei']);
                    if ($imei === '' || localizarNaLista($estoque, $imei) !== null || localizarNaLista($enviados, $imei) !== null) {
                        $ignorados++;
                        continue;
                    }
                    $registro = [
                        'imei' => $imei,
                        'modelo' => trim((string) $item['modelo']),
                        'status' => normalizarStatus($item['status']),
                        'observacao' => trim((string) ($item['observacao'] ?? ''))
                    ];
                    if (!empty($item['chip'] ?? [])) {
                        $registro['chip'] = $item['chip'];
                    }
                    $estoque[] = $registro;
                    $adicionados++;
                }

                $estoque = removerDuplicadosPorImei($estoque);
                salvarJson($estoqueFile, $estoque);
                $mensagem = "Importação concluída. $adicionados item(ns) adicionado(s) e $ignorados ignorado(s) por duplicidade ou dados incompletos.";
            } catch (Throwable $th) {
                $erro = $th->getMessage();
            }
        }
    }

    if ($acao === 'adicionar_manual') {
        $imei = normalizarImei($_POST['imei'] ?? '');
        $modelo = trim((string) ($_POST['modelo'] ?? ''));
        $status = normalizarStatus($_POST['status'] ?? 'em_estoque');
        $chip = [];
        $iccid = trim((string) ($_POST['iccid'] ?? ''));
        if ($iccid !== '') {
            $chip['iccid'] = $iccid;
        }
        $chipStatus = trim((string) ($_POST['chip_status'] ?? ''));
        if ($chipStatus !== '') {
            $chip['status'] = normalizarChipStatus($chipStatus);
        }
        $chipLinha = trim((string) ($_POST['linha_cadastrada'] ?? ''));
        if ($chipLinha !== '') {
            $chip['linha_cadastrada'] = $chipLinha;
        }
        $chipPessoa = trim((string) ($_POST['pessoa_vinculada'] ?? ''));
        if ($chipPessoa !== '') {
            $chip['pessoa'] = $chipPessoa;
        }

        if ($imei === '' || $modelo === '') {
            $erro = 'Informe o IMEI e o modelo do aparelho.';
        } elseif (localizarNaLista($estoque, $imei) !== null || localizarNaLista($enviados, $imei) !== null) {
            $erro = 'Este IMEI já existe no estoque ou já foi enviado e não pode ser duplicado.';
        } else {
            $registro = ['imei' => $imei, 'modelo' => $modelo, 'status' => $status, 'observacao' => trim((string) ($_POST['observacao'] ?? ''))];
            if (!empty($chip)) {
                $registro['chip'] = $chip;
            }
            $estoque[] = $registro;
            $estoque = removerDuplicadosPorImei($estoque);
            salvarJson($estoqueFile, $estoque);
            $mensagem = 'Aparelho adicionado ao estoque com sucesso.';
        }
    }

    if ($acao === 'marcar_enviado') {
        $imei = normalizarImei($_POST['imei'] ?? '');
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $canal = strtoupper(trim((string) ($_POST['canal'] ?? '')));
        $modelo = trim((string) ($_POST['modelo'] ?? ''));
        $cdLoja = trim((string) ($_POST['cd_loja'] ?? ''));

        $item = localizarNaLista($estoque, $imei);
        if ($item === null) {
            $erro = 'IMEI não encontrado no estoque.';
        } elseif ($nome === '' || $canal === '' || $modelo === '') {
            $erro = 'Preencha nome, canal e modelo para registrar o envio.';
        } elseif (localizarNaLista($enviados, $imei) !== null) {
            $erro = 'Este IMEI já foi registrado como enviado e não pode ser duplicado.';
        } else {
            $estoque = array_values(array_filter($estoque, fn($linha) => ($linha['imei'] ?? '') !== $imei));
            $enviados[] = [
                'imei' => $imei,
                'nome' => $nome,
                'cd_loja' => $cdLoja,
                'canal' => $canal,
                'modelo' => $modelo,
                'data_envio' => date('Y-m-d')
            ];
            $enviados = removerDuplicadosPorImei($enviados);
            salvarJson($estoqueFile, $estoque);
            salvarJson($enviadosFile, $enviados);
            $mensagem = 'Aparelho registrado como enviado com sucesso.';
        }
    }

    if ($acao === 'exportar_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="estoque.csv"');
        $saida = fopen('php://output', 'w');
        fputcsv($saida, ['IMEI', 'MODELO', 'STATUS', 'ICCID', 'STATUS CHIP', 'LINHA CADASTRADA', 'VINCULADO A', 'OBSERVACAO'], ';');
        foreach ($estoque as $item) {
            $chip = $item['chip'] ?? [];
            fputcsv($saida, [
                $item['imei'] ?? '',
                $item['modelo'] ?? '',
                $item['status'] ?? 'em_estoque',
                $chip['iccid'] ?? '',
                $chip['status'] ?? '',
                $chip['linha_cadastrada'] ?? '',
                $chip['pessoa'] ?? '',
                $item['observacao'] ?? ''
            ], ';');
        }
        fclose($saida);
        exit;
    }

    if ($acao === 'remover_estoque') {
        $imei = normalizarImei($_POST['imei'] ?? '');

        if ($imei === '') {
            $erro = 'IMEI inválido para remover do estoque.';
        } else {
            $estoque = array_values(array_filter($estoque, fn($linha) => ($linha['imei'] ?? '') !== $imei));
            salvarJson($estoqueFile, $estoque);
            $mensagem = 'Aparelho removido do estoque com sucesso.';
        }
    }
}

$statusFiltro = strtolower((string) ($_GET['status'] ?? 'todos'));
if (!in_array($statusFiltro, ['todos', 'em_estoque', 'pendente'], true)) {
    $statusFiltro = 'todos';
}

$termoBusca = trim((string) ($_GET['q'] ?? ''));
$estoqueExibicao = array_values(array_filter($estoque, function ($item) use ($termoBusca, $statusFiltro) {
    $statusAtual = strtolower((string) ($item['status'] ?? 'em_estoque'));
    $matchStatus = $statusFiltro === 'todos' || $statusAtual === $statusFiltro;
    if (!$matchStatus) {
        return false;
    }

    if ($termoBusca === '') {
        return true;
    }

$chip = $item['chip'] ?? [];
$texto = strtolower(
    ($item['imei'] ?? '') . ' ' .
    ($item['modelo'] ?? '') . ' ' .
    ($item['status'] ?? '') . ' ' .
    ($item['observacao'] ?? '') . ' ' .
    ($chip['iccid'] ?? '') . ' ' .
    ($chip['status'] ?? '') . ' ' .
    ($chip['linha_cadastrada'] ?? '') . ' ' .
    ($chip['pessoa'] ?? '')
);
return str_contains($texto, strtolower($termoBusca));
}));
$quantidadeEmEstoque = count(array_filter($estoque, fn($item) => strtolower((string) ($item['status'] ?? 'em_estoque')) === 'em_estoque'));
$quantidadePendentes = count(array_filter($estoque, fn($item) => strtolower((string) ($item['status'] ?? 'em_estoque')) === 'pendente'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo">📡 Gestão TI</div>
        <nav class="menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="colaboradores.php">👥 Colaboradores</a>
            <a href="ativos.php">💻 Ativos</a>
            <a href="estoque.php" class="active">📦 Estoque</a>
            <a href="enviados.php">📤 Enviados</a>
            <a href="chamados.php">🎫 Chamados</a>
            <a href="centros_de_custo.php">🏢 Centros de Custo</a>
            <a href="relatorios.php">📈 Relatórios</a>
            <a href="configuracoes.php">⚙ Configurações</a>
            <a href="logout.php" class="logout-link">🚪 Sair</a>
        </nav>
    </aside>

    <main class="content">
        <h1>Estoque</h1>

        <div class="cards">
            <div class="card estoque">
                <h3>Total em estoque</h3>
                <h2><?= count($estoque) ?></h2>
            </div>
            <div class="card colaboradores">
                <h3>Disponíveis</h3>
                <h2><?= $quantidadeEmEstoque ?></h2>
            </div>
            <div class="card chamados">
                <h3>Enviados</h3>
                <h2><?= count($enviados) ?></h2>
            </div>
        </div>

        <div class="status-tabs" aria-label="Filtros de estoque">
            <?php
            $tabs = [
                ['value' => 'todos', 'label' => 'Todos', 'count' => count($estoque)],
                ['value' => 'em_estoque', 'label' => 'Em estoque', 'count' => $quantidadeEmEstoque],
                ['value' => 'pendente', 'label' => 'Pendentes', 'count' => $quantidadePendentes],
            ];
            foreach ($tabs as $tab) :
                $query = $_GET;
                $query['status'] = $tab['value'];
                $href = 'estoque.php?' . http_build_query($query);
            ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= $statusFiltro === $tab['value'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($tab['label']) ?>
                    <span><?= (int) $tab['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="toolbar">
            <form method="get" class="inline-form search-form">
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFiltro) ?>">
                <input type="text" name="q" value="<?= htmlspecialchars($termoBusca) ?>" placeholder="Pesquisar por IMEI, modelo, status, ICCID ou nome do cliente">
                <button type="submit">Buscar</button>
            </form>
            <form method="post" class="inline-form">
                <input type="hidden" name="action" value="exportar_csv">
                <button type="submit">Exportar CSV</button>
            </form>
        </div>

        <?php if ($mensagem !== '') : ?>
            <div class="alert success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>
        <?php if ($erro !== '') : ?>
            <div class="alert danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h3>Adicionar aparelho manualmente</h3>
            <form method="post" class="grid-form">
                <input type="hidden" name="action" value="adicionar_manual">
                <div>
                    <label>IMEI</label>
                    <input type="text" name="imei" required>
                </div>
                <div>
                    <label>Modelo</label>
                    <input type="text" name="modelo" required>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="em_estoque">Em estoque</option>
                        <option value="pendente">Pendente</option>
                    </select>
                </div>
                <div>
                    <label>ICCID</label>
                    <input type="text" name="iccid" placeholder="Opcional">
                </div>
                <div>
                    <label>Status do chip</label>
                    <select name="chip_status">
                        <option value="">Selecione</option>
                        <option value="Disponível">Disponível</option>
                        <option value="Vinculado">Vinculado</option>
                        <option value="Reservado">Reservado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                <div>
                    <label>Linha cadastrada</label>
                    <input type="text" name="linha_cadastrada" placeholder="Opcional">
                </div>
                <div>
                    <label>Vinculado a</label>
                    <input type="text" name="pessoa_vinculada" placeholder="Nome da pessoa">
                </div>
                <div>
                    <label>Observação</label>
                    <input type="text" name="observacao" placeholder="Opcional">
                </div>
                <div class="form-actions">
                    <button type="submit">Salvar</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h3>Importar arquivo</h3>
            <form method="post" enctype="multipart/form-data" class="grid-form">
                <input type="hidden" name="action" value="importar">
                <div class="full-width">
                    <label for="arquivo">Arquivo CSV ou XLSX</label>
                    <input type="file" id="arquivo" name="arquivo" accept=".csv,.xlsx,.xls" required>
                </div>
                <div class="form-actions">
                    <button type="submit">Importar</button>
                </div>
            </form>
        </section>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>IMEI</th>
                        <th>Modelo</th>
                        <th>Status</th>
                        <th>ICCID</th>
                        <th>Chip</th>
                        <th>Linha</th>
                        <th>Vínculo</th>
                        <th>Observação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estoqueExibicao)) : ?>
                        <tr>
                            <td colspan="9">Nenhum aparelho encontrado no estoque.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($estoqueExibicao as $item) : ?>
                            <?php $chip = $item['chip'] ?? []; ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($item['imei'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['modelo'] ?? '')) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars((string) ($item['status'] ?? 'em_estoque')) ?>"><?= htmlspecialchars(formatarStatus((string) ($item['status'] ?? 'em_estoque'))) ?></span></td>
                                <td><?= htmlspecialchars((string) ($chip['iccid'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($chip['status'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($chip['linha_cadastrada'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($chip['pessoa'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['observacao'] ?? '')) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <form method="post" class="inline-form action-form">
                                            <input type="hidden" name="action" value="marcar_enviado">
                                            <input type="hidden" name="imei" value="<?= htmlspecialchars((string) ($item['imei'] ?? '')) ?>">
                                            <input type="hidden" name="modelo" value="<?= htmlspecialchars((string) ($item['modelo'] ?? '')) ?>">
                                            <input type="text" name="nome" placeholder="Nome do cliente" required>
                                            <select name="canal" required>
                                                <option value="">Canal</option>
                                                <option value="CD">CD</option>
                                                <option value="VAREJO">VAREJO</option>
                                                <option value="4BIO">4BIO</option>
                                            </select>
                                            <input type="text" name="cd_loja" placeholder="CD / Loja" aria-label="CD / Loja">
                                            <button type="submit">Enviar</button>
                                        </form>
                                        <a class="button secondary small" href="detalhes_imei.php?imei=<?= urlencode((string) ($item['imei'] ?? '')) ?>">Detalhes</a>
                                        <form method="post" class="inline-form action-form compact-form">
                                            <input type="hidden" name="action" value="remover_estoque">
                                            <input type="hidden" name="imei" value="<?= htmlspecialchars((string) ($item['imei'] ?? '')) ?>">
                                            <button type="submit" class="danger-button" onclick="return confirm('Tem certeza que deseja remover este aparelho do estoque?');">Remover</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
