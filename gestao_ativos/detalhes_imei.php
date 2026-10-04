<?php
require_once __DIR__ . '/auth.php';
exigirAutenticacao();

function lerJson(string $arquivo): array
{
    if (!file_exists($arquivo)) {
        return [];
    }

    $dados = json_decode(file_get_contents($arquivo), true);
    return is_array($dados) ? $dados : [];
}

function normalizarImei($valor): string
{
    return preg_replace('/\D+/', '', (string) $valor);
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

$estoque = lerJson(__DIR__ . '/dados/estoque.json');
$enviados = lerJson(__DIR__ . '/dados/enviados.json');
$imei = normalizarImei($_GET['imei'] ?? '');

$estoqueItem = null;
foreach ($estoque as $item) {
    if (($item['imei'] ?? '') === $imei) {
        $estoqueItem = $item;
        break;
    }
}

$enviadoItem = null;
foreach ($enviados as $item) {
    if (($item['imei'] ?? '') === $imei) {
        $enviadoItem = $item;
        break;
    }
}

$erro = 'IMEI não encontrado no estoque ou no histórico de enviados.';
if ($imei === '') {
    $erro = 'Informe um IMEI válido para consultar os detalhes.';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do IMEI</title>
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
        <h1>Detalhes do IMEI</h1>

        <div class="toolbar">
            <a href="estoque.php" class="button secondary">Voltar ao estoque</a>
            <a href="enviados.php" class="button secondary">Voltar aos enviados</a>
        </div>

        <?php if ($imei === '' || ($estoqueItem === null && $enviadoItem === null)) : ?>
            <div class="alert danger"><?= htmlspecialchars($erro) ?></div>
        <?php else : ?>
            <section class="panel">
                <h3>Resumo do aparelho</h3>
                <div class="details-grid">
                    <div class="detail-box">
                        <label>IMEI</label>
                        <strong><?= htmlspecialchars((string) $imei) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Modelo</label>
                        <strong><?= htmlspecialchars((string) (($estoqueItem['modelo'] ?? $enviadoItem['modelo'] ?? '-'))) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Status</label>
                        <strong><?= htmlspecialchars(formatarStatus((string) (($estoqueItem['status'] ?? ($enviadoItem ? 'enviado' : 'em_estoque'))))) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Nome vinculado</label>
                        <strong><?= htmlspecialchars((string) ($enviadoItem['nome'] ?? 'Sem vínculo')) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Canal</label>
                        <strong><?= htmlspecialchars((string) ($enviadoItem['canal'] ?? 'Não informado')) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>ICCID</label>
                        <strong><?= htmlspecialchars((string) (($estoqueItem['chip']['iccid'] ?? $enviadoItem['chip']['iccid'] ?? 'Não informado'))) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Status do chip</label>
                        <strong><?= htmlspecialchars((string) (($estoqueItem['chip']['status'] ?? $enviadoItem['chip']['status'] ?? 'Não informado'))) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Linha cadastrada</label>
                        <strong><?= htmlspecialchars((string) (($estoqueItem['chip']['linha_cadastrada'] ?? $enviadoItem['chip']['linha_cadastrada'] ?? 'Não informado'))) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Vínculo</label>
                        <strong><?= htmlspecialchars((string) (($estoqueItem['chip']['pessoa'] ?? $enviadoItem['chip']['pessoa'] ?? 'Sem vínculo'))) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Observação</label>
                        <strong><?= htmlspecialchars((string) ($estoqueItem['observacao'] ?? 'Sem observação')) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Data do envio</label>
                        <strong><?= htmlspecialchars((string) ($enviadoItem['data_envio'] ?? 'Não enviado')) ?></strong>
                    </div>
                    <div class="detail-box">
                        <label>Origem</label>
                        <strong><?= $estoqueItem !== null ? 'Estoque' : 'Enviado' ?></strong>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
