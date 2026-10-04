<?php
require_once __DIR__ . '/auth.php';
exigirAutenticacao();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações</title>
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
           <a href="enviados.php">📤 Enviados</a>
           <a href="chamados.php">🎫 Chamados</a>
           <a href="relatorios.php">📈 Relatórios</a>
           <a href="configuracoes.php" class="active">⚙ Configurações</a>
           <a href="logout.php" class="logout-link">🚪 Sair</a>
        </nav>
    </aside>

    <main class="content">
        <h1>Configurações</h1>
        <section class="panel">
           <h3>Status do sistema</h3>
           <p>O módulo de estoque foi ajustado para evitar duplicidade de IMEIs, importar CSV/XLSX e exportar dados de forma segura.</p>
           <p>Também foi incluída a aba <strong>Enviados</strong> para controlar aparelhos já entregues com IMEI, nome e canal.</p>
        </section>
    </main>
</body>
</html>
