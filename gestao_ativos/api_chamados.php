<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/github_sync.php';

if (!usuarioAutenticado()) {
    responderErro('Sua sessão expirou. Faça login novamente.', 401);
}

$arquivo = __DIR__ . '/dados/chamados.json';

if (!is_dir(__DIR__ . '/dados')) {
    mkdir(__DIR__ . '/dados', 0775, true);
}

if (!file_exists($arquivo)) {
    file_put_contents($arquivo, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$acao = $_GET['acao'] ?? '';

if ($metodo === 'OPTIONS') {
    http_response_code(204);
    exit;
}

switch ($metodo) {
    case 'GET':
        if ($acao === 'listar') {
            listarChamados();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;

    case 'POST':
        if ($acao === 'adicionar') {
            adicionarChamado();
        } elseif ($acao === 'importar') {
            importarChamados();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;

    case 'PUT':
        if ($acao === 'atualizar') {
            atualizarChamado();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;

    case 'DELETE':
        if ($acao === 'deletar') {
            deletarChamado();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['erro' => 'Ação não reconhecida'], JSON_UNESCAPED_UNICODE);
}

function responderErro(string $mensagem, int $status): void {
    http_response_code($status);
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

function carregarChamados(): array {
    global $arquivo;
    $conteudo = @file_get_contents($arquivo);
    if ($conteudo === false || trim($conteudo) === '') {
        return [];
    }

    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : [];
}

function salvarChamados(array $chamados): bool {
    global $arquivo;
    return (bool) file_put_contents(
        $arquivo,
        json_encode(array_values($chamados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function normalizarTexto($valor, string $padrao = ''): string {
    $texto = trim((string) $valor);
    return $texto !== '' ? $texto : $padrao;
}

function listarChamados() {
    echo json_encode(carregarChamados(), JSON_UNESCAPED_UNICODE);
}

function adicionarChamado() {
    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados)) {
        responderErro('JSON inválido', 400);
    }

    $numero = normalizarTexto($dados['numeroChamado'] ?? '');
    $solicitante = normalizarTexto($dados['solicitante'] ?? '');

    if ($numero === '' || $solicitante === '') {
        responderErro('Dados incompletos', 400);
    }

    $chamados = carregarChamados();
    $dados['id'] = uniqid('', true);
    $dados['numeroChamado'] = $numero;
    $dados['solicitante'] = $solicitante;
    $dados['status'] = $dados['status'] ?? 'Aberto';
    $dados['dataCriacao'] = $dados['dataCriacao'] ?? date('c');
    $chamados[] = $dados;

    if (salvarChamados($chamados)) {
        if (SYNC_GITHUB_ENABLED) {
            sincronizarComGitHub("Novo chamado adicionado: {$numero}", 'chamados');
        }

        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado adicionado'], JSON_UNESCAPED_UNICODE);
        return;
    }

    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar'], JSON_UNESCAPED_UNICODE);
}

function importarChamados() {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (empty($dados) || !is_array($dados)) {
        responderErro('Dados inválidos', 400);
    }

    $chamados = carregarChamados();
    $totalAdicionado = 0;

    foreach ($dados as $chamado) {
        if (!is_array($chamado)) {
            continue;
        }

        $numero = normalizarTexto($chamado['numeroChamado'] ?? '');
        $solicitante = normalizarTexto($chamado['solicitante'] ?? '');
        if ($numero === '' || $solicitante === '') {
            continue;
        }

        $chamado['id'] = uniqid('', true);
        $chamado['numeroChamado'] = $numero;
        $chamado['solicitante'] = $solicitante;
        $chamado['status'] = $chamado['status'] ?? 'Aberto';
        $chamado['dataCriacao'] = $chamado['dataCriacao'] ?? date('c');
        $chamados[] = $chamado;
        $totalAdicionado++;
    }

    if (salvarChamados($chamados)) {
        if (SYNC_GITHUB_ENABLED) {
            sincronizarComGitHub("Importação em massa: $totalAdicionado chamados adicionados", 'chamados');
        }

        echo json_encode([
            'sucesso' => true,
            'total' => $totalAdicionado,
            'mensagem' => $totalAdicionado . ' chamados importados'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar'], JSON_UNESCAPED_UNICODE);
}

function atualizarChamado() {
    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados) || empty($dados['id'])) {
        responderErro('ID não fornecido', 400);
    }

    $chamados = carregarChamados();
    $encontrado = false;

    foreach ($chamados as &$chamado) {
        if (($chamado['id'] ?? null) === $dados['id']) {
            $chamado = array_merge($chamado, $dados);
            $encontrado = true;
            break;
        }
    }
    unset($chamado);

    if (!$encontrado) {
        responderErro('Chamado não encontrado', 404);
    }

    if (salvarChamados($chamados)) {
        if (SYNC_GITHUB_ENABLED) {
            sincronizarComGitHub('Chamado atualizado: ' . ($dados['numeroChamado'] ?? $dados['id']), 'chamados');
        }

        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado atualizado'], JSON_UNESCAPED_UNICODE);
        return;
    }

    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar'], JSON_UNESCAPED_UNICODE);
}

function deletarChamado() {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados) || empty($dados['id'])) {
        responderErro('ID não fornecido', 400);
    }

    $chamados = carregarChamados();
    $quantidadeAntes = count($chamados);
    $chamados = array_values(array_filter($chamados, function ($chamado) use ($dados) {
        return ($chamado['id'] ?? null) !== $dados['id'];
    }));

    if (count($chamados) === $quantidadeAntes) {
        responderErro('Chamado não encontrado', 404);
    }

    if (salvarChamados($chamados)) {
        if (SYNC_GITHUB_ENABLED) {
            sincronizarComGitHub("Chamado deletado: {$dados['id']}", 'chamados');
        }

        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado deletado'], JSON_UNESCAPED_UNICODE);
        return;
    }

    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar'], JSON_UNESCAPED_UNICODE);
}
