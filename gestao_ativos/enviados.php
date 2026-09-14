<?php
ini_set('memory_limit', '1024M');
set_time_limit(300);

require_once __DIR__ . '/auth.php';
exigirAutenticacao();

$enviadosFile = __DIR__ . '/dados/enviados.json';
$estoqueFile = __DIR__ . '/dados/estoque.json';

$enviados = file_exists($enviadosFile) ? json_decode(file_get_contents($enviadosFile), true) : [];
$estoque = file_exists($estoqueFile) ? json_decode(file_get_contents($estoqueFile), true) : [];
$enviados = is_array($enviados) ? removerDuplicadosPorImei($enviados) : [];
$estoque = is_array($estoque) ? $estoque : [];
$mensagem = '';
$erro = '';

function salvarEnviados(array $dados): void {
    file_put_contents(__DIR__ . '/dados/enviados.json', json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function removerDuplicadosPorImei(array $lista): array
{
    $deduplicada = [];
    $seen = [];

    foreach ($lista as $item) {
        $imei = preg_replace('/\D+/', '', (string) ($item['imei'] ?? ''));
        if ($imei === '' || isset($seen[$imei])) {
            continue;
        }

        $seen[$imei] = true;
        $deduplicada[] = $item;
    }

    return array_values($deduplicada);
}

function normalizarCanal(string $valor): string
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }

    $normal = strtolower(str_replace(['-', '_'], ' ', preg_replace('/\s+/', ' ', $valor)));

    if (str_contains($normal, '4bio')) {
        return '4BIO';
    }

    if (str_contains($normal, 'varejo')) {
        return 'VAREJO';
    }

    if (
        str_contains($normal, 'loja')
        || str_contains($normal, 'distrib')
        || str_contains($normal, 'expans')
    ) {
        return 'VAREJO';
    }

    if (str_contains($normal, 'cd')) {
        return 'CD';
    }

    return strtoupper($valor);
}

function inferirCanalPorLoja(string $valor): string
{
    $valor = strtolower(trim((string) $valor));
    if ($valor === '') {
        return '';
    }

    if (str_contains($valor, '4bio')) {
        return '4BIO';
    }

    if (str_contains($valor, 'loja') || str_contains($valor, 'distrib') || str_contains($valor, 'expans')) {
        return 'VAREJO';
    }

    if (str_contains($valor, 'cd')) {
        return 'CD';
    }

    return '';
}

function normalizarCampo(string $valor): string
{
    return strtolower(trim((string) preg_replace('/[^\pL\pN]+/u', ' ', $valor)));
}

function valorPorCampo(array $dados, array $alternativas): string
{
    $mapa = [];
    foreach ($dados as $chave => $valor) {
        $normalizada = normalizarCampo((string) $chave);
        $mapa[$normalizada] = $valor;
        $mapa[str_replace(' ', '', $normalizada)] = $valor;
    }

    foreach ($alternativas as $alternativa) {
        $normalizada = normalizarCampo((string) $alternativa);
        if (array_key_exists($normalizada, $mapa)) {
            return trim((string) $mapa[$normalizada]);
        }

        $compacta = str_replace(' ', '', $normalizada);
        if (array_key_exists($compacta, $mapa)) {
            return trim((string) $mapa[$compacta]);
        }
    }

    return '';
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

function colunaParaIndice(string $coluna): int
{
    $valor = 0;
    foreach (str_split(strtoupper($coluna)) as $char) {
        $valor = $valor * 26 + ((ord($char) - 64));
    }
    return $valor;
}

function normalizarRegistroEnviado(array $linha): ?array
{
    $imei = preg_replace('/\D+/', '', (string) valorPorCampo($linha, ['imei', 'imei_', 'numero_imei']));
    $nome = valorPorCampo($linha, ['nome', 'nome cliente', 'nome_cliente', 'cliente']);
    $canal = normalizarCanal(valorPorCampo($linha, ['canal', 'tipo canal', 'tipo_canal', 'canal_venda', 'canal venda', 'segmento', 'tipo']));
    $modelo = valorPorCampo($linha, ['modelo', 'nome_modelo', 'modelo_aparelho']);
    $dataEnvio = valorPorCampo($linha, ['data_envio', 'data envio', 'data', 'data de envio']);
    $cdLoja = valorPorCampo($linha, ['cd loja', 'cd_loja', 'cdloja', 'loja', 'loja cd', 'filial', 'sede']);

    if ($canal === '') {
        $canal = inferirCanalPorLoja($cdLoja);
    }

    if ($imei === '' || $nome === '' || $canal === '') {
        return null;
    }

    return [
        'imei' => $imei,
        'nome' => $nome,
        'cd_loja' => $cdLoja,
        'canal' => $canal,
        'modelo' => $modelo,
        'data_envio' => $dataEnvio !== '' ? $dataEnvio : date('Y-m-d')
    ];
}

function importarCsvEnviados(array $upload): array
{
    $handle = fopen($upload['tmp_name'], 'r');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível ler o arquivo CSV dos enviados.');
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
            if (is_array($dadosLinha)) {
                $registro = normalizarRegistroEnviado($dadosLinha);
                if ($registro !== null) {
                    $resultados[] = $registro;
                }
            }
        }
    }

    fclose($handle);

    return $resultados;
}

function importarXlsxEnviados(array $upload): array
{
    $zip = new ZipArchive();
    $abriu = $zip->open($upload['tmp_name']);
    if ($abriu !== true) {
        throw new RuntimeException('O arquivo Excel dos enviados não pôde ser aberto.');
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

        $registro = normalizarRegistroEnviado($data);
        if ($registro !== null) {
            $registros[] = $registro;
        }
    }

    return $registros;
}

$pesquisa = trim((string) ($_GET['q'] ?? ''));
if ($pesquisa !== '') {
$termoBusca = strtolower($pesquisa);
$enviados = array_values(array_filter($enviados, function ($item) use ($termoBusca) {
    $texto = strtolower((string) (($item['imei'] ?? '') . ' ' . ($item['nome'] ?? '') . ' ' . ($item['cd_loja'] ?? '') . ' ' . ($item['canal'] ?? '') . ' ' . ($item['modelo'] ?? '')));
    return str_contains($texto, $termoBusca);
}));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo_envio') {
    $imei = preg_replace('/\D+/', '', (string) ($_POST['imei'] ?? ''));
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $canal = strtoupper(trim((string) ($_POST['canal'] ?? '')));
    $modelo = trim((string) ($_POST['modelo'] ?? ''));
    $cdLoja = trim((string) ($_POST['cd_loja'] ?? ''));

    if ($imei === '' || $nome === '' || $canal === '' || $modelo === '') {
        $erro = 'Preencha IMEI, nome, canal e modelo.';
    } elseif (array_filter($enviados, fn($item) => ($item['imei'] ?? '') === $imei)) {
        $erro = 'Este IMEI já foi registrado como enviado.';
    } elseif (array_filter($estoque, fn($item) => ($item['imei'] ?? '') === $imei)) {
        $erro = 'Este IMEI ainda está no estoque. Primeiro registre o envio pelo estoque.';
    } else {
        $enviados[] = [
            'imei' => $imei,
            'nome' => $nome,
            'cd_loja' => $cdLoja,
            'canal' => $canal,
            'modelo' => $modelo,
            'data_envio' => date('Y-m-d')
        ];
        $enviados = removerDuplicadosPorImei($enviados);
        salvarEnviados($enviados);
        header('Location: enviados.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'exportar_csv_enviados') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aparelhos_enviados.csv"');
    $saida = fopen('php://output', 'w');
   fputcsv($saida, ['IMEI', 'NOME', 'MODELO', 'CD/LOJA', 'CANAL', 'DATA_ENVIO'], ';');
    foreach ($enviados as $item) {
        fputcsv($saida, [
            $item['imei'] ?? '',
            $item['nome'] ?? '',
           $item['modelo'] ?? '',
           $item['cd_loja'] ?? '',
           $item['canal'] ?? '',
           $item['data_envio'] ?? ''
       ], ';');
   }
   fclose($saida);
exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'importar_enviados') {
    if (!isset($_FILES['arquivo_enviados']) || $_FILES['arquivo_enviados']['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Selecione um arquivo para importar os enviados.';
    } else {
        $arquivo = $_FILES['arquivo_enviados'];
        $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        try {
            if ($ext === 'csv') {
                $novos = importarCsvEnviados($arquivo);
            } elseif ($ext === 'xlsx' || $ext === 'xls') {
                $novos = importarXlsxEnviados($arquivo);
            } else {
                throw new RuntimeException('Formato de arquivo não suportado. Use CSV ou XLSX.');
            }

            $adicionados = 0;
            $ignorados = 0;
            foreach ($novos as $item) {
                $imei = preg_replace('/\D+/', '', (string) $item['imei']);
                if ($imei === '' || array_filter($enviados, fn($registro) => ($registro['imei'] ?? '') === $imei)) {
                    $ignorados++;
                    continue;
                }

                $estoque = array_values(array_filter($estoque, fn($registro) => ($registro['imei'] ?? '') !== $imei));
                $enviados[] = [
                    'imei' => $imei,
                    'nome' => trim((string) $item['nome']),
                    'cd_loja' => trim((string) ($item['cd_loja'] ?? '')),
                    'canal' => normalizarCanal((string) $item['canal']),
                    'modelo' => trim((string) ($item['modelo'] ?? '')),
                    'data_envio' => trim((string) ($item['data_envio'] ?? date('Y-m-d')))
                ];
                $adicionados++;
            }

            $enviados = removerDuplicadosPorImei($enviados);
            salvarEnviados($enviados);
            file_put_contents($estoqueFile, json_encode($estoque, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $mensagem = "Importação concluída. $adicionados item(ns) adicionado(s) e $ignorados ignorado(s) por duplicidade ou dados incompletos.";
        } catch (Throwable $th) {
            $erro = $th->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviados</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo">📡 Gestão TI</div>
        <nav class="menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="colaboradores.php">👥 Colaboradores</a>
            <a href="ativos.php">💻 Ativos</a>
            <a href="estoque.php">📦 Estoque</a>
            <a href="enviados.php" class="active">📤 Enviados</a>
            <a href="chamados.php">🎫 Chamados</a>
            <a href="centros_de_custo.php">🏢 Centros de Custo</a>
            <a href="relatorios.php">📈 Relatórios</a>
            <a href="configuracoes.php">⚙ Configurações</a>
            <a href="logout.php" class="logout-link">🚪 Sair</a>
        </nav>
    </aside>

    <main class="content">
        <h1>Enviados</h1>

        <div class="cards">
            <div class="card colaboradores">
                <h3>Total enviados</h3>
                <h2><?= count($enviados) ?></h2>
            </div>
            <div class="card chamados">
                <h3>CD</h3>
                <h2><?= count(array_filter($enviados, fn($item) => strtoupper((string) ($item['canal'] ?? '')) === 'CD')) ?></h2>
            </div>
            <div class="card estoque">
                <h3>VAREJO / 4BIO</h3>
                <h2><?= count(array_filter($enviados, fn($item) => in_array(strtoupper((string) ($item['canal'] ?? '')), ['VAREJO', '4BIO'], true))) ?></h2>
            </div>
        </div>

        <div class="toolbar">
            <form method="get" class="inline-form search-form">
                <input type="text" name="q" value="<?= htmlspecialchars($pesquisa) ?>" placeholder="Pesquisar por IMEI, nome, canal ou modelo">
                <button type="submit">Buscar</button>
            </form>
            <form method="post" class="inline-form">
                <input type="hidden" name="action" value="exportar_csv_enviados">
                <button type="submit">Exportar CSV</button>
            </form>
            <a href="estoque.php" class="button secondary">Voltar ao estoque</a>
        </div>

        <?php if ($mensagem !== '') : ?>
            <div class="alert success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>
        <?php if ($erro !== '') : ?>
            <div class="alert danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h3>Registrar envio</h3>
            <form method="post" class="grid-form">
                <input type="hidden" name="action" value="novo_envio">
                <div>
                    <label>IMEI</label>
                    <input type="text" name="imei" required>
                </div>
                <div>
                    <label>Nome do cliente</label>
                    <input type="text" name="nome" required>
                </div>
                <div>
                    <label>Canal</label>
                    <select name="canal" required>
                        <option value="">Selecione</option>
                        <option value="CD">CD</option>
                        <option value="VAREJO">VAREJO</option>
                        <option value="4BIO">4BIO</option>
                    </select>
                </div>
                <div>
                    <label>CD / Loja</label>
                    <input type="text" name="cd_loja" placeholder="Opcional">
                </div>
                <div>
                    <label>Modelo</label>
                    <input type="text" name="modelo" required>
                </div>
                <div class="form-actions">
                    <button type="submit">Salvar envio</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h3>Importar múltiplos enviados</h3>
            <form method="post" enctype="multipart/form-data" class="grid-form">
                <input type="hidden" name="action" value="importar_enviados">
                <div class="full-width">
                    <label for="arquivo_enviados">Arquivo CSV ou XLSX</label>
                    <input type="file" id="arquivo_enviados" name="arquivo_enviados" accept=".csv,.xlsx,.xls" required>
                    <small>Estrutura esperada: IMEI, NOME, CD/LOJA, CANAL (ex.: CD, VAREJO, 4BIO). O campo MODELO é opcional e pode ficar em branco.</small>
                </div>
                <div class="form-actions">
                    <button type="submit">Importar enviados</button>
                </div>
            </form>
        </section>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>IMEI</th>
                        <th>Nome</th>
                        <th>Modelo</th>
                        <th>Canal</th>
                        <th>CD / Loja</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enviados)) : ?>
                        <tr>
                            <td colspan="7">Nenhum aparelho enviado cadastrado.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($enviados as $item) : ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($item['imei'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['nome'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['modelo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['canal'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['cd_loja'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($item['data_envio'] ?? '-')) ?></td>
                                <td><a class="button secondary small" href="detalhes_imei.php?imei=<?= urlencode((string) ($item['imei'] ?? '')) ?>">Detalhes</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
