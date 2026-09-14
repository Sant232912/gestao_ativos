<?php
// Configurações de acesso
$USER = "sys";
$PASSWORD = "23291250";
$DSN = "localhost:1521/xe"; // Ajuste se necessário

function ler_entrada(string $prompt): string
{
    echo $prompt;
    $handle = fopen('php://stdin', 'r');
    $line = fgets($handle);
    fclose($handle);
    return trim($line ?: '');
}

function buscar_chamados(): void
{
    global $USER, $PASSWORD, $DSN;

    $conn = oci_connect($USER, $PASSWORD, $DSN, 'AL32UTF8', OCI_SYSDBA);
    if (!$conn) {
        $error = oci_error();
        echo "\n❌ ERRO AO CONECTAR AO BANCO: " . $error['message'] . "\n";
        return;
    }

    echo "\n--- CONSULTA DE CHAMADOS POR COLABORADOR ---\n";
    $id_busca = ler_entrada("Digite o ID do Colaborador (Ex: 1 para Freddie Mercury): ");

    $sql = "SELECT c.id_chamado, col.nome_completo, p.nome_peca, c.descricao_problema, c.status_chamado
            FROM chamados_suporte c
            JOIN colaboradores col ON c.id_colab = col.id_colab
            JOIN pecas_estoque p ON c.id_peca = p.id_peca
            WHERE col.id_colab = :id_colab";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_colab', $id_busca);
    oci_execute($stmt);

    $resultados = [];
    while (($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) !== false) {
        $resultados[] = $row;
    }

    if (count($resultados) > 0) {
        echo "\n" . str_repeat('=', 75) . "\n";
        $nome_usuario = $resultados[0]['NOME_COMPLETO'];
        echo "📋 Chamados encontrados para: {$nome_usuario} (ID: {$id_busca})\n";
        echo str_repeat('-', 75) . "\n";
        echo sprintf("%-5s | %-20s | %s\n", 'ID', 'ITEM', 'STATUS');
        echo str_repeat('-', 75) . "\n";

        foreach ($resultados as $row) {
            echo sprintf("%-5s | %-20s | %s\n", $row['ID_CHAMADO'], $row['NOME_PECA'], $row['STATUS_CHAMADO']);
            echo "   ↳ Descrição: " . $row['DESCRICAO_PROBLEMA'] . "\n";
            echo str_repeat('-', 75) . "\n";
        }
    } else {
        echo "\n⚠️ Nenhum chamado encontrado para o colaborador ID {$id_busca}.\n";
    }

    oci_free_statement($stmt);
    oci_close($conn);
}

try {
    buscar_chamados();
} catch (Exception $e) {
    echo "\n❌ ERRO NA BUSCA: " . $e->getMessage() . "\n";
}
