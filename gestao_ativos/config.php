<?php
/**
 * Configuração do sistema de gestão de ativos.
 *
 * O token nunca deve ser salvo no código versionado. Configure GITHUB_TOKEN
 * como variável de ambiente no servidor quando a sincronização for desejada.
 */

define('GITHUB_TOKEN', getenv('GITHUB_TOKEN') ?: '');
define('GITHUB_USER', 'Sant232912');
define('GITHUB_REPO', 'gestao-ativos');
define('GITHUB_BRANCH', 'main');

define('DADOS_DIR', __DIR__ . '/dados');
define('CHAMADOS_FILE', DADOS_DIR . '/chamados.json');
define('CENTROS_FILE', DADOS_DIR . '/centros_de_custo.json');

define('LOG_DIR', DADOS_DIR . '/logs');
define('LOG_FILE', LOG_DIR . '/sync.log');
define('SYNC_GITHUB_ENABLED', GITHUB_TOKEN !== '');
define('SYNC_INTERVAL', 0);

function logSync($mensagem, $tipo = 'info') {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0777, true);
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
