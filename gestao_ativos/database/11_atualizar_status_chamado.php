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

function atualizar_chamado(): void
{
    global $USER, $PASSWORD, $DSN;

    $conn = oci_connect($USER, $PASSWORD, $DSN, 'AL32UTF8', OCI_SYSDBA);
    if (!$conn) {
        $error = oci_error();
        echo "\n❌ ERRO AO CONECTAR AO BANCO: " . $error['message'] . "\n";
        return;
    }

    echo "\n--- 🛠️ SISTEMA DE ATUALIZAÇÃO DE CHAMADOS ---\n";
    $id_chamado = ler_entrada("Digite o ID do chamado que deseja atualizar: ");

    $stmt = oci_parse($conn, "SELECT descricao_problema, status_chamado FROM chamados_suporte WHERE id_chamado = :id_chamado");
    oci_bind_by_name($stmt, ':id_chamado', $id_chamado);
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);
    oci_free_statement($stmt);

    if ($row) {
        $desc_atual = $row['DESCRICAO_PROBLEMA'];
        $status_atual = $row['STATUS_CHAMADO'];

        echo "\nDados Atuais:\n";
        echo "📝 Descrição: {$desc_atual}\n";
        echo "📊 Status: {$status_atual}\n";
        echo str_repeat('-', 40) . "\n";

        echo "O que deseja fazer?\n";
        echo "1 - Alterar Status (Ex: Concluído, Cancelado)\n";
        echo "2 - Corrigir Descrição\n";
        echo "3 - Ambos\n";
        $opcao = ler_entrada("Escolha uma opção: ");

        $novo_status = $status_atual;
        $nova_desc = $desc_atual;

        if (in_array($opcao, ['1', '3'], true)) {
            $novo_status = ler_entrada("Digite o novo Status: ");
        }
        if (in_array($opcao, ['2', '3'], true)) {
            $nova_desc = ler_entrada("Digite a nova Descrição: ");
        }

        $sql_update = "UPDATE chamados_suporte SET status_chamado = :status, descricao_problema = :descricao WHERE id_chamado = :id_chamado";
        $stmt = oci_parse($conn, $sql_update);
        oci_bind_by_name($stmt, ':status', $novo_status);
        oci_bind_by_name($stmt, ':descricao', $nova_desc);
        oci_bind_by_name($stmt, ':id_chamado', $id_chamado);
        oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);

        echo "\n✅ Chamado #{$id_chamado} atualizado com sucesso!\n";
    } else {
        echo "\n⚠️ Chamado ID {$id_chamado} não encontrado.\n";
    }

    oci_close($conn);
}

try {
    atualizar_chamado();
} catch (Exception $e) {
    echo "\n❌ ERRO AO ATUALIZAR: " . $e->getMessage() . "\n";
}
