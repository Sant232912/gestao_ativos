<?php
// Configurações de acesso (mantendo o seu padrão)
$USER = "sys";
$PASSWORD = "23291250";
$DSN = "localhost:1521/xe"; // Ajuste se necessário

function gerar_relatorio(): void
{
    global $USER, $PASSWORD, $DSN;

    $conn = oci_connect($USER, $PASSWORD, $DSN, 'AL32UTF8', OCI_SYSDBA);
    if (!$conn) {
        $error = oci_error();
        echo "\n❌ ERRO AO CONECTAR AO BANCO: " . $error['message'] . "\n";
        return;
    }

    oci_execute(oci_parse($conn, "ALTER SESSION SET CURRENT_SCHEMA = SYS"));

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "📊 RELATÓRIO EXECUTIVO DE INVENTÁRIO TI\n";
    echo str_repeat('=', 60) . "\n";

    // 1. RESUMO FINANCEIRO (Soma total do valor em estoque)
    $stmt = oci_parse($conn, "SELECT SUM(valor_unitario * qtd_inicial) AS total_investido FROM pecas_estoque");
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);
    $total_investido = $row['TOTAL_INVESTIDO'] ?? 0;
    if ($total_investido === null) {
        $total_investido = 0;
    }
    oci_free_statement($stmt);

    // 2. TOTAL DE ITENS ÚNICOS
    $stmt = oci_parse($conn, "SELECT COUNT(*) AS total_itens FROM pecas_estoque");
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);
    $total_itens = $row['TOTAL_ITENS'] ?? 0;
    oci_free_statement($stmt);

    echo sprintf("💰 Valor Total em Ativos: R$ %0.2f\n", $total_investido);
    echo sprintf("📦 Variedade de Itens: %d modelos cadastrados\n", $total_itens);
    echo str_repeat('-', 60) . "\n";

    // 3. ALERTA DE ESTOQUE CRÍTICO (Menos de 10 unidades)
    echo "\n⚠️  ALERTA: ITENS COM ESTOQUE BAIXO (MENOS DE 10 UNID.)\n";
    echo sprintf("%-5s | %-35s | %s\n", 'ID', 'NOME DO ATIVO', 'QTD');
    echo str_repeat('-', 60) . "\n";

    $sqlCritico = "SELECT id_peca, nome_peca, qtd_inicial FROM pecas_estoque WHERE qtd_inicial < 10 ORDER BY qtd_inicial ASC";
    $stmt = oci_parse($conn, $sqlCritico);
    oci_execute($stmt);

    while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) !== false) {
        $idP = $row['ID_PECA'];
        $nome = $row['NOME_PECA'];
        $qtd = $row['QTD_INICIAL'];
        echo sprintf("%-5s | %-35s | %s\n", $idP, $nome, $qtd);
    }

    echo "\n" . str_repeat('=', 60) . "\n";

    oci_free_statement($stmt);
    oci_close($conn);
}

try {
    gerar_relatorio();
} catch (Exception $e) {
    echo "\n❌ ERRO AO GERAR RELATÓRIO: " . $e->getMessage() . "\n";
}
