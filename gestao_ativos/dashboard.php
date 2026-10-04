<?php
require_once __DIR__ . '/auth.php';
exigirAutenticacao();

$estoqueFile = __DIR__ . '/dados/estoque.json';
$enviadosFile = __DIR__ . '/dados/enviados.json';

$estoque = file_exists($estoqueFile) ? json_decode(file_get_contents($estoqueFile), true) : [];
$enviados = file_exists($enviadosFile) ? json_decode(file_get_contents($enviadosFile), true) : [];

$ativosCount = is_array($estoque) ? count($estoque) : 0;
$enviadosCount = is_array($enviados) ? count($enviados) : 0;
$emEstoque = count(array_filter($estoque, fn($item) => ($item['status'] ?? 'em_estoque') === 'em_estoque'));
$pendentes = count(array_filter($estoque, fn($item) => ($item['status'] ?? 'em_estoque') === 'pendente'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/style.css?v=20260907">
</head>
<body>
    <aside class="sidebar">
        <div class="logo">📡 Gestão TI</div>
        <nav class="menu">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="colaboradores.php">👥 Colaboradores</a>
            <a href="ativos.php">💻 Ativos</a>
            <a href="estoque.php">📦 Estoque</a>
            <a href="enviados.php">📤 Enviados</a>
            <a href="chamados.php">🎫 Chamados</a>
            <a href="centros_de_custo.php">🏢 Centros de Custo</a>
            <a href="relatorios.php">📈 Relatórios</a>
            <a href="configuracoes.php">⚙ Configurações</a>
            <a href="logout.php" class="logout-link">🚪 Sair</a>
        </nav>
    </aside>

    <main class="content">
        <h1>Dashboard</h1>

        <section class="cards">
            <div class="card ativos">
                <h3>Itens em estoque</h3>
                <h2><?= htmlspecialchars((string) $ativosCount) ?></h2>
            </div>

            <div class="card colaboradores">
                <h3>Disponíveis</h3>
                <h2><?= htmlspecialchars((string) $emEstoque) ?></h2>
            </div>

            <div class="card chamados">
                <h3>Pendentes</h3>
                <h2><?= htmlspecialchars((string) $pendentes) ?></h2>
            </div>

            <div class="card estoque">
                <h3>Enviados</h3>
                <h2><?= htmlspecialchars((string) $enviadosCount) ?></h2>
            </div>
        </section>

        <aside class="dashboard-postit" aria-label="Consulta rápida">
            <div class="postit-title">📌 Consulta rápida</div>
            <div class="postit-content">
                <p><strong>1000</strong> - Drogaria Cipriano (Tamoio)</p>
                <p><strong>2200</strong> - Profarma</p>
                <p><strong>5000</strong> - Rosário</p>
                <p><strong>8500</strong> - Drogasmil</p>

                <div class="postit-divider"></div>

                <p><strong>PROFARMA</strong> 45.453.214/0001-51</p>
                <p><strong>CSB</strong> 42.225.938/0001-50</p>
                <p><strong>TAMOIO</strong> 07.781.007/0021-80</p>
                <p><strong>ROSÁRIO</strong> 00.447.821/0001-70</p>
            </div>
        </aside>

        <section class="panel info-panel">
            <h3>Informações de coleta</h3>
            <p><strong>Processo:</strong> A troca será realizada via Correios, sendo o custo do envio de responsabilidade da loja. Como alternativa, caso alguém tenha uma viagem programada para nossa sede, no Rio de Janeiro, os aparelhos poderão ser entregues em mãos.</p>
            <p>Estou localizada no 3º andar, em frente à sala de reunião Varejo 03. Fico à disposição em caso de dúvidas.</p>
            <p><strong>Endereço para envio:</strong> Destinatário: Consultor Telegestao | Filial: SEDE BARRA | Av. José Silva de Azevedo Neto, 155 | Bloco P, 3º andar, Casa Shopping | Barra da Tijuca, Rio de Janeiro - RJ | 22775-056</p>
        </section>
    </main>
</body>
</html>
