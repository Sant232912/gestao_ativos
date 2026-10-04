<?php
/**
 * Configuração do sistema de gestão de ativos.
 *
 * O token não deve ser gravado no código-fonte. Ele deve ser informado via
 * variável de ambiente (ex.: GITHUB_TOKEN) no servidor ou em um arquivo de
 * ambiente local que não seja versionado.
 */

function obterConfiguracaoAmbiente(string $chave, string $padrao = ''): string {
    $valor = getenv($chave);
    if ($valor === false || $valor === null) {
        return $padrao;
    }

    return trim((string) $valor);
}

define('GITHUB_TOKEN', obterConfiguracaoAmbiente('GITHUB_TOKEN', ''));
define('GITHUB_USER', obterConfiguracaoAmbiente('GITHUB_USER', 'Sant232912'));
define('GITHUB_REPO', obterConfiguracaoAmbiente('GITHUB_REPO', 'gestao_ativos'));
define('GITHUB_BRANCH', obterConfiguracaoAmbiente('GITHUB_BRANCH', 'main'));

define('DADOS_DIR', __DIR__ . '/dados');
define('CHAMADOS_FILE', DADOS_DIR . '/chamados.json');
define('CENTROS_FILE', DADOS_DIR . '/centros_de_custo.json');

define('LOG_DIR', DADOS_DIR . '/logs');
define('LOG_FILE', LOG_DIR . '/sync.log');
define('SYNC_GITHUB_ENABLED', GITHUB_TOKEN !== '');
define('SYNC_INTERVAL', (int) obterConfiguracaoAmbiente('SYNC_INTERVAL', '0'));

function logSync($mensagem, $tipo = 'info') {
    if (!is_dir(LOG_DIR)) {
        if (!mkdir(LOG_DIR, 0775, true) && !is_dir(LOG_DIR)) {
            return;
        }
    }

    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        LOG_FILE,
        "[$timestamp] [$tipo] $mensagem\n",
        FILE_APPEND | LOCK_EX
    );
}

function verificarConfiguracao() {
    $erros = [];

    if (GITHUB_TOKEN === '') {
        $erros[] = 'Token do GitHub não configurado';
    }

    if (GITHUB_USER === '') {
        $erros[] = 'Usuário do GitHub não configurado';
    }

    if (GITHUB_REPO === '') {
        $erros[] = 'Repositório do GitHub não configurado';
    }

    return $erros;
}
